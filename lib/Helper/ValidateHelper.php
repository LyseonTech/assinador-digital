<?php

namespace OCA\Libresign\Helper;

use OCA\Libresign\AppInfo\Application;
use OCA\Libresign\Db\FileUserMapper;
use OCA\LibreSign\Db\File as LibresignFile;
use OCA\Libresign\Db\FileMapper;
use OCA\Libresign\Db\FileUser;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUser;

class ValidateHelper {
	/** @var IL10N */
	private $l10n;
	/** @var FileUserMapper */
	private $fileUserMapper;
	/** @var FileMapper */
	private $fileMapper;
	/** @var IConfig */
	private $config;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IRootFolder */
	private $root;
	/** @var LibresignFile */
	private $libresignFile;
	/** @var FileUser[] */
	private $signers;

	public function __construct(
		IL10N $l10n,
		FileUserMapper $fileUserMapper,
		FileMapper $fileMapper,
		IConfig $config,
		IGroupManager $groupManager,
		IRootFolder $root
	) {
		$this->l10n = $l10n;
		$this->fileUserMapper = $fileUserMapper;
		$this->fileMapper = $fileMapper;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->root = $root;
	}
	public function validateNewFile(array $data) {
		if (empty($data['file'])) {
			throw new \Exception($this->l10n->t('Empty file'));
		}
		if (empty($data['file']['url']) && empty($data['file']['base64']) && empty($data['file']['fileId'])) {
			throw new \Exception($this->l10n->t('Inform URL or base64 or fileID to sign'));
		}
		if (!empty($data['file']['fileId'])) {
			if (!is_numeric($data['file']['fileId'])) {
				throw new \Exception($this->l10n->t('Invalid fileID'));
			}
			$this->validateNotRequestedSign((int)$data['file']['fileId']);
			$this->validateIfNodeIdExists((int)$data['file']['fileId']);
			$this->validateMimeTypeAccepted((int)$data['file']['fileId']);
		}
		if (!empty($data['file']['base64'])) {
			$this->validateBase64($data['file']['base64']);
		}
	}

	public function validateBase64(string $base64) {
		$string = base64_decode($base64);
		$newBase64 = base64_encode($string);
		if ($newBase64 !== $base64) {
			throw new \Exception($this->l10n->t('Invalid base64 file'));
		}
	}

	public function validateNotRequestedSign(int $nodeId) {
		try {
			$fileMapper = $this->fileUserMapper->getByNodeId($nodeId);
		} catch (\Throwable $th) {
		}
		if (!empty($fileMapper)) {
			throw new \Exception($this->l10n->t('Already asked to sign this document'));
		}
	}

	public function validateIfNodeIdExists(int $nodeId) {
		try {
			$file = $this->root->getById($nodeId);
			$file = $file[0];
		} catch (\Throwable $th) {
			throw new \Exception($this->l10n->t('Invalid fileID'));
		}
		if (!$file) {
			throw new \Exception($this->l10n->t('Invalid fileID'));
		}
	}

	public function validateMimeTypeAccepted(int $nodeId) {
		$file = $this->root->getById($nodeId);
		$file = $file[0];
		if ($file->getMimeType() !== 'application/pdf') {
			throw new \Exception($this->l10n->t('Must be a fileID of a PDF'));
		}
	}

	public function validateLibreSignNodeId(int $nodeId) {
		try {
			$this->getLibreSignFileByNodeId($nodeId);
		} catch (\Throwable $th) {
			throw new \Exception($this->l10n->t('Invalid fileID'));
		}
	}

	private function getLibreSignFileByNodeId(int $nodeId): \OCP\Files\File {
		if (empty($this->file)) {
			$libresignFile = $this->getLibreSignFile($nodeId);

			$userFolder = $this->root->getUserFolder($libresignFile->getUserId());
			$this->file = $userFolder->getById($nodeId);
			if (!empty($this->file)) {
				$this->file = $this->file[0];
			}
		}
		return $this->file;
	}

	public function getLibreSignFile(?int $nodeId = null): ?LibresignFile {
		if (empty($this->libresignFile) && $nodeId) {
			$this->libresignFile = $this->fileMapper->getByFileId($nodeId);
		}
		return $this->libresignFile;
	}

	public function canRequestSign(IUser $user) {
		$authorized = json_decode($this->config->getAppValue(Application::APP_ID, 'webhook_authorized', '["admin"]'));
		if (empty($authorized) || !is_array($authorized)) {
			throw new \Exception($this->l10n->t('You are not allowed to request signing'));
		}
		$userGroups = $this->groupManager->getUserGroupIds($user);
		if (!array_intersect($userGroups, $authorized)) {
			throw new \Exception($this->l10n->t('You are not allowed to request signing'));
		}
	}

	public function iRequestedSignThisFile(IUser $user, int $nodeId) {
		$libresignFile = $this->getLibreSignFile($nodeId);
		if ($libresignFile->getUserId() !== $user->getUID()) {
			throw new \Exception($this->l10n->t('You are not the signer request for this file'));
		}
	}

	public function haveValidMail(array $data) {
		if (empty($data)) {
			throw new \Exception($this->l10n->t('User needs values'));
		}
		if (empty($data['email'])) {
			throw new \Exception($this->l10n->t('Email required'));
		}
		if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			throw new \Exception($this->l10n->t('Invalid email'));
		}
	}

	public function signerWasAssociated(array $signer) {
		$libresignFile = $this->getLibreSignFile();
		if (!$libresignFile) {
			throw new \Exception($this->l10n->t('File not loaded'));
		}
		$signatures = $this->fileUserMapper->getByFileUuid($libresignFile->getUuid());
		$exists = array_filter($signatures, fn ($s) => $s->getEmail() === $signer['email']);
		if (!$exists) {
			throw new \Exception($this->l10n->t('No signature was requested to %s', $signer['email']));
		}
	}

	public function notSigned(array $signer) {
		$libresignFile = $this->getLibreSignFile();
		if (!$libresignFile) {
			throw new \Exception($this->l10n->t('File not loaded'));
		}
		$signatures = $this->fileUserMapper->getByFileUuid($libresignFile->getUuid());
		$exists = array_filter($signatures, fn ($s) => $s->getEmail() === $signer['email']);
		$signed = $exists[0]->getSigned();
		if ($signed) {
			throw new \Exception($this->l10n->t('%s already signed this file', $signer['email']));
		}
	}
}
