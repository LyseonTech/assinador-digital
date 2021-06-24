<?php

namespace OCA\Libresign\Controller;

use OC\Files\Filesystem;
use OCA\Libresign\AppInfo\Application;
use OCA\Libresign\Db\FileMapper;
use OCA\Libresign\Db\FileUserMapper;
use OCA\Libresign\Exception\LibresignException;
use OCA\Libresign\Handler\JLibresignHandler;
use OCA\Libresign\Helper\JSActions;
use OCA\Libresign\Service\AccountService;
use OCA\Libresign\Service\LibresignService;
use OCA\Libresign\Service\WebhookService;
use OCA\Libresign\Storage\ClientStorage;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use setasign\Fpdi\Fpdi;

class LibresignController extends Controller {
	use HandleParamsTrait;

	/** @var FileUserMapper */
	private $fileUserMapper;
	/** @var FileMapper */
	private $fileMapper;
	/** @var IRootFolder */
	private $root;
	/** @var IL10N */
	private $l10n;
	/** @var AccountService */
	private $account;
	/** @var JLibresignHandler */
	private $libresignHandler;
	/** @var WebhookService */
	private $webhook;
	/** @var LoggerInterface */
	private $logger;
	/** @var IUserSession */
	private $userSession;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IConfig */
	private $config;

	public function __construct(
		IRequest $request,
		FileUserMapper $fileUserMapper,
		FileMapper $fileMapper,
		IRootFolder $root,
		IL10N $l10n,
		AccountService $account,
		JLibresignHandler $libresignHandler,
		WebhookService $webhook,
		LoggerInterface $logger,
		IURLGenerator $urlGenerator,
		IConfig $config,
		IUserSession $userSession
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->fileUserMapper = $fileUserMapper;
		$this->fileMapper = $fileMapper;
		$this->root = $root;
		$this->l10n = $l10n;
		$this->account = $account;
		$this->libresignHandler = $libresignHandler;
		$this->webhook = $webhook;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @todo remove NoCSRFRequired
	 * @deprecated
	 */
	public function signDeprecated(
		string $inputFilePath = null,
		string $outputFolderPath = null,
		string $certificatePath = null,
		string $password = null
	): JSONResponse {
		try {
			$this->checkParams([
				'inputFilePath' => $inputFilePath,
				'outputFolderPath' => $outputFolderPath,
				'certificatePath' => $certificatePath,
				'password' => $password,
			]);

			$clientStorage = new ClientStorage($this->root->getUserFolder(
				$this->userSession->getUser()->getUID()
			));
			$service = new LibresignService($this->libresignHandler, $clientStorage);
			$fileSigned = $service->sign($inputFilePath, $outputFolderPath, $certificatePath, $password);

			return new JSONResponse(
				['fileSigned' => $fileSigned->getInternalPath()],
				HTTP::STATUS_OK
			);
		} catch (\Exception $exception) {
			return new JSONResponse(
				[
					'action' => JSActions::ACTION_DO_NOTHING,
					'errors' => [$this->l10n->t($exception->getMessage())]
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function signUsingFileid(string $fileId, string $password): JSONResponse {
		return $this->sign($password, $fileId);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function signUsingUuid(string $uuid, string $password): JSONResponse {
		return $this->sign($password, null, $uuid);
	}

	public function sign(string $password, string $file_id = null, string $uuid = null): JSONResponse {
		try {
			try {
				$user = $this->userSession->getUser();
				if ($file_id) {
					$fileUser = $this->fileUserMapper->getByFileIdAndUserId($file_id, $user->getUID());
				} else {
					$fileUser = $this->fileUserMapper->getByUuidAndUserId($uuid, $user->getUID());
				}
			} catch (\Throwable $th) {
				throw new LibresignException($this->l10n->t('Invalid data to sign file'), 1);
			}
			if ($fileUser->getSigned()) {
				throw new LibresignException($this->l10n->t('File already signed by you'), 1);
			}
			$fileData = $this->fileMapper->getById($fileUser->getFileId());
			Filesystem::initMountPoints($fileData->getuserId());
			$originalFile = $this->root->getById($fileData->getNodeId());
			if (count($originalFile) < 1) {
				throw new LibresignException($this->l10n->t('File not found'));
			}
			$originalFile = $originalFile[0];
			$signedFilePath = preg_replace(
				'/' . $originalFile->getExtension() . '$/',
				$this->l10n->t('signed') . '.' . $originalFile->getExtension(),
				$originalFile->getPath()
			);
			$certificatePath = $this->account->getPfx($fileUser->getUserId());

			if ($this->root->nodeExists($signedFilePath)) {
				/** @var \OCP\Files\File */
				$fileToSign = $this->root->get($signedFilePath);
			} else {
				/** @var \OCP\Files\File */
				$buffer = $this->writeFooter($originalFile, $fileData->getUuid());
				if (!$buffer) {
					$buffer = $originalFile->getContent($originalFile);
				}
				$fileToSign = $this->root->newFile($signedFilePath);
				$fileToSign->putContent($buffer);
			}
			list(, $signedContent) = $this->libresignHandler->signExistingFile($fileToSign, $certificatePath, $password);
			$fileToSign->putContent($signedContent);
			$fileUser->setSigned(time());
			$this->fileUserMapper->update($fileUser);

			$signers = $this->fileUserMapper->getByFileId($fileUser->getFileId());
			$total = array_reduce($signers, function ($carry, $signer) {
				$carry += $signer->getSigned() ? 1 : 0;
				return $carry;
			});
			if (count($signers) === $total) {
				$callbackUrl = $fileData->getCallback();
				if ($callbackUrl) {
					$this->webhook->notifyCallback(
						$callbackUrl,
						$fileData->getUuid(),
						$fileToSign
					);
				}
			}

			return new JSONResponse(
				[
					'success' => true,
					'action' => JSActions::ACTION_SIGNED,
					'message' => $this->l10n->t('File signed')
				],
				Http::STATUS_OK
			);
		} catch (LibresignException $e) {
			return new JSONResponse(
				[
					'success' => false,
					'action' => JSActions::ACTION_DO_NOTHING,
					'errors' => [$e->getMessage()]
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		} catch (\Throwable $th) {
			$message = $th->getMessage();
			$this->logger->error($message);
			$action = JSActions::ACTION_DO_NOTHING;
			switch ($message) {
				case 'Password to sign not defined. Create a password to sign':
					$action = JSActions::ACTION_CREATE_SIGNATURE_PASSWORD;
					// no break
				case 'Host violates local access rules.':
				case 'Certificate Password Invalid.':
				case 'Certificate Password is Empty.':
					$message = $this->l10n->t($message);
					break;
				default:
					$message = $this->l10n->t('Internal error. Contact admin.');
			}
			return new JSONResponse(
				[
					'success' => false,
					'action' => $action,
					'errors' => [$message]
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
	}

	private function writeFooter($file, $uuid) {
		$validation_site = $this->config->getAppValue(Application::APP_ID, 'validation_site');
		if (!$validation_site) {
			return;
		}
		$validation_site = rtrim($validation_site, '/').'/'.$uuid;
		$pdf = new Fpdi();
		$pageCount = $pdf->setSourceFile($file->fopen('r'));

		for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
			$templateId = $pdf->importPage($pageNo);

			$pdf->AddPage();
			$pdf->useTemplate($templateId, ['adjustPageSize' => true]);

			$pdf->SetFont('Helvetica');
			$pdf->SetFontSize(8);
			$pdf->SetAutoPageBreak(false);
			$pdf->SetXY(5, -10);

			$pdf->Write(8, iconv('UTF-8', 'windows-1252', $this->l10n->t(
				'Digital signed by LibreSign. Validate in %s',
				$validation_site
			)));
		}

		return $pdf->Output('S');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function validateUuid($uuid) {
		return $this->validate('Uuid', $uuid);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function validateFileId($fileId) {
		return $this->validate('FileId', $fileId);
	}

	private function validate(string $type, $identifier) {
		$canSign = false;
		try {
			try {
				$file = call_user_func(
					[$this->fileMapper, 'getBy' . $type],
					$identifier
				);
			} catch (\Throwable $th) {
				throw new LibresignException('Invalid data to validate file', 404);
			}
			if (!$file) {
				throw new LibresignException('Invalid file identifier', 404);
			}

			$return['success'] = true;
			$return['name'] = $file->getName();
			$return['file'] = $this->urlGenerator->linkToRoute('libresign.page.getPdf', ['uuid' => $file->getUuid()]);
			$signatures = $this->fileUserMapper->getByFileId($file->id);
			foreach ($signatures as $signature) {
				$signatureToShow = [
					'signed' => $signature->getSigned(),
					'displayName' => $signature->getDisplayName(),
					'fullName' => $signature->getFullName(),
					'me' => false
				];
				if ($this->userSession->getUser()) {
					$uid = $this->userSession->getUser()->getUID();
					$signatureToShow['me'] = $uid === $signature->getUserId();
					if ($uid === $signature->getUserId() && !$signature->getSigned()) {
						$canSign = true;
					}
				}
				$return['signatures'][] = $signatureToShow;
			}
			$statusCode = Http::STATUS_OK;
		} catch (\Throwable $th) {
			$message = $this->l10n->t($th->getMessage());
			$this->logger->error($message);
			$return = [
				'success' => false,
				'action' => JSActions::ACTION_DO_NOTHING,
				'errors' => [$message]
			];
			$statusCode = $th->getCode() ?? Http::STATUS_UNPROCESSABLE_ENTITY;
		}
		$return['settings'] = [
			'canRequestSign' => $this->account->canRequestSign($this->userSession->getUser()),
			'canSign' => $canSign
		];
		if ($return['settings']['canSign']) {
			$return['messages'][
				[
					'type' => 'info',
					'message' => $this->l10n->t('You need to sign this document')
				]
			];
		}
		if (!$return['settings']['canRequestSign'] && empty($return['signatures'])) {
			$return['messages'][
				[
					'type' => 'info',
					'message' => $this->l10n->t('You cannot request signature for this document, please contact your administrator')
				]
			];
		}
		return new JSONResponse($return, $statusCode);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function list($page = null, $limit = null) {
		$return = $this->account->list($this->userSession->getUser(), $page, $limit);
		return new JSONResponse($return, Http::STATUS_OK);
	}
}
