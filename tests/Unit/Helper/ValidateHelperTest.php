<?php

namespace OCA\Libresign\Tests\Unit\Helper;

use OCA\Libresign\Db\FileMapper;
use OCA\Libresign\Db\FileUserMapper;
use OCA\Libresign\Helper\ValidateHelper;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;

final class ValidateHelperTest extends \OCA\Libresign\Tests\Unit\TestCase {
	/** @var ValidateHelper */
	private $validateHelper;
	/** @var IL10N */
	private $l10n;
	/** @var FileUserMapper */
	private $fileUserMapper;
	/** @var FileMapper */
	private $fileMapper;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IRootFolder */
	private $root;

	public function setUp(): void {
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n
			->method('t')
			->will($this->returnArgument(0));
		$this->fileUserMapper = $this->createMock(FileUserMapper::class);
		$this->fileMapper = $this->createMock(FileMapper::class);
		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->root = $this->createMock(IRootFolder::class);
		$this->validateHelper = new ValidateHelper(
			$this->l10n,
			$this->fileUserMapper,
			$this->fileMapper,
			$this->config,
			$this->groupManager,
			$this->root
		);
	}

	public function testValidateFileWithoutAllNecessaryData() {
		$this->expectExceptionMessage('Inform URL or base64 or fileID to sign');
		$this->validateHelper->validateNewFile([
			'file' => ['invalid'],
			'name' => 'test'
		]);
	}

	public function testValidateFileWithInvalidFileId() {
		$this->expectExceptionMessage('Invalid fileID');
		$this->validateHelper->validateNewFile([
			'file' => ['fileId' => 'invalid'],
			'name' => 'test'
		]);
	}

	public function testValidateFileWhenFileIdDoesNotExist() {
		$this->expectExceptionMessage('Invalid fileID');
		$this->root->method('getById')->will($this->returnCallback(function () {
			throw new \Exception('not found');
		}));
		$this->validateHelper->validateNewFile([
			'file' => ['fileId' => 123],
			'name' => 'test'
		]);
	}

	public function testValidateFileUsingFileIdWithSuccess() {
		$file = $this->createMock(\OCP\Files\File::class);
		$file
			->method('getMimeType')
			->willReturn('application/pdf');
		$this->root
			->method('getById')
			->willReturn([$file]);
		$actual = $this->validateHelper->validateNewFile([
			'file' => ['fileId' => 123],
			'name' => 'test'
		]);
		$this->assertNull($actual);
	}

	public function testValidateNotRequestedSignWhenAlreadyAskedToSignThisDocument() {
		$this->fileUserMapper->method('getByNodeId')->will($this->returnValue('exists'));
		$this->expectExceptionMessage('Already asked to sign this document');
		$this->validateHelper->validateNotRequestedSign(1);
	}

	public function testValidateNotRequestedSignWithSuccessWhenNotFound() {
		$this->fileUserMapper->method('getByNodeId')->will($this->returnCallback(function () {
			throw new \Exception('not found');
		}));
		$actual = $this->validateHelper->validateNotRequestedSign(1);
		$this->assertNull($actual);
	}

	public function testValidateLibreSignNodeIdWhenFileIdNotExists() {
		$this->fileUserMapper->method('getByNodeId')->will($this->returnCallback(function () {
			throw new \Exception('not found');
		}));
		$this->expectExceptionMessage('Invalid fileID');
		$this->validateHelper->validateLibreSignNodeId(1);
	}

	public function testValidateMimeTypeAcceptedWhenFileIsNotPDF() {
		$file = $this->createMock(\OCP\Files\File::class);
		$file
			->method('getMimeType')
			->willReturn('invalid');
		$this->root
			->method('getById')
			->willReturn([$file]);
		$this->expectExceptionMessage('Must be a fileID of a PDF');
		$this->validateHelper->validateMimeTypeAccepted(171);
	}

	public function testValidateMimeTypeAcceptedWithValidFile() {
		$file = $this->createMock(\OCP\Files\File::class);
		$file
			->method('getMimeType')
			->willReturn('application/pdf');
		$this->root
			->method('getById')
			->willReturn([$file]);
		$actual = $this->validateHelper->validateMimeTypeAccepted(171);
		$this->assertNull($actual);
	}

	public function testValidateLibreSignNodeIdWhenSuccess() {
		$libresignFile = $this->createMock(\OCA\Libresign\Db\File::class);
		$this->fileMapper
			->method('getByFileId')
			->willReturn($libresignFile);
		$file = $this->createMock(\OCP\Files\File::class);
		$file
			->method('getMimeType')
			->willReturn('application/pdf');
		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder
			->method('getById')
			->willReturn([$file]);
		$this->root
			->method('getUserFolder')
			->willReturn($folder);
		$actual = $this->validateHelper->validateLibreSignNodeId(1);
		$this->assertNull($actual);
	}

	public function testCanRequestSignWithoutUserManager() {
		$this->expectExceptionMessage('You are not allowed to request signing');

		$user = $this->createMock(\OCP\IUser::class);
		$this->validateHelper->canRequestSign($user);
	}

	public function testCanRequestSignWithoutPermission() {
		$this->expectExceptionMessage('You are not allowed to request signing');

		$this->config = $this->createMock(IConfig::class);
		$this->config
			->method('getAppValue')
			->willReturn('["admin"]');
		$this->groupManager
			->method('getUserGroupIds')
			->willReturn([]);
		$this->validateHelper = new ValidateHelper(
			$this->l10n,
			$this->fileUserMapper,
			$this->fileMapper,
			$this->config,
			$this->groupManager,
			$this->root
		);
		$user = $this->createMock(\OCP\IUser::class);
		$this->validateHelper->canRequestSign($user);
	}

	public function testValidateFileWithEmptyFile() {
		$this->expectExceptionMessage('Empty file');

		$this->validateHelper->validateNewFile([
			'file' => []
		]);
	}

	public function testValidateInvalidBase64File() {
		$this->expectExceptionMessage('Invalid base64 file');

		$user = $this->createMock(\OCP\IUser::class);
		$this->validateHelper->validateNewFile([
			'file' => ['base64' => 'qwert'],
			'name' => 'test',
			'userManager' => $user
		]);
	}

	public function testIRequestedSignThisFileWithInvalidRequester() {
		$this->expectExceptionMessage('You are not the signer request for this file');
		$libresignFile = $this->createMock(\OCA\Libresign\Db\File::class);
		$libresignFile
			->method('__call')
			->willReturn('user1');
		$this->fileMapper
			->method('getByFileId')
			->willReturn($libresignFile);
		$user = $this->createMock(\OCP\IUser::class);
		$user
			->method('getUID')
			->willReturn('user2');
		$this->validateHelper->iRequestedSignThisFile($user, 171);
	}

	/**
	 * @dataProvider dataProviderHaveValidMail
	 */
	public function testHaveValidMailWithDataProvider($data, $errorMessage) {
		$this->expectExceptionMessage($errorMessage);
		$this->validateHelper->haveValidMail($data);
	}

	public function dataProviderHaveValidMail() {
		return [
			[[], 'User needs values'],
			[[''], 'Email required'],
			[['email' => 'invalid'], 'Invalid email']
		];
	}

	public function testSignerWasAssociatedWithNotLibreSignFileLoaded() {
		$this->expectExceptionMessage('File not loaded');
		$this->validateHelper->signerWasAssociated([
			'email' => 'invalid@test.coop'
		]);
	}

	public function testSignerWasAssociatedWithUnassociatedSigner() {
		$this->expectExceptionMessage('No signature was requested to %s');
		$libresignFile = $this->createMock(\OCA\Libresign\Db\File::class);
		$libresignFile
			->method('__call')
			->willReturn('uuid');
		$this->fileMapper
			->method('getByFileId')
			->willReturn($libresignFile);
		$this->fileUserMapper
			->method('getByFileUuid')
			->willReturn([]);
		$this->validateHelper->signerWasAssociated([
			'email' => 'invalid@test.coop'
		]);
	}

	public function testNotSignedWithError() {
		$this->expectExceptionMessage('%s already signed this file');
		$libresignFile = $this->createMock(\OCA\Libresign\Db\File::class);
		$libresignFile
			->method('__call')
			->willReturn('uuid');
		$this->fileMapper
			->method('getByFileId')
			->willReturn($libresignFile);
		$fileUser = $this->createMock(\OCA\Libresign\Db\FileUser::class);
		$fileUser
			->method('__call')
			->withConsecutive(
				[$this->equalTo('getEmail'), $this->anything()],
				[$this->equalTo('getSigned'), $this->anything()]
			)
			->will($this->returnValueMap([
				['getEmail', [], 'signed@test.coop'],
				['getSigned', [], date('Y-m-d H:i:s')]
			]));
		$this->fileUserMapper
			->method('getByFileUuid')
			->willReturn([$fileUser]);
		$this->validateHelper->notSigned([
			'email' => 'signed@test.coop'
		]);
	}

	public function testNotSignedWithFileNotLoaded() {
		$this->expectExceptionMessage('File not loaded');
		$this->validateHelper->notSigned([]);
	}

	public function testValidateIfNodeIdExistsWhenGetFileThrowException() {
		$this->expectExceptionMessage('Invalid fileID');
		$this->root
			->method('getById')
			->will($this->returnCallback(function () {
				throw new \Exception('not found');
			}));
		$this->validateHelper->validateIfNodeIdExists(171);
	}

	public function testValidateIfNodeIdExistsWithInvalidFile() {
		$this->expectExceptionMessage('Invalid fileID');
		$this->root
			->method('getById')
			->willReturn([0 => null]);
		$this->validateHelper->validateIfNodeIdExists(171);
	}

	public function testValidateIfNodeIdExistsWithSuccess() {
		$this->root
			->method('getById')
			->willReturn(['file']);
		$actual = $this->validateHelper->validateIfNodeIdExists(171);
		$this->assertNull($actual);
	}

	public function testValidateFileUuidWithInvalidUuid() {
		$this->expectExceptionMessage('Invalid UUID file');
		$this->fileMapper->method('getByUuid')->will($this->returnCallback(function () {
			throw new \Exception('not found');
		}));
		$this->validateHelper->validateFileUuid(['uuid' => 'invalid']);
	}

	public function testValidateFileUuidWithValidUuid() {
		$file = $this->createMock(\OCA\Libresign\Db\File::class);
		$this->fileMapper->method('getByUuid')->will($this->returnValue($file));
		$actual = $this->validateHelper->validateFileUuid(['uuid' => 'valid']);
		$this->assertNull($actual);
	}
}
