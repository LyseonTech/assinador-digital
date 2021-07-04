<?php

namespace OCA\Libresign\Service;

use OCA\Libresign\AppInfo\Application;
use OCA\Libresign\Db\AccountFileMapper;
use OCA\Libresign\Db\FileMapper;
use OCA\Libresign\Db\FileUser;
use OCA\Libresign\Db\FileUserMapper;
use OCA\Libresign\Db\ReportDao;
use OCA\Libresign\Exception\LibresignException;
use OCA\Libresign\Handler\CfsslHandler;
use OCA\Libresign\Handler\Pkcs12Handler;
use OCA\Libresign\Helper\JSActions;
use OCA\Libresign\Helper\ValidateHelper;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use Sabre\DAV\UUIDUtil;
use Throwable;

class AccountService {
	/** @var IL10N */
	private $l10n;
	/** @var FileUserMapper */
	private $fileUserMapper;
	/** @var FileUser */
	private $fileUser;
	/** @var IUserManager */
	protected $userManager;
	/** @var IRootFolder */
	private $root;
	/** @var IConfig */
	private $config;
	/** @var NewUserMailHelper */
	private $newUserMail;
	/** @var ValidateHelper */
	private $validateHelper;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var CfsslHandler */
	private $cfsslHandler;
	/** @var Pkcs12Handler */
	private $pkcs12Handler;
	/** @var FileMapper */
	private $fileMapper;
	/** @var ReportDao */
	private $reportDao;
	/** @var SignFileService */
	private $signFile;
	/** @var \OCA\Libresign\DbFile */
	private $fileData;
	/** @var \OCA\Files\Node\File */
	private $fileToSign;
	/** @var IGroupManager */
	private $groupManager;
	/** @var AccountFileService */
	private $accountFileService;
	/** @var AccountFileMapper */
	private $accountFileMapper;

	public function __construct(
		IL10N $l10n,
		FileUserMapper $fileUserMapper,
		IUserManager $userManager,
		IRootFolder $root,
		FileMapper $fileMapper,
		ReportDao $reportDao,
		SignFileService $signFile,
		IConfig $config,
		NewUserMailHelper $newUserMail,
		ValidateHelper $validateHelper,
		IURLGenerator $urlGenerator,
		CfsslHandler $cfsslHandler,
		Pkcs12Handler $pkcs12Handler,
		IGroupManager $groupManager,
		AccountFileService $accountFileService,
		AccountFileMapper $accountFileMapper
	) {
		$this->l10n = $l10n;
		$this->fileUserMapper = $fileUserMapper;
		$this->userManager = $userManager;
		$this->root = $root;
		$this->fileMapper = $fileMapper;
		$this->reportDao = $reportDao;
		$this->signFile = $signFile;
		$this->config = $config;
		$this->newUserMail = $newUserMail;
		$this->validateHelper = $validateHelper;
		$this->urlGenerator = $urlGenerator;
		$this->cfsslHandler = $cfsslHandler;
		$this->pkcs12Handler = $pkcs12Handler;
		$this->groupManager = $groupManager;
		$this->accountFileService = $accountFileService;
		$this->accountFileMapper = $accountFileMapper;
	}

	public function validateCreateToSign(array $data) {
		if (!UUIDUtil::validateUUID($data['uuid'])) {
			throw new LibresignException($this->l10n->t('Invalid UUID'), 1);
		}
		try {
			$fileUser = $this->getFileUserByUuid($data['uuid']);
		} catch (\Throwable $th) {
			throw new LibresignException($this->l10n->t('UUID not found'), 1);
		}
		if ($fileUser->getEmail() !== $data['email']) {
			throw new LibresignException($this->l10n->t('This is not your file'), 1);
		}
		if ($this->userManager->userExists($data['email'])) {
			throw new LibresignException($this->l10n->t('User already exists'), 1);
		}
		if (empty($data['password'])) {
			throw new LibresignException($this->l10n->t('Password is mandatory'), 1);
		}
		$file = $this->getFileByUuid($data['uuid']);
		if (empty($file['fileToSign'])) {
			throw new LibresignException($this->l10n->t('File not found'));
		}
	}

	public function getFileByUuid(string $uuid) {
		$fileUser = $this->getFileUserByUuid($uuid);
		if (!$this->fileData) {
			$this->fileData = $this->fileMapper->getById($fileUser->getFileId());
			$userId = $this->fileData->getUserId();
			$userFolder = $this->root->getUserFolder($userId);
			$fileToSign = $userFolder->getById($this->fileData->getNodeId());
			if (count($fileToSign)) {
				$this->fileToSign = $fileToSign[0];
			}
		}
		return [
			'fileData' => $this->fileData,
			'fileToSign' => $this->fileToSign
		];
	}

	public function validateCertificateData(array $data) {
		if (!$data['email']) {
			throw new LibresignException($this->l10n->t('You must have an email. You can define the email in your profile.'), 1);
		}
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			throw new LibresignException($this->l10n->t('Invalid email'), 1);
		}
		if (empty($data['signPassword'])) {
			throw new LibresignException($this->l10n->t('Password to sign is mandatory'), 1);
		}
	}

	public function validateAccountFiles(array $files, IUser $user) {
		foreach ($files as $fileIndex => $file) {
			$this->validateAccountFile($fileIndex, $file, $user);
		}
	}

	private function validateAccountFile(int $fileIndex, array $file, IUser $user) {
		$profileFileTypes = json_decode($this->config->getAppValue(Application::APP_ID, 'profile_file_types', '["IDENTIFICATION"]'), true);
		if (!in_array($file['type'], $profileFileTypes)) {
			throw new LibresignException(json_encode([
				'type' => 'danger',
				'file' => $fileIndex,
				'message' => $this->l10n->t('Invalid file type.')
			]));
		}

		try {
			$this->validateHelper->validateNewFile($file);
		} catch (\Exception $e) {
			throw new LibresignException(json_encode([
				'type' => 'danger',
				'file' => $fileIndex,
				'message' => $e->getMessage()
			]));
		}

		try {
			$exists = $this->accountFileMapper->getByUserAndType($user->getUID(), $file['type']);
		} catch (\Exception $e) {
		}
		if (!empty($exists)) {
			throw new LibresignException(json_encode([
				'type' => 'danger',
				'file' => $fileIndex,
				'message' => $this->l10n->t('A file of this type has been associated.')
			]));
		}
	}

	/**
	 * Get fileUser by Uuid
	 *
	 * @param string $uuid
	 * @return FileUser
	 */
	public function getFileUserByUuid($uuid) {
		if (!$this->fileUser) {
			$this->fileUser = $this->fileUserMapper->getByUuid($uuid);
		}
		return $this->fileUser;
	}

	public function createToSign($uuid, $uid, $password, $signPassword) {
		$fileUser = $this->getFileUserByUuid($uuid);

		$newUser = $this->userManager->createUser($uid, $password);
		$newUser->setDisplayName($fileUser->getDisplayName());
		$newUser->setEMailAddress($fileUser->getEmail());

		$fileUser->setUserId($newUser->getUID());
		$this->fileUserMapper->update($fileUser);

		if ($this->config->getAppValue('core', 'newUser.sendEmail', 'yes') === 'yes') {
			try {
				$emailTemplate = $this->newUserMail->generateTemplate($newUser, false);
				$this->newUserMail->sendMail($newUser, $emailTemplate);
			} catch (\Exception $e) {
				throw new LibresignException('Unable to send the invitation', 1);
			}
		}

		$this->generateCertificate($uid, $signPassword, $newUser->getUID());
	}

	/**
	 * Generate certificate
	 *
	 * @param string $email Email
	 * @param string $signPassword Password of signature
	 * @param string $uid User id
	 * @return File
	 */
	public function generateCertificate(string $email, string $signPassword, string $uid): File {
		$content = $this->cfsslHandler
			->setCommonName($this->config->getAppValue(Application::APP_ID, 'commonName'))
			->sethosts([$email])
			->setCountry($this->config->getAppValue(Application::APP_ID, 'country'))
			->setOrganization($this->config->getAppValue(Application::APP_ID, 'organization'))
			->setOrganizationUnit($this->config->getAppValue(Application::APP_ID, 'organizationUnit'))
			->setCfsslUri($this->config->getAppValue(Application::APP_ID, 'cfsslUri'))
			->setPassword($signPassword)
			->generateCertificate();
		if (!$content) {
			throw new LibresignException('Failure on generate certificate', 1);
		}
		return $this->pkcs12Handler->savePfx($uid, $content);
	}

	/**
	 * Undocumented function
	 *
	 * @param string $formatOfPdfOnSign (base64,url,file)
	 * @return array|string
	 */
	public function getConfig(?string $uuid, ?string $userId, string $formatOfPdfOnSign): array {
		$info = $this->getInfoOfFileToSign($uuid, $userId, $formatOfPdfOnSign);
		$info['settings']['hasSignatureFile'] = $this->hasSignatureFile($userId);
		return $info;
	}

	private function getInfoOfFileToSign(?string $uuid, ?string $userId, string $formatOfPdfOnSign): array {
		$return = [];
		try {
			if (!$uuid) {
				return $return;
			}
			$fileUser = $this->fileUserMapper->getByUuid($uuid);
		} catch (\Throwable $th) {
			$return['action'] = JSActions::ACTION_DO_NOTHING;
			$return['errors'][] = $this->l10n->t('Invalid UUID');
			return $return;
		}
		$fileUserId = $fileUser->getUserId();
		if (!$fileUserId) {
			if ($userId) {
				$return['action'] = JSActions::ACTION_DO_NOTHING;
				$return['errors'][] = $this->l10n->t('This is not your file');
				return $return;
			}
			$email = $fileUser->getEmail();
			if ($this->userManager->userExists($email)) {
				$return['action'] = JSActions::ACTION_REDIRECT;
				$return['errors'][] = $this->l10n->t('User already exists. Please login.');
				$return['redirect'] = $this->urlGenerator->linkToRoute('core.login.showLoginForm', [
					'redirect_url' => $this->urlGenerator->linkToRoute(
						'libresign.page.sign',
						['uuid' => $uuid]
					),
				]);
				return $return;
			}
			$return['settings']['accountHash'] = md5($email);
			$return['action'] = JSActions::ACTION_CREATE_USER;
			return $return;
		}
		if ($fileUser->getSigned()) {
			$return['action'] = JSActions::ACTION_SHOW_ERROR;
			$return['errors'][] = $this->l10n->t('File already signed.');
			return $return;
		}
		if (!$userId) {
			$return['action'] = JSActions::ACTION_REDIRECT;

			$return['redirect'] = $this->urlGenerator->linkToRoute('core.login.showLoginForm', [
				'redirect_url' => $this->urlGenerator->linkToRoute(
					'libresign.page.sign',
					['uuid' => $uuid]
				),
			]);
			$return['errors'][] = $this->l10n->t('You are not logged in. Please log in.');
			return $return;
		}
		if ($fileUserId !== $userId) {
			$return['action'] = JSActions::ACTION_DO_NOTHING;
			$return['errors'][] = $this->l10n->t('Invalid user');
			return $return;
		}
		$fileData = $this->fileMapper->getById($fileUser->getFileId());
		$userFolder = $this->root->getUserFolder($fileData->getUserId());
		$fileToSign = $userFolder->getById($fileData->getNodeId());
		if (count($fileToSign) < 1) {
			$return['action'] = JSActions::ACTION_DO_NOTHING;
			$return['errors'][] = $this->l10n->t('File not found');
			return $return;
		}
		/** @var File */
		$fileToSign = $fileToSign[0];
		$return['action'] = JSActions::ACTION_SIGN;
		$return['user']['name'] = $fileUser->getDisplayName();
		switch ($formatOfPdfOnSign) {
			case 'base64':
				$pdf = ['base64' => base64_encode($fileToSign->getContent())];
				break;
			case 'url':
				$pdf = ['url' => $this->urlGenerator->linkToRoute('libresign.page.getPdfUser', ['uuid' => $uuid])];
				break;
			case 'nodeId':
				$pdf = ['nodeId' => $fileToSign->getId()];
				break;
			case 'file':
				$pdf = ['file' => $fileToSign];
				break;
		}
		$return['sign'] = [
			'pdf' => $pdf,
			'filename' => $fileData->getName(),
			'description' => $fileUser->getDescription()
		];
		return $return;
	}

	public function hasSignatureFile(?string $userId = null) {
		if (!$userId) {
			return false;
		}
		try {
			$this->pkcs12Handler->getPfx($userId);
			return true;
		} catch (\Throwable $th) {
		}
		return false;
	}

	/**
	 * Get PDF node by UUID
	 *
	 * @param string $uuid
	 * @throws Throwable
	 * @return \OCP\Files\File
	 */
	public function getPdfByUuid(string $uuid): \OCP\Files\File {
		$fileData = $this->fileMapper->getByUuid($uuid);
		$userFolder = $this->root->getUserFolder($fileData->getUserId());

		$file = $userFolder->getById($fileData->getNodeId())[0];
		$filePath = $file->getPath();

		$fileUser = $this->fileUserMapper->getByFileId($fileData->getId());
		$signedUsers = array_filter($fileUser, function ($row) {
			return !is_null($row->getSigned());
		});
		if (count($fileUser) === count($signedUsers)) {
			$filePath = preg_replace(
				'/' . $file->getExtension() . '$/',
				$this->l10n->t('signed') . '.' . $file->getExtension(),
				$filePath
			);
		}
		// If signed, return signed file
		if ($userFolder->nodeExists($filePath)) {
			/** @var \OCP\Files\File */
			$file = $userFolder->get($filePath);
		}
		return $file;
	}

	public function canRequestSign(?IUser $user = null): bool {
		if (!$user) {
			return false;
		}
		$authorized = json_decode($this->config->getAppValue(Application::APP_ID, 'webhook_authorized', '["admin"]'));
		if (empty($authorized)) {
			return false;
		}
		$userGroups = $this->groupManager->getUserGroupIds($user);
		if (!array_intersect($userGroups, $authorized)) {
			return false;
		}
		return true;
	}

	public function getSettings(?IUser $user = null): array {
		$return['canRequestSign'] = $this->canRequestSign($user);
		$return['hasSignatureFile'] = $this->hasSignatureFile($user->getUID());
		return $return;
	}

	public function list(IUser $user, $page = null, $length = 15) {
		$page = $page ?? 1;
		$length = $length ?? 1;
		$data = $this->reportDao->getFilesAssociatedFilesWithMeFormatted($user->getUID(), $page, $length);
		$data['pagination']->setRootPath('/file/list');
		return [
			'data' => $data['data'],
			'pagination' => $data['pagination']->getPagination($page, $length)
		];
	}

	public function addFilesToAccount($files, $user) {
		$this->validateAccountFiles($files, $user);
		foreach ($files as $fileData) {
			$dataToSave = $fileData;
			$dataToSave['userManager'] = $user;
			$dataToSave['name'] = $fileData['type'];
			$file = $this->signFile->saveFile($dataToSave);

			$this->accountFileService->addFile($file, $user, $fileData['type']);
		}
	}
}
