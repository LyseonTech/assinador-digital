<?php

use OCA\Libresign\Handler\JSignPdfHandler;
use OCA\Libresign\Handler\Pkcs12Handler;
use OCA\Libresign\Service\FolderService;
use OCP\IConfig;
use OCP\IL10N;

final class Pkcs12HandlerTest extends \OCA\Libresign\Tests\Unit\TestCase {
	/** @var Pkcs12Handler */
	protected $pkcs12Handler;
	/** @var FolderService */
	protected $folderService;
	/** @var JSignPdfHandler */
	protected $jSignPdfHandler;
	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l10n;

	public function setUp(): void {
		$this->folderService = $this->createMock(FolderService::class);
		$this->jSignPdfHandler = $this->createMock(JSignPdfHandler::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n
			->method('t')
			->will($this->returnArgument(0));
		$this->pkcs12Handler = new Pkcs12Handler(
			$this->folderService,
			$this->jSignPdfHandler,
			$this->config,
			$this->l10n
		);
	}

	public function testSavePfxWhenPfxFileIsAFolder() {
		$node = $this->createMock(\OCP\Files\Folder::class);
		$node->method('nodeExists')->will($this->returnValue(true));
		$node->method('get')->will($this->returnValue($node));
		$this->folderService->method('getFolder')->will($this->returnValue($node));

		$this->expectErrorMessage('path signature.pfx already exists and is not a file!');
		$this->pkcs12Handler->savePfx('userId', 'content');
	}

	public function testSavePfxWhenPfxFileExsitsAndIsAFile() {
		$node = $this->createMock(\OCP\Files\Folder::class);
		$node->method('nodeExists')->will($this->returnValue(true));
		$file = $this->createMock(\OCP\Files\File::class);
		$node->method('get')->will($this->returnValue($file));
		$this->folderService->method('getFolder')->will($this->returnValue($node));

		$actual = $this->pkcs12Handler->savePfx('userId', 'content');
		$this->assertInstanceOf(\OCP\Files\File::class, $actual);
	}

	public function testGetPfxWithInvalidPfx() {
		$node = $this->createMock(\OCP\Files\Folder::class);
		$node->method('nodeExists')->will($this->returnValue(false));
		$this->folderService->method('getFolder')->will($this->returnValue($node));
		$this->expectErrorMessage('Password to sign not defined. Create a password to sign');
		$this->expectExceptionCode(400);
		$this->pkcs12Handler->getPfx('userId');
	}

	public function testGetPfxOk() {
		$node = $this->createMock(\OCP\Files\Folder::class);
		$node->method('nodeExists')->will($this->returnValue(true));
		$node->method('get')->will($this->returnValue($node));
		$this->folderService->method('getFolder')->will($this->returnValue($node));
		$actual = $this->pkcs12Handler->getPfx('userId');
		$this->assertInstanceOf('\OCP\Files\Node', $actual);
	}

	public function testWriteFooterWithoutValidationSite() {
		$this->config = $this->createMock(IConfig::class);
		$this->config
			->method('getAppValue')
			->willReturn(null);
		$this->pkcs12Handler = new Pkcs12Handler(
			$this->folderService,
			$this->jSignPdfHandler,
			$this->config,
			$this->l10n
		);
		$file = $this->createMock(\OCP\Files\File::class);
		$actual = $this->pkcs12Handler->writeFooter($file, 'uuid');
		$this->assertNull($actual);
	}

	public function testWriteFooterWithSuccess() {
		$this->config = $this->createMock(IConfig::class);
		$this->config
			->method('getAppValue')
			->willReturnCallback(function ($appid, $key, $default) {
				switch ($key) {
					case 'add_footer': return true;
					case 'validation_site': return 'http://test.coop';
					case 'write_qrcode_on_footer': return true;
				}
			});
		$this->pkcs12Handler = new Pkcs12Handler(
			$this->folderService,
			$this->jSignPdfHandler,
			$this->config,
			$this->l10n
		);

		$resource = fopen(__DIR__ . '/../../fixtures/small_valid.pdf', 'r');
		$file = $this->createMock(\OCP\Files\File::class);
		$file->method('fopen')
			->willReturn($resource);
		$actual = $this->pkcs12Handler->writeFooter($file, 'uuid');
		// file_put_contents(__DIR__ . '/../../fixtures/small_valid-signed.pdf', $actual);
		$expected = file_get_contents(__DIR__ . '/../../fixtures/small_valid-signed.pdf');
		$this->assertEquals(strlen($expected), strlen($actual));
	}
}
