<?php

namespace OCA\Libresign\Service;

use OCA\Libresign\Db\File as FileEntity;
use OCA\Libresign\Db\FileMapper;
use OCA\Libresign\Db\FileUser as FileUserEntity;
use OCA\Libresign\Db\FileUserMapper;
use OCA\Libresign\Exception\LibresignException;
use OCA\Libresign\Handler\Pkcs7Handler;
use OCA\Libresign\Handler\Pkcs12Handler;
use OCA\Libresign\Helper\ValidateHelper;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IL10N;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\UUIDUtil;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParserException;

class SignFileService {
	/** @var IL10N */
	private $l10n;
	/** @var FileMapper */
	private $fileMapper;
	/** @var FileUserMapper */
	private $fileUserMapper;
	/** @var Pkcs7Handler */
	private $pkcs7Handler;
	/** @var Pkcs12Handler */
	private $pkcs12Handler;
	/** @var FolderService */
	private $folderService;
	/** @var IClientService */
	private $client;
	/** @var IUserManager */
	private $userManager;
	/** @var MailService */
	private $mail;
	/** @var LoggerInterface */
	private $logger;
	/** @var ValidateHelper */
	private $validateHelper;
	/** @var IRootFolder */
	private $root;

	public function __construct(
		IL10N $l10n,
		FileMapper $fileMapper,
		FileUserMapper $fileUserMapper,
		Pkcs7Handler $pkcs7Handler,
		Pkcs12Handler $pkcs12Handler,
		FolderService $folderService,
		IClientService $client,
		IUserManager $userManager,
		MailService $mail,
		LoggerInterface $logger,
		ValidateHelper $validateHelper,
		IRootFolder $root
	) {
		$this->l10n = $l10n;
		$this->fileMapper = $fileMapper;
		$this->fileUserMapper = $fileUserMapper;
		$this->pkcs7Handler = $pkcs7Handler;
		$this->pkcs12Handler = $pkcs12Handler;
		$this->folderService = $folderService;
		$this->client = $client;
		$this->userManager = $userManager;
		$this->mail = $mail;
		$this->logger = $logger;
		$this->validateHelper = $validateHelper;
		$this->root = $root;
	}

	public function save(array $data) {
		if (!empty($data['uuid'])) {
			$file = $this->fileMapper->getByUuid($data['uuid']);
		} elseif (!empty($data['file']['fileId'])) {
			try {
				$file = $this->fileMapper->getByFileId($data['file']['fileId']);
			} catch (\Throwable $th) {
				$file = $this->saveFile($data);
			}
		} else {
			$file = $this->saveFile($data);
		}
		$return['uuid'] = $file->getUuid();
		$return['nodeId'] = $file->getNodeId();
		$return['users'] = $this->associateToUsers($data, $file->getId());
		return $return;
	}

	/**
	 * Save file data
	 *
	 * @param array $data
	 * @return FileEntity
	 */
	public function saveFile(array $data): FileEntity {
		$node = $this->getNodeFromData($data);

		$file = new FileEntity();
		$file->setNodeId($node->getId());
		$file->setUserId($data['userManager']->getUID());
		$file->setUuid(UUIDUtil::getUUID());
		$file->setCreatedAt(time());
		$file->setName($data['name']);
		if (!empty($data['callback'])) {
			$file->setCallback($data['callback']);
		}
		$file->setEnabled(1);
		$this->fileMapper->insert($file);
		return $file;
	}

	public function saveFileUser(FileUserEntity $fileUser) {
		if ($fileUser->getId()) {
			$this->fileUserMapper->update($fileUser);
			$this->mail->notifySignDataUpdated($fileUser);
		} else {
			$this->fileUserMapper->insert($fileUser);
			$this->mail->notifyUnsignedUser($fileUser);
		}
	}

	private function associateToUsers(array $data, int $fileId): array {
		$return = [];
		if (!empty($data['users'])) {
			foreach ($data['users'] as $user) {
				$user['email'] = strtolower($user['email']);
				$fileUser = $this->getFileUser($user['email'], $fileId);
				$this->setDataToUser($fileUser, $user, $fileId);
				$this->saveFileUser($fileUser);
				$return[] = $fileUser;
			}
		}
		return $return;
	}

	private function getFileUser(string $email, int $fileId): FileUserEntity {
		try {
			$fileUser = $this->fileUserMapper->getByEmailAndFileId($email, $fileId);
		} catch (\Throwable $th) {
			$fileUser = new FileUserEntity();
		}
		return $fileUser;
	}

	private function getNodeFromData(array $data): \OCP\Files\Node {
		if (!$this->folderService->getUserId()) {
			$this->folderService->setUserId($data['userManager']->getUID());
		}
		if (isset($data['file']['fileId'])) {
			$userFolder = $this->folderService->getFolder($data['file']['fileId']);
			return $userFolder->getById($data['file']['fileId'])[0];
		}
		$userFolder = $this->folderService->getFolder();
		$folderName = $this->getFolderName($data);
		if ($userFolder->nodeExists($folderName)) {
			throw new \Exception($this->l10n->t('File already exists'));
		}
		$folderToFile = $userFolder->newFolder($folderName);
		return $folderToFile->newFile($data['name'] . '.pdf', $this->getFileRaw($data));
	}

	private function getFileRaw($data) {
		if (!empty($data['file']['url'])) {
			if (!filter_var($data['file']['url'], FILTER_VALIDATE_URL)) {
				throw new \Exception($this->l10n->t('Invalid URL file'));
			}
			$response = $this->client->newClient()->get($data['file']['url']);
			$contentType = $response->getHeader('Content-Type');
			if ($contentType !== 'application/pdf') {
				throw new \Exception($this->l10n->t('The URL should be a PDF.'));
			}
			$content = $response->getBody();
			if (!$content) {
				throw new \Exception($this->l10n->t('Empty file'));
			}
		} else {
			$content = base64_decode($data['file']['base64']);
		}
		$this->validatePdfStringWithFpdi($content);
		return $content;
	}

	/**
	 * Validates a PDF. Triggers error if invalid.
	 *
	 * @param string $string
	 *
	 * @throws Type\PdfTypeException
	 * @throws CrossReferenceException
	 * @throws PdfParserException
	 */
	private function validatePdfStringWithFpdi($string) {
		$pdf = new Fpdi();
		try {
			$stream = fopen('php://memory','r+');
			fwrite($stream, $string);
			rewind($stream);
			$pdf->setSourceFile($stream);
		} catch (\Throwable $th) {
			$this->logger->error($th->getMessage());
			throw new \Exception($this->l10n->t('Invalid PDF'));
		}
	}

	private function getFolderName(array $data) {
		if (!isset($data['settings']['folderPatterns'])) {
			$data['settings']['separator'] = '_';
			$data['settings']['folderPatterns'][] = [
				'name' => 'date',
				'setting' => 'Y-m-d\TH:i:s'
			];
			$data['settings']['folderPatterns'][] = [
				'name' => 'name'
			];
			$data['settings']['folderPatterns'][] = [
				'name' => 'userId'
			];
		}
		foreach ($data['settings']['folderPatterns'] as $pattern) {
			switch ($pattern['name']) {
				case 'date':
					$folderName[] = (new \DateTime('NOW'))->format($pattern['setting']);
					break;
				case 'name':
					if (!empty($data['name'])) {
						$folderName[] = $data['name'];
					}
					break;
				case 'userId':
					$folderName[] = $data['userManager']->getUID();
					break;
			}
		}
		return implode($data['settings']['separator'], $folderName);
	}

	private function setDataToUser(FileUserEntity $fileUser, array $user, $fileId) {
		$fileUser->setFileId($fileId);
		if (!$fileUser->getUuid()) {
			$fileUser->setUuid(UUIDUtil::getUUID());
		}
		$fileUser->setEmail($user['email']);
		if (!empty($user['description']) && $fileUser->getDescription() !== $user['description']) {
			$fileUser->setDescription($user['description']);
		}
		if (empty($user['user_id'])) {
			$userToSign = $this->userManager->getByEmail($user['email']);
			if ($userToSign) {
				$fileUser->setUserId($userToSign[0]->getUID());
				if (empty($user['displayName'])) {
					$user['displayName'] = $userToSign[0]->getDisplayName();
				}
			}
		}
		if (!empty($user['displayName'])) {
			$fileUser->setDisplayName($user['displayName']);
		}
		if (!$fileUser->getId()) {
			$fileUser->setCreatedAt(time());
		}
	}

	public function validate(array $data) {
		$this->validateUserManager($data);
		$this->validateNewFile($data);
		$this->validateUsers($data);
	}

	public function validateUserManager(array $user) {
		if (!isset($user['userManager'])) {
			throw new \Exception($this->l10n->t('You are not allowed to request signing'), Http::STATUS_UNPROCESSABLE_ENTITY);
		}
		$this->validateHelper->canRequestSign($user['userManager']);
	}

	public function validateNewFile(array $data) {
		if (empty($data['name'])) {
			throw new \Exception($this->l10n->t('Name is mandatory'));
		}
		$this->validateHelper->validateNewFile($data);
	}

	public function validateExistingFile(array $data) {
		if (isset($data['uuid'])) {
			$this->validateHelper->validateFileUuid($data);
			$file = $this->fileMapper->getByUuid($data['uuid']);
			$this->validateHelper->iRequestedSignThisFile($data['userManager'], $file->getNodeId());
		} elseif (isset($data['file'])) {
			if (!isset($data['file']['fileId'])) {
				throw new \Exception($this->l10n->t('Invalid fileID'));
			}
			$this->validateHelper->validateLibreSignNodeId($data['file']['fileId']);
			$this->validateHelper->iRequestedSignThisFile($data['userManager'], $data['file']['fileId']);
		} else {
			throw new \Exception($this->l10n->t('Inform or UUID or a File object'));
		}
	}

	public function validateUsers(array $data) {
		if (empty($data['users'])) {
			throw new \Exception($this->l10n->t('Empty users list'));
		}
		if (!is_array($data['users'])) {
			throw new \Exception($this->l10n->t('User list needs to be an array'));
		}
		$emails = [];
		foreach ($data['users'] as $index => $user) {
			$this->validateHelper->haveValidMail($user);
			$emails[$index] = strtolower($user['email']);
		}
		$uniques = array_unique($emails);
		if (count($emails) > count($uniques)) {
			throw new \Exception($this->l10n->t('Remove duplicated users, email address need to be unique'));
		}
	}

	/**
	 * Can delete sing request
	 *
	 * @param array $data
	 */
	public function canDeleteSignRequest(array $data) {
		if (!empty($data['uuid'])) {
			$signatures = $this->fileUserMapper->getByFileUuid($data['uuid']);
		} elseif (!empty($data['file']['fileId'])) {
			$signatures = $this->fileUserMapper->getByNodeId($data['file']['fileId']);
		}
		$signed = array_filter($signatures, fn ($s) => $s->getSigned());
		if ($signed) {
			throw new \Exception($this->l10n->t('Document already signed'));
		}
		array_walk($data['users'], function ($user) use ($signatures) {
			$exists = array_filter($signatures, fn ($s) => $s->getEmail() === $user['email']);
			if (!$exists) {
				throw new \Exception($this->l10n->t('No signature was requested to %s', $user['email']));
			}
		});
	}

	/**
	 * @deprecated 2.4.0
	 * @param array $data
	 * @return array
	 */
	public function deleteSignRequestDeprecated(array $data): array {
		$this->validateHelper->validateFileUuid($data);
		$this->validateUsers($data);
		$this->canDeleteSignRequest($data);

		if (!empty($data['uuid'])) {
			$signatures = $this->fileUserMapper->getByFileUuid($data['uuid']);
			$fileData = $this->fileMapper->getByUuid($data['uuid']);
		} elseif (!empty($data['file']['fileId'])) {
			$signatures = $this->fileUserMapper->getByNodeId($data['file']['fileId']);
			$fileData = $this->fileMapper->getByFileId($data['file']['fileId']);
		}

		$deletedUsers = [];
		foreach ($data['users'] as $key => $signer) {
			try {
				$fileUser = $this->fileUserMapper->getByEmailAndFileId(
					$signer['email'],
					$fileData->getId()
				);
				$deletedUsers[] = $fileUser;
				$this->fileUserMapper->delete($fileUser);
			} catch (\Throwable $th) {
				// already deleted
			}
		}
		if ((empty($data['users']) && !count($signatures)) || count($signatures) === count($data['users'])) {
			$this->fileMapper->delete($fileData);
		}
		return $deletedUsers;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function deleteSignRequest(array $data): void {
		if (!empty($data['uuid'])) {
			$signatures = $this->fileUserMapper->getByFileUuid($data['uuid']);
			$fileData = $this->fileMapper->getByUuid($data['uuid']);
		} elseif (!empty($data['file']['fileId'])) {
			$signatures = $this->fileUserMapper->getByNodeId($data['file']['fileId']);
			$fileData = $this->fileMapper->getByFileId($data['file']['fileId']);
		}
		foreach ($signatures as $fileUser) {
			$this->fileUserMapper->delete($fileUser);
		}
		$this->fileMapper->delete($fileData);
	}

	public function unassociateToUser(int $fileId, int $signatureId) {
		$fileUser = $this->fileUserMapper->getByFileIdAndFileUserId($fileId, $signatureId);
		$this->fileUserMapper->delete($fileUser);
	}

	public function notifyCallback(string $uri, string $uuid, File $file): IResponse {
		$options = [
			'multipart' => [
				[
					'name' => 'uuid',
					'contents' => $uuid
				],
				[
					'name' => 'file',
					'contents' => $file->fopen('r'),
					'filename' => $file->getName()
				]
			]
		];
		return $this->client->newClient()->post($uri, $options);
	}

	public function sign(FileEntity $libreSignFile, FileUserEntity $fileUser, string $password): \OCP\Files\File {
		$fileToSign = $this->getFileToSing($libreSignFile);
		$pfxFile = $this->pkcs12Handler->getPfx($fileUser->getUserId());
		switch ($fileToSign->getExtension()) {
			case 'pdf':
				$signedFile = $this->pkcs12Handler->sign($fileToSign, $pfxFile, $password);
				break;
			default:
				$signedFile = $this->pkcs7Handler->sign($fileToSign, $pfxFile, $password);
		}

		$fileUser->setSigned(time());
		$this->fileUserMapper->update($fileUser);

		return $signedFile;
	}

	/**
	 * Get file to sign
	 *
	 * @throws LibresignException
	 * @param FileEntity $fileData
	 * @return \OCP\Files\File
	 */
	public function getFileToSing(FileEntity $fileData): \OCP\Files\File {
		$userFolder = $this->root->getUserFolder($fileData->getUserId());
		$originalFile = $userFolder->getById($fileData->getNodeId());
		if (count($originalFile) < 1) {
			throw new LibresignException($this->l10n->t('File not found'));
		}
		$originalFile = $originalFile[0];
		if ($originalFile->getExtension() === 'pdf') {
			return $this->getPdfToSign($fileData, $originalFile);
		}
		return $userFolder->get($originalFile);
	}

	private function getPdfToSign(FileEntity $fileData, File $originalFile): \OCP\Files\File {
		$signedFilePath = preg_replace(
			'/' . $originalFile->getExtension() . '$/',
			$this->l10n->t('signed') . '.' . $originalFile->getExtension(),
			$originalFile->getPath()
		);

		if ($this->root->nodeExists($signedFilePath)) {
			/** @var \OCP\Files\File */
			$fileToSign = $this->root->get($signedFilePath);
		} else {
			/** @var \OCP\Files\File */
			$buffer = $this->pkcs12Handler->writeFooter($originalFile, $fileData->getUuid());
			if (!$buffer) {
				$buffer = $originalFile->getContent($originalFile);
			}
			$fileToSign = $this->root->newFile($signedFilePath);
			$fileToSign->putContent($buffer);
		}
		return $fileToSign;
	}
}
