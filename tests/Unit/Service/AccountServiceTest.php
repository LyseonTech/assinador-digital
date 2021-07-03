<?php

namespace OCA\Libresign\Tests\Unit\Service;

use OCA\Libresign\Db\AccountFileMapper;
use OCA\Libresign\Db\FileMapper;
use OCA\Libresign\Db\FileUser;
use OCA\Libresign\Db\FileUserMapper;
use OCA\Libresign\Db\ReportDao;
use OCA\Libresign\Handler\CfsslHandler;
use OCA\Libresign\Handler\Pkcs12Handler;
use OCA\Libresign\Helper\JSActions;
use OCA\Libresign\Helper\ValidateHelper;
use OCA\Libresign\Service\AccountFileService;
use OCA\Libresign\Service\AccountService;
use OCA\Libresign\Service\SignFileService;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;

/**
 * @internal
 * @group DB
 */
final class AccountServiceTest extends \OCA\Libresign\Tests\Unit\TestCase {
	/** @var IL10N */
	private $l10n;
	/** @var FileUserMapper */
	private $fileUserMapper;
	/** @var IUserManager */
	private $userManagerInstance;
	/** @var IRootFolder */
	private $root;
	/** @var FileMapper */
	private $fileMapper;
	/** @var ReportDao */
	private $reportDao;
	/** @var SignFileService */
	private $signFile;
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
	/** @var AccountService */
	private $accountService;
	/** @var IGroupManager */
	private $groupManager;
	/** @var AccountFileService */
	private $accountFile;

	public function setUp(): void {
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n
			->method('t')
			->will($this->returnArgument(0));
		$this->fileUserMapper = $this->createMock(FileUserMapper::class);
		$this->userManagerInstance = $this->createMock(IUserManager::class);
		$this->root = $this->createMock(IRootFolder::class);
		$this->fileMapper = $this->createMock(FileMapper::class);
		$this->reportDao = $this->createMock(ReportDao::class);
		$this->signFile = $this->createMock(SignFileService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->newUserMail = $this->createMock(NewUserMailHelper::class);
		$this->validateHelper = \OC::$server->get(\OCA\Libresign\Helper\ValidateHelper::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->cfsslHandler = $this->createMock(CfsslHandler::class);
		$this->pkcs12Handler = $this->createMock(Pkcs12Handler::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->accountFileService = $this->createMock(AccountFileService::class);
		$this->accountFileMapper = $this->createMock(AccountFileMapper::class);

		$this->accountService = new AccountService(
			$this->l10n,
			$this->fileUserMapper,
			$this->userManagerInstance,
			$this->root,
			$this->fileMapper,
			$this->reportDao,
			$this->signFile,
			$this->config,
			$this->newUserMail,
			$this->validateHelper,
			$this->urlGenerator,
			$this->cfsslHandler,
			$this->pkcs12Handler,
			$this->groupManager,
			$this->accountFileService,
			$this->accountFileMapper
		);
	}

	/**
	 * @dataProvider providerTestValidateCreateToSignUsingDataProvider
	 */
	public function testValidateCreateToSignUsingDataProvider($arguments, $expectedErrorMessage) {
		if (is_callable($arguments)) {
			$arguments = $arguments($this);
		}

		$this->accountService = new AccountService(
			$this->l10n,
			$this->fileUserMapper,
			$this->userManagerInstance,
			$this->root,
			$this->fileMapper,
			$this->reportDao,
			$this->signFile,
			$this->config,
			$this->newUserMail,
			$this->validateHelper,
			$this->urlGenerator,
			$this->cfsslHandler,
			$this->pkcs12Handler,
			$this->groupManager,
			$this->accountFileService,
			$this->accountFileMapper
		);
		$this->expectExceptionMessage($expectedErrorMessage);
		$this->accountService->validateCreateToSign($arguments);
	}

	public function providerTestValidateCreateToSignUsingDataProvider() {
		return [
			[ #0
				[
					'uuid' => 'invalid uuid'
				],
				'Invalid UUID'
			],
			[ #1
				function ($self) {
					$uuid = '12345678-1234-1234-1234-123456789012';
					$self->fileUserMapper = $self->createMock(FileUserMapper::class);
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnCallback(function () {
							throw new \Exception("Beep, beep, not found!", 1);
						}));
					return [
						'uuid' => $uuid
					];
				},
				'UUID not found'
			],
			[ #2
				function ($self) {
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->with($self->equalTo('getEmail'), $self->anything())
						->will($self->returnValue('valid@test.coop'));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					return [
						'uuid' => '12345678-1234-1234-1234-123456789012',
						'email' => 'invalid@test.coop',
						'signPassword' => '132456789'
					];
				},
				'This is not your file'
			],
			[ #3
				function ($self) {
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->with($self->equalTo('getEmail'), $self->anything())
						->will($self->returnValue('valid@test.coop'));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					$self->userManagerInstance
						->method('userExists')
						->will($self->returnValue(true));
					return [
						'uuid' => '12345678-1234-1234-1234-123456789012',
						'email' => 'valid@test.coop',
						'signPassword' => '123456789'
					];
				},
				'User already exists'
			],
			[ #4
				function ($self) {
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->with($self->equalTo('getEmail'), $self->anything())
						->will($self->returnValue('valid@test.coop'));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					return [
						'uuid' => '12345678-1234-1234-1234-123456789012',
						'email' => 'valid@test.coop',
						'signPassword' => '132456789',
						'password' => ''
					];
				},
				'Password is mandatory'
			],
			[ #5
				function ($self) {
					$fileUser = $this->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$this->equalTo('getEmail')],
							[$this->equalTo('getFileId')],
							[$this->equalTo('getUserId')],
						)
						->will($this->returnValueMap([
							['getEmail', [], 'valid@test.coop'],
							['getFileId', [], 171],
							['getUserId', [], 'username'],
						]));
					$self->fileMapper
						->method('getById')
						->will($self->returnValue($fileUser));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));

					$self->root
						->method('getById')
						->will($self->returnValue([]));
					$folder = $this->createMock(\OCP\Files\Folder::class);
					$folder
						->method('getById')
						->willReturn([]);
					$self->root
						->method('getUserFolder')
						->willReturn($folder);
					return [
						'uuid' => '12345678-1234-1234-1234-123456789012',
						'email' => 'valid@test.coop',
						'signPassword' => '132456789',
						'password' => '123456789'
					];
				},
				'File not found'
			],
		];
	}

	/**
	 * @dataProvider providerTestValidateCertificateDataUsingDataProvider
	 */
	public function testValidateCertificateDataUsingDataProvider($arguments, $expectedErrorMessage) {
		if (is_callable($arguments)) {
			$arguments = $arguments($this);
		}

		$this->accountService = new AccountService(
			$this->l10n,
			$this->fileUserMapper,
			$this->userManagerInstance,
			$this->root,
			$this->fileMapper,
			$this->reportDao,
			$this->signFile,
			$this->config,
			$this->newUserMail,
			$this->validateHelper,
			$this->urlGenerator,
			$this->cfsslHandler,
			$this->pkcs12Handler,
			$this->groupManager,
			$this->accountFileService,
			$this->accountFileMapper
		);
		$this->expectExceptionMessage($expectedErrorMessage);
		$this->accountService->validateCertificateData($arguments);
	}

	public function providerTestValidateCertificateDataUsingDataProvider() {
		return [
			[
				[
					'uuid' => '12345678-1234-1234-1234-123456789012',
					'email' => ''
				],
				'You must have an email. You can define the email in your profile.'
			],
			[
				[
					'uuid' => '12345678-1234-1234-1234-123456789012',
					'email' => 'invalid'
				],
				'Invalid email'
			],
			[
				function ($self) {
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->with($self->equalTo('getEmail'), $self->anything())
						->will($self->returnValue('valid@test.coop'));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					return [
						'uuid' => '12345678-1234-1234-1234-123456789012',
						'email' => 'valid@test.coop',
						'password' => '123456789',
						'signPassword' => '',
					];
				},
				'Password to sign is mandatory'
			]
		];
	}

	public function testValidateCertificateDataWithSuccess() {
		$fileUser = $this->createMock(FileUser::class);
		$fileUser
			->method('__call')
			->with($this->equalTo('getEmail'), $this->anything())
			->will($this->returnValue('valid@test.coop'));
		$this->fileUserMapper
			->method('getByUuid')
			->will($this->returnValue($fileUser));
		$actual = $this->accountService->validateCertificateData([
			'uuid' => '12345678-1234-1234-1234-123456789012',
			'email' => 'valid@test.coop',
			'password' => '123456789',
			'signPassword' => '123456',
		]);
		$this->assertNull($actual);
	}

	private function mockValidateWithSuccess() {
		$backend = $this->createMock(\OC\User\Database::class);
		$backend->method('implementsActions')
			->willReturn(true);
		$backend->method('userExists')
			->willReturn(true);
		$backend->method('getRealUID')
			->willReturn('userId');
		$userManager = \OC::$server->getUserManager();
		$userManager->clearBackends();
		$userManager->registerBackend($backend);

		$fileUser = $this->createMock(FileUser::class);
		$fileUser
			->method('__call')
			->withConsecutive(
				[$this->equalTo('getEmail')],
				[$this->equalTo('getFileId')],
				[$this->equalTo('getUserId')],
				[$this->equalTo('getNodeId')],
			)
			->will($this->returnValueMap([
				['getEmail', [], 'valid@test.coop'],
				['getFileId', [], 171],
				['getUserId', [], 'username'],
				['getNodeId', [], 171],
			]));
		$this->fileMapper
			->method('getById')
			->will($this->returnValue($fileUser));
		$this->fileUserMapper
			->method('getByUuid')
			->will($this->returnValue($fileUser));

		$this->root
			->method('getById')
			->will($this->returnValue(['fileToSign']));
		$file = $this->createMock(\OCP\Files\File::class);
		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder
			->method('getById')
			->willReturn([$file]);
		$this->root
			->method('getUserFolder')
			->willReturn($folder);
	}

	public function testValidateCreateToSignSuccess() {
		$this->mockValidateWithSuccess();

		$this->accountService = new AccountService(
			$this->l10n,
			$this->fileUserMapper,
			$this->userManagerInstance,
			$this->root,
			$this->fileMapper,
			$this->reportDao,
			$this->signFile,
			$this->config,
			$this->newUserMail,
			$this->validateHelper,
			$this->urlGenerator,
			$this->cfsslHandler,
			$this->pkcs12Handler,
			$this->groupManager,
			$this->accountFileService,
			$this->accountFileMapper
		);
		$actual = $this->accountService->validateCreateToSign([
			'uuid' => '12345678-1234-1234-1234-123456789012',
			'email' => 'valid@test.coop',
			'password' => '123456789',
			'signPassword' => '123456789',
		]);
		$this->assertNull($actual);
	}

	public function testGenerateCertificateWithInvalidData() {
		$this->cfsslHandler
			->method('__call')
			->will($this->returnValue($this->cfsslHandler));
		$this->expectErrorMessage('Failure on generate certificate');
		$this->accountService->generateCertificate('uid', 'password', 'username');
	}

	public function testGenerateCertificateAndSuccessfullySavedToAnExistingFile() {
		$backend = $this->createMock(\OC\User\Database::class);
		$backend->method('implementsActions')
			->willReturn(true);
		$backend->method('userExists')
			->willReturn(true);
		$backend->method('getRealUID')
			->willReturn('userId');
		$userManager = \OC::$server->getUserManager();
		$userManager->clearBackends();
		$userManager->registerBackend($backend);

		$this->cfsslHandler
			->method('__call')
			->will($this->returnValue($this->cfsslHandler));
		$this->cfsslHandler
			->method('generateCertificate')
			->will($this->returnValue('raw content of pfx file'));
		$actual = $this->accountService->generateCertificate('uid', 'password', 'username');
		$this->assertInstanceOf('\OCP\Files\File', $actual);
	}

	public function testGenerateCertificateAndSuccessfullySavedToANewFile() {
		$backend = $this->createMock(\OC\User\Database::class);
		$backend->method('implementsActions')
			->willReturn(true);
		$backend->method('userExists')
			->willReturn(true);
		$backend->method('getRealUID')
			->willReturn('userId');
		$userManager = \OC::$server->getUserManager();
		$userManager->clearBackends();
		$userManager->registerBackend($backend);

		$this->cfsslHandler
			->method('__call')
			->will($this->returnValue($this->cfsslHandler));
		$this->cfsslHandler
			->method('generateCertificate')
			->will($this->returnValue('raw content of pfx file'));
		$actual = $this->accountService->generateCertificate('uid', 'password', 'username');
		$this->assertInstanceOf('\OCP\Files\File', $actual);
	}

	public function testCreateToSignWithErrorInSendingEmail() {
		$fileUser = $this->createMock(\OCA\Libresign\Db\FileUser::class);
		$this->fileUserMapper->method('getByUuid')->will($this->returnValue($fileUser));
		$userToSign = $this->createMock(\OCP\IUser::class);
		$this->userManagerInstance->method('createUser')->will($this->returnValue($userToSign));
		$this->config->method('getAppValue')->will($this->returnValue('yes'));
		$template = $this->createMock(\OCP\Mail\IEMailTemplate::class);
		$this->newUserMail->method('generateTemplate')->will($this->returnValue($template));
		$this->newUserMail->method('sendMail')->will($this->returnCallback(function () {
			throw new \Exception("Error Processing Request", 1);
		}));
		$this->expectErrorMessage('Unable to send the invitation');
		$this->accountService->createToSign('uuid', 'username', 'passwordOfUser', 'passwordToSign');
	}

	public function testCreateToSignSuccess() {
		$fileUser = $this->createMock(\OCA\Libresign\Db\FileUser::class);
		$this->fileUserMapper->method('getByUuid')->will($this->returnValue($fileUser));
		$userToSign = $this->createMock(\OCP\IUser::class);
		$userToSign->method('getUID')->will($this->returnValue('userToSignUid'));
		$this->userManagerInstance->method('createUser')->will($this->returnValue($userToSign));
		$this->config->method('getAppValue')->will($this->returnValue('no'));

		$backend = $this->createMock(\OC\User\Database::class);
		$backend->method('implementsActions')
			->willReturn(true);
		$backend->method('userExists')
			->willReturn(true);
		$backend->method('getRealUID')
			->willReturn('userId');
		$userManager = \OC::$server->getUserManager();
		$userManager->clearBackends();
		$userManager->registerBackend($backend);

		$this->cfsslHandler
			->method('__call')
			->will($this->returnValue($this->cfsslHandler));
		$this->cfsslHandler
			->method('generateCertificate')
			->will($this->returnValue('raw content of pfx file'));

		$actual = $this->accountService->createToSign('uuid', 'username', 'passwordOfUser', 'passwordToSign');
		$this->assertNull($actual);
	}

	/**
	 * @dataProvider providerTestGetConfigWithInvalidUuid
	 */
	public function testGetConfigWithInvalidUuid($uuid, $userId, $formatOfPdfOnSign, $expected, $setUp) {
		if (is_callable($setUp)) {
			$setUp($this);
		}
		$this->accountService = new AccountService(
			$this->l10n,
			$this->fileUserMapper,
			$this->userManagerInstance,
			$this->root,
			$this->fileMapper,
			$this->reportDao,
			$this->signFile,
			$this->config,
			$this->newUserMail,
			$this->validateHelper,
			$this->urlGenerator,
			$this->cfsslHandler,
			$this->pkcs12Handler,
			$this->groupManager,
			$this->accountFileService,
			$this->accountFileMapper
		);
		$actual = $this->accountService->getConfig($uuid, $userId, $formatOfPdfOnSign);
		$actual = json_encode($actual);
		$this->assertJsonStringEqualsJsonString(
			$actual,
			json_encode($expected)
		);
	}

	public function providerTestGetConfigWithInvalidUuid() {
		return [
			[ #0
				null, null, 'filetype',
				[
					'settings' => [
						'hasSignatureFile' => false
					]
				], null
			],
			[ #1
				'uuid', 'userid', 'filetype',
				[
					'action' => JSActions::ACTION_DO_NOTHING,
					'errors' => [
						'This is not your file'
					],
					'settings' => [
						'hasSignatureFile' => true
					]
				], null
			],
			[ #2
				'uuid', null, 'filetype',
				[
					'action' => JSActions::ACTION_CREATE_USER,
					'settings' => [
						'hasSignatureFile' => false,
						'accountHash' => md5('valid@test.coop')
					]
				], function ($self) {
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$this->equalTo('getUserId')],
							[$this->equalTo('getEmail')]
						)
						->will($this->returnValueMap([
							['getUserId', [], null],
							['getEmail', [], 'valid@test.coop']
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
				}
			],
			[ #3
				'uuid', 'username', 'filetype',
				[
					'action' => JSActions::ACTION_DO_NOTHING,
					'errors' => [
						'This is not your file'
					],
					'settings' => [
						'hasSignatureFile' => true
					]
				], function ($self) {
					$self->createUser('username', 'password');
				}
			],
			[ #4
				'uuid', null, 'filetype',
				[
					'action' => JSActions::ACTION_DO_NOTHING,
					'errors' => [
						'Invalid UUID'
					],
					'settings' => [
						'hasSignatureFile' => false
					]
				], function ($self) {
					$self->fileUserMapper
						->method('getByUuid')
						->will($this->returnCallback(function () {
							throw new \Exception("Beep, beep, not found!", 1);
						}));
				}
			],
			[ #5
				'uuid', null, 'filetype',
				[
					'action' => JSActions::ACTION_REDIRECT,
					'errors' => [
						'User already exists. Please login.'
					],
					'redirect' => '',
					'settings' => [
						'hasSignatureFile' => false
					]
				], function ($self) {
					$user = $self->createUser('username', 'password');
					$user->setEMailAddress('valid@test.coop');

					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$this->equalTo('getUserId')],
							[$this->equalTo('getEmail')]
						)
						->will($this->returnValueMap([
							['getUserId', [], null],
							['getEmail', [], 'valid@test.coop']
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					$self->userManagerInstance
						->method('userExists')
						->willReturn(true);
				}
			],
			[ #6
				'uuid', null, 'filetype',
				[
					'action' => JSActions::ACTION_SHOW_ERROR,
					'errors' => [
						'File already signed.'
					],
					'settings' => [
						'hasSignatureFile' => false
					]
				], function ($self) {
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$this->equalTo('getUserId')],
							[$this->equalTo('getSigned')]
						)
						->will($this->returnValueMap([
							['getUserId', [], 'fileuser'],
							['getSigned', [], true]
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
				}
			],
			[ #7
				'uuid', null, 'filetype',
				[
					'action' => JSActions::ACTION_REDIRECT,
					'errors' => [
						'You are not logged in. Please log in.'
					],
					'redirect' => '',
					'settings' => [
						'hasSignatureFile' => false
					]
				], function ($self) {
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$this->equalTo('getUserId')],
							[$this->equalTo('getSigned')]
						)
						->will($this->returnValueMap([
							['getUserId', [], 'fileuser'],
							['getSigned', [], false]
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
				}
			],
			[ #8
				'uuid', 'username', 'filetype',
				[
					'action' => JSActions::ACTION_DO_NOTHING,
					'errors' => [
						'Invalid user'
					],
					'settings' => [
						'hasSignatureFile' => true
					]
				], function ($self) {
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$this->equalTo('getUserId')],
							[$this->equalTo('getSigned')]
						)
						->will($this->returnValueMap([
							['getUserId', [], 'fileuser'],
							['getSigned', [], false]
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
				}
			],
			[ #9
				'uuid', 'username', 'filetype',
				[
					'action' => JSActions::ACTION_DO_NOTHING,
					'errors' => [
						'File not found'
					],
					'settings' => [
						'hasSignatureFile' => true
					]
				], function ($self) {
					$self->createUser('username', 'password');
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$self->equalTo('getUserId')],
							[$self->equalTo('getSigned')]
						)
						->will($self->returnValueMap([
							['getUserId', [], 'username'],
							['getSigned', [], false]
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					$self->fileMapper
						->method('getByUuid')
						->willReturn($fileUser);
					$self->fileMapper
						->method('getById')
						->willReturn($fileUser);
					$folder = $self->createMock(\OCP\Files\Folder::class);
					$folder
						->method('getById')
						->willReturn([]);
					$self->root
						->method('getUserFolder')
						->willReturn($folder);
				}
			],
			[ #10
				null, 'username', 'filetype',
				[
					'settings' => [
						'hasSignatureFile' => true
					]
				], function ($self) {
					$self->createUser('username', 'password');
				}
			],
			[ #11
				'uuid', 'username', 'base64',
				[
					'action' => JSActions::ACTION_SIGN,
					'sign' => [
						'pdf' => [
							'base64' => base64_encode('content')
						],
						'filename' => null,
						'description' => null
					],
					'user' => [
						'name' => null
					],
					'settings' => [
						'hasSignatureFile' => true
					]
				], function ($self) {
					$self->createUser('username', 'password');
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$self->equalTo('getUserId')],
							[$self->equalTo('getSigned')]
						)
						->will($self->returnValueMap([
							['getUserId', [], 'username'],
							['getSigned', [], false]
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					$self->fileMapper
						->method('getByUuid')
						->willReturn($fileUser);
					$self->fileMapper
						->method('getById')
						->willReturn($fileUser);
					$node = $self->createMock(\OCP\Files\File::class);
					$node->method('getContent')->will($self->returnValue('content'));
					$folder = $self->createMock(\OCP\Files\Folder::class);
					$folder
						->method('getById')
						->willReturn([$node]);
					$self->root
						->method('getUserFolder')
						->willReturn($folder);
				}
			],
			[ #12
				'uuid', 'username', 'url',
				[
					'action' => JSActions::ACTION_SIGN,
					'sign' => [
						'pdf' => [
							'url' => ''
						],
						'filename' => null,
						'description' => null
					],
					'user' => [
						'name' => null
					],
					'settings' => [
						'hasSignatureFile' => true
					]
				], function ($self) {
					$self->createUser('username', 'password');
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$self->equalTo('getUserId')],
							[$self->equalTo('getSigned')]
						)
						->will($self->returnValueMap([
							['getUserId', [], 'username'],
							['getSigned', [], false]
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					$self->fileMapper
						->method('getByUuid')
						->willReturn($fileUser);
					$self->fileMapper
						->method('getById')
						->willReturn($fileUser);
					$node = $self->createMock(\OCP\Files\File::class);
					$folder = $self->createMock(\OCP\Files\Folder::class);
					$folder
						->method('getById')
						->willReturn([$node]);
					$self->root
						->method('getUserFolder')
						->willReturn($folder);
				}
			],
			[ #13
				'uuid', 'username', 'file',
				[
					'action' => JSActions::ACTION_SIGN,
					'sign' => [
						'pdf' => [
							'file' => new \stdClass()
						],
						'filename' => null,
						'description' => null
					],
					'user' => [
						'name' => null
					],
					'settings' => [
						'hasSignatureFile' => true
					]
				], function ($self) {
					$self->createUser('username', 'password');
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$self->equalTo('getUserId')],
							[$self->equalTo('getSigned')]
						)
						->will($self->returnValueMap([
							['getUserId', [], 'username'],
							['getSigned', [], false]
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					$self->fileMapper
						->method('getByUuid')
						->willReturn($fileUser);
					$self->fileMapper
						->method('getById')
						->willReturn($fileUser);
					$node = $self->createMock(\OCP\Files\File::class);
					$node->method('getId')->will($self->returnValue(171));
					$folder = $self->createMock(\OCP\Files\Folder::class);
					$folder
						->method('getById')
						->willReturn([$node]);
					$self->root
						->method('getUserFolder')
						->willReturn($folder);
				}
			],
			[ #14
				'uuid', 'username', 'nodeId',
				[
					'action' => JSActions::ACTION_SIGN,
					'sign' => [
						'pdf' => [
							'nodeId' => null
						],
						'filename' => null,
						'description' => null
					],
					'user' => [
						'name' => null
					],
					'settings' => [
						'hasSignatureFile' => true
					]
				], function ($self) {
					$self->createUser('username', 'password');
					$fileUser = $self->createMock(FileUser::class);
					$fileUser
						->method('__call')
						->withConsecutive(
							[$self->equalTo('getUserId')],
							[$self->equalTo('getSigned')]
						)
						->will($self->returnValueMap([
							['getUserId', [], 'username'],
							['getSigned', [], false]
						]));
					$self->fileUserMapper
						->method('getByUuid')
						->will($self->returnValue($fileUser));
					$self->fileMapper
						->method('getByUuid')
						->willReturn($fileUser);
					$self->fileMapper
						->method('getById')
						->willReturn($fileUser);
					$node = $self->createMock(\OCP\Files\File::class);
					$node->method('getContent')->will($self->returnValue('content'));
					$folder = $self->createMock(\OCP\Files\Folder::class);
					$folder
						->method('getById')
						->willReturn([$node]);
					$self->root
						->method('getUserFolder')
						->willReturn($folder);
				}
			],
		];
	}

	public function testGetConfigUsingFileTypeWithSuccess() {
		$this->createUser('username', 'password');
		$fileUser = $this->createMock(FileUser::class);
		$fileUser
			->method('__call')
			->withConsecutive(
				[$this->equalTo('getUserId')],
				[$this->equalTo('getSigned')]
			)
			->will($this->returnValueMap([
				['getUserId', [], 'username'],
				['getSigned', [], false]
			]));
		$this->fileUserMapper
			->method('getByUuid')
			->will($this->returnValue($fileUser));
		$this->fileMapper
			->method('getByUuid')
			->willReturn($fileUser);
		$this->fileMapper
			->method('getById')
			->willReturn($fileUser);
		$node = $this->createMock(\OCP\Files\File::class);
		$node->method('getId')->will($this->returnValue(171));
		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder
			->method('getById')
			->willReturn([$node]);
		$this->root
			->method('getUserFolder')
			->willReturn($folder);

		$actual = $this->accountService->getConfig('uuid', 'username', 'file');
		$this->assertJsonStringEqualsJsonString(
			json_encode($actual),
			json_encode([
				'action' => JSActions::ACTION_SIGN,
				'sign' => [
					'pdf' => [
						'file' => new \stdClass()
					],
					'filename' => null,
					'description' => null
				],
				'user' => [
					'name' => null
				],
				'settings' => [
					'hasSignatureFile' => true
				]
			])
		);
		$this->assertInstanceOf(\OCP\Files\File::class, $actual['sign']['pdf']['file']);
	}

	public function testGetPdfByUuidWithSuccess() {
		$this->createUser('username', 'password');

		$fileUser = $this->createMock(FileUser::class);
		$fileUser
			->method('__call')
			->withConsecutive(
				[$this->equalTo('getUserId')],
				[$this->equalTo('getNodeId')],
				[$this->equalTo('getId')]
			)
			->will($this->returnValueMap([
				['getUserId', [], 'username'],
				['getNodeId', [], 171],
				['getId', [], 171]
			]));
		$this->fileMapper
			->method('getByUuid')
			->will($this->returnValue($fileUser));

		$node = $this->createMock(\OCP\Files\File::class);
		$node->method('getId')->will($this->returnValue(171));
		$this->root
			->method('getById')
			->will($this->returnValue([$node]));
		$this->root
			->method('nodeExists')
			->willReturn(true);

		$fileUser = $this->createMock(FileUser::class);
		$fileUser
			->method('__call')
			->with($this->equalTo('getSigned'))
			->willReturn(true);
		$this->fileUserMapper
			->method('getByFileId')
			->willReturn([$fileUser]);
		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder
			->method('getById')
			->willReturn([$node]);
		$this->root
			->method('getUserFolder')
			->willReturn($folder);

		$actual = $this->accountService->getPdfByUuid('uuid');
		$this->assertInstanceOf(\OCP\Files\File::class, $actual);
	}

	public function testCanRequestSignWithUnexistentUser() {
		$actual = $this->accountService->canRequestSign();
		$this->assertFalse($actual);
	}

	public function testCanRequestSignWithoutGroups() {
		$this->config
			->method('getAppValue')
			->willReturn(null);
		$user = $this->createMock(\OCP\IUser::class);
		$actual = $this->accountService->canRequestSign($user);
		$this->assertFalse($actual);
	}

	public function testCanRequestSignWithUserOutOfAuthorizedGroups() {
		$this->config
			->method('getAppValue')
			->willReturn('["admin"]');
		$this->groupManager
			->method('getUserGroupIds')
			->willReturn([]);
		$user = $this->createMock(\OCP\IUser::class);
		$actual = $this->accountService->canRequestSign($user);
		$this->assertFalse($actual);
	}

	public function testCanRequestSignWithSuccess() {
		$this->config
			->method('getAppValue')
			->willReturn('["admin"]');
		$this->groupManager
			->method('getUserGroupIds')
			->willReturn(['admin']);
		$user = $this->createMock(\OCP\IUser::class);
		$actual = $this->accountService->canRequestSign($user);
		$this->assertTrue($actual);
	}

	public function testAccountvalidateWithSuccess() {
		$this->config
			->method('getAppValue')
			->will($this->returnValue(json_encode(['VALID'])));
		$user = $this->createMock(\OCP\IUser::class);
		$user->method('getUID')
			->willReturn('username');
		$actual = $this->accountService->validateAccountFiles([
			[
				'type' => 'VALID',
				'file' => [
					'base64' => 'dGVzdA=='
				]
			]
		], $user);
		$this->assertNull($actual);
	}

	public function testAccountvalidateWithInvalidFileType() {
		$this->expectExceptionMessage('Invalid file type.');
		$this->config
			->method('getAppValue')
			->will($this->returnValue(json_encode(['VALID'])));
		$user = $this->createMock(\OCP\IUser::class);
		$this->accountService->validateAccountFiles([
			[
				'type' => 'invalid',
				'file' => [
					'base64' => 'invalid'
				]
			]
		], $user);
	}

	public function testAccountvalidateWithInvalidBase64() {
		$this->expectExceptionMessage('Invalid base64 file');
		$this->config
			->method('getAppValue')
			->will($this->returnValue(json_encode(['VALID'])));
		$user = $this->createMock(\OCP\IUser::class);
		$this->accountService->validateAccountFiles([
			[
				'type' => 'VALID',
				'file' => [
					'base64' => 'invalid'
				]
			]
		], $user);
	}

	public function testAddFilesToAccountWithSuccess() {
		$this->config
			->method('getAppValue')
			->willReturn('["VALID"]');
		$files = [
			[
				'type' => 'VALID',
				'file' => [
					'base64' => base64_encode(file_get_contents(__DIR__ . '/../../fixtures/small_valid.pdf'))
				]
			]
		];
		$user = $this->createMock(\OCP\IUser::class);
		$user->method('getUID')
			->willReturn('username');
		$return = $this->accountService->addFilesToAccount($files, $user);
		$this->assertNull($return);
	}
}
