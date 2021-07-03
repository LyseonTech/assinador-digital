<?php

namespace OCA\Libresign\Controller;

use OC\Authentication\Login\Chain;
use OC\Authentication\Login\LoginData;
use OCA\Libresign\AppInfo\Application;
use OCA\Libresign\Helper\JSActions;
use OCA\Libresign\Service\AccountService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class AccountController extends ApiController {
	/** @var IL10N */
	private $l10n;
	/** @var AccountService */
	private $account;
	/** @var Chain */
	private $loginChain;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IUserSession */
	private $userSession;

	public function __construct(
		IRequest $request,
		IL10N $l10n,
		AccountService $account,
		Chain $loginChain,
		IURLGenerator $urlGenerator,
		IUserSession $userSession
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->l10n = $l10n;
		$this->account = $account;
		$this->loginChain = $loginChain;
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @PublicPage
	 * @UseSession
	 * @return JSONResponse
	 */
	public function createToSign(string $uuid, string $email, string $password, string $signPassword) {
		try {
			$data = [
				'uuid' => $uuid,
				'email' => $email,
				'password' => $password,
				'signPassword' => $signPassword
			];
			$this->account->validateCreateToSign($data);
			$this->account->validateCertificateData($data);

			$fileToSign = $this->account->getFileByUuid($uuid);
			$fileUser = $this->account->getFileUserByUuid($uuid);

			$this->account->createToSign($uuid, $email, $password, $signPassword);
			$data = [
				'success' => true,
				'message' => $this->l10n->t('Success'),
				'action' => JSActions::ACTION_SIGN,
				'pdf' => [
					'url' => $this->urlGenerator->linkToRoute('libresign.page.getPdfUser', ['uuid' => $uuid])
				],
				'filename' => $fileToSign['fileData']->getName(),
				'description' => $fileUser->getDescription()
			];

			$loginData = new LoginData(
				$this->request,
				trim($email),
				$password
			);
			$this->loginChain->process($loginData);
		} catch (\Throwable $th) {
			return new JSONResponse(
				[
					'success' => false,
					'message' => $th->getMessage(),
					'action' => JSActions::ACTION_DO_NOTHING
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
		return new JSONResponse(
			$data,
			Http::STATUS_OK
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function signatureGenerate(
		string $signPassword
	): JSONResponse {
		try {
			$data = [
				'email' => $this->userSession->getUser()->getEMailAddress(),
				'signPassword' => $signPassword,
				'userId' => $this->userSession->getUser()->getUID()
			];
			$this->account->validateCertificateData($data);
			$signaturePath = $this->account->generateCertificate(...array_values($data));

			return new JSONResponse([
				'success' => true,
				'signature' => $signaturePath->getPath()
			], Http::STATUS_OK);
		} catch (\Exception $exception) {
			return new JSONResponse(
				[
					'success' => false,
					'message' => $exception->getMessage()
				],
				Http::STATUS_UNAUTHORIZED
			);
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function addFiles(array $files): JSONResponse {
		try {
			$this->account->addFilesToAccount($files, $this->userSession->getUser());
			return new JSONResponse([
				'success' => true
			], Http::STATUS_OK);
		} catch (\Exception $exception) {
			$exceptionData = json_decode($exception->getMessage());
			if (isset($exceptionData->file)) {
				$message = [
					'file' => $exceptionData->file,
					'type' => $exceptionData->type,
					'message' => $exceptionData->message
				];
			} else {
				$message = [
					'file' => null,
					'type' => null,
					'message' => $exception->getMessage()
				];
			}
			return new JSONResponse(
				[
					'success' => false,
					'messages' => [
						$message
					]
				],
				Http::STATUS_UNAUTHORIZED
			);
		}
	}

	/**
	 * Who am I.
	 *
	 * Validates API access data and returns the authenticated user's data.
	 *
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @PublicPage
	 * @return JSONResponse
	 */
	public function me() {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(
				[
					'message' => $this->l10n->t('Invalid user or password')
				],
				Http::STATUS_NOT_FOUND
			);
		}
		return new JSONResponse(
			[
				'uid' => $user->getUID()
			],
			Http::STATUS_OK
		);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function approvalList() {
		$json = <<<MOCK
		{
			"data": [
				{
				"account": {
					"displayName": "John Doe",
					"uid": "johndoe"
				},
				"files": [
					{
					"uuid": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
					"name": "filename",
					"callback": "http://app.test.coop/callback_webhook",
					"status": "signed",
					"status_date": "2021-12-31 22:45:50",
					"request_date": "2021-12-31 22:45:50",
					"requested_by": {
						"displayName": "John Doe",
						"uid": "johndoe"
					},
					"file": {
						"type": "pdf",
						"url": "http://cloud.test.coop/apps/libresign/pdf/46d30465-ae11-484b-aad5-327249a1e8ef",
						"nodeId": 2312
					},
					"signers": [
						{
						"email": "user@test.coop",
						"me": true,
						"displayName": "John Doe",
						"uid": "johndoe",
						"description": "As the company's CEO, you must sign this contract",
						"sign_date": "2021-12-31 22:45:50",
						"request_sign_date": "2021-12-31 22:45:50"
						}
					]
					}
				]
				}
			],
			"pagination": {
				"total": 300,
				"current": "/file/list?page=2&length=15",
				"next": "/file/list?page=3",
				"prev": "/file/list?page=1",
				"last": "/file/list?page=20",
				"first": "/file/list?page=0"
			}
		}
		MOCK;
		return new JSONResponse(
			json_decode($json, true)
		);
	}
}
