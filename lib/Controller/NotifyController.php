<?php

namespace OCA\Libresign\Controller;

use OCA\Libresign\AppInfo\Application;
use OCA\Libresign\Service\NotifyService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;

class NotifyController extends Controller {
	/** @var IL10N */
	private $l10n;
	/** @var NotifyService */
	private $notifyService;

	public function __construct(
		IRequest $request,
		IL10N $l10n,
		NotifyService $notifyService
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->l10n = $l10n;
		$this->notifyService = $notifyService;
	}
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function signers($fileId, $signers) {
		try {
			$this->notifyService->signers($fileId, $signers);
		} catch (\Throwable $th) {
			return new JSONResponse(
				[
					'success' => false,
					'messages' => [
						[
							'type' => 'danger',
							'message' => $th->getMessage()
						]
					]
				],
				Http::STATUS_UNAUTHORIZED
			);
		}
		return new JSONResponse([
			'success' => true,
			'message' => $this->l10n->t('Notification sent with success.')
		], Http::STATUS_OK);
	}
}
