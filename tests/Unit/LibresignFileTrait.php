<?php

namespace OCA\Libresign\Tests\Unit;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use OCA\Libresign\Service\SignFileService;

trait LibresignFileTrait {
	/**
	 * @var MockWebServer
	 */
	protected static $libresignTraitServer;

	private $libresignFileTraitFiles = [];

	/** @var SignFileService */
	private $libresignFileTraitSignFileService;

	public function requestSignFile($data): array {
		if (!self::$libresignTraitServer) {
			self::$libresignTraitServer = new MockWebServer();
			self::$libresignTraitServer->start();
		}
		self::$libresignTraitServer->setResponseOfPath('/api/v1/cfssl/newcert', new Response(
			file_get_contents(__DIR__ . '/../fixtures/cfssl/newcert-with-success.json')
		));

		$this->mockConfig([
			'libresign' => [
				'notifyUnsignedUser' => 0,
				'commonName' => 'CommonName',
				'country' => 'Brazil',
				'organization' => 'Organization',
				'organizationUnit' => 'organizationUnit',
				'cfsslUri' => self::$libresignTraitServer->getServerRoot() . '/api/v1/cfssl/'
			]
		]);

		if (!isset($data['settings'])) {
			$data['settings']['separator'] = '_';
			$data['settings']['folderPatterns'][] = [
				'name' => 'date',
				'setting' => 'Y-m-d\TH:i:s.u'
			];
			$data['settings']['folderPatterns'][] = [
				'name' => 'name'
			];
			$data['settings']['folderPatterns'][] = [
				'name' => 'userId'
			];
		}
		$file = $this->getSignFileService()->save($data);
		$this->addFile($file);
		return $file;
	}

	/**
	 * @return \OCA\Libresign\Service\SignFileService
	 */
	public function getSignFileService(): \OCA\Libresign\Service\SignFileService {
		if (!$this->libresignFileTraitSignFileService) {
			$this->libresignFileTraitSignFileService = \OC::$server->get(\OCA\Libresign\Service\SignFileService::class);
		}
		return $this->libresignFileTraitSignFileService;
	}

	public function addFile($file) {
		$this->libresignFileTraitFiles[] = $file;
	}

	/**
	 * @after
	 */
	public function libresignFileTearDown(): void {
		foreach ($this->libresignFileTraitFiles as $file) {
			$toRemove = [
				'uuid' => $file['uuid'],
				'users' => []
			];
			foreach ($file['users'] as $user) {
				if (is_array($user)) {
					$toRemove['users'][] = [
						'email' => $user['email']
					];
				} else {
					$toRemove['users'][] = [
						'email' => $user->getEmail()
					];
				}
			}
			try {
				$this->getSignFileService()->deleteSignRequest($toRemove);
			} catch (\Throwable $th) {
			}
		}
	}
}
