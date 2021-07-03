<?php

namespace OCA\Libresign\Tests\Unit\Service;

use OC\Mail\Mailer;
use OCA\Libresign\Db\File;
use OCA\Libresign\Db\FileMapper;
use OCA\Libresign\Db\FileUser;
use OCA\Libresign\Service\MailService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Mail\IMailer;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class MailServiceTest extends \OCA\Libresign\Tests\Unit\TestCase {
	/** @var LoggerInterface */
	private $logger;
	/** @var Mailer */
	private $mailer;
	/** @var FileMapper */
	private $fileMapper;
	/** @var IL10N */
	private $l10n;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IConfig */
	private $config;
	/** @var MailService */
	private $service;

	public function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->fileMapper = $this->createMock(FileMapper::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n
			->method('t')
			->will($this->returnArgument(0));
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->service = new MailService(
			$this->logger,
			$this->mailer,
			$this->fileMapper,
			$this->l10n,
			$this->urlGenerator,
			$this->config
		);
	}

	public function testSuccessNotifyUnsignedUser() {
		$fileUser = $this->createMock(FileUser::class);
		$fileUser
			->method('__call')
			->withConsecutive(
				[$this->equalTo('getUuid'), $this->anything()],
				[$this->equalTo('getFileId'), $this->anything()]
			)
			->will($this->returnValueMap([
				['getUuid', [], 'asdfg'],
				['getFileId', [], 1]
			]));
		
		$file = $this->createMock(File::class);
		$file
			->method('__call')
			->with($this->equalTo('getName'), $this->anything())
			->will($this->returnValue('Filename'));
		$this->fileMapper
			->method('getById')
			->will($this->returnValue($file));
		$this->config
			->method('getAppValue')
			->willReturn(true);
		$actual = $this->service->notifyUnsignedUser($fileUser);
		$this->assertNull($actual);
	}

	public function testFailToSendMailToUnsignedUser() {
		$this->expectExceptionMessage('Notify unsigned notification mail could not be sent');

		$fileUser = $this->createMock(FileUser::class);
		$fileUser
			->method('__call')
			->withConsecutive(
				[$this->equalTo('getUuid'), $this->anything()],
				[$this->equalTo('getFileId'), $this->anything()]
			)
			->will($this->returnValueMap([
				['getUuid', [], 'asdfg'],
				['getFileId', [], 1]
			]));

		$file = $this->createMock(File::class);
		$file
			->method('__call')
			->with($this->equalTo('getName'), $this->anything())
			->will($this->returnValue('Filename'));
		$this->fileMapper
			->method('getById')
			->will($this->returnValue($file));
		$this->mailer
			->method('send')
			->willReturnCallback(function () {
				throw new \Exception("Error Processing Request", 1);
			});
		$this->config
			->method('getAppValue')
			->will($this->returnValue(true));
		$actual = $this->service->notifyUnsignedUser($fileUser);
		$this->assertNull($actual);
	}
}
