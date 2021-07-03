<?php

namespace OCA\Libresign\Handler;

use Jeidison\JSignPDF\JSignPDF;
use Jeidison\JSignPDF\Sign\JSignParam;
use OCA\Libresign\AppInfo\Application;
use OCP\Files\File;
use OCP\IConfig;

class JSignPdfHandler {
	/** @var JSignPDF */
	private $jSignPdf;
	/** @var JSignParam */
	private $jSignParam;
	/** @var IConfig */
	private $config;

	public function __construct(
		IConfig $config
	) {
		$this->config = $config;
	}

	public function setJSignPdf($jSignPdf) {
		$this->jSignPdf = $jSignPdf;
	}

	public function getJSignPdf() {
		if (!$this->jSignPdf) {
			// @codeCoverageIgnoreStart
			$this->setJSignPdf(new JSignPDF());
			// @codeCoverageIgnoreEnd
		}
		return $this->jSignPdf;
	}

	public function getJSignParam(): JSignParam {
		if (!$this->jSignParam) {
			$this->jSignParam = (new JSignParam())
				->setTempPath(
					$this->config->getAppValue(Application::APP_ID, 'jsignpdf_temp_path', '/tmp/')
				)
				->setIsUseJavaInstalled(true)
				->setjSignPdfJarPath(
					$this->config->getAppValue(Application::APP_ID, 'jsignpdf_jar_path', '/opt/jsignpdf-1.6.4/JSignPdf.jar')
				);
		}
		return $this->jSignParam;
	}

	public function sign(
		File $inputFile,
		File $certificate,
		string $password
	): string {
		$param = $this->getJSignParam()
			->setCertificate($certificate->getContent())
			->setPdf($inputFile->getContent())
			->setPassword($password);

		$jSignPdf = $this->getJSignPdf();
		$jSignPdf->setParam($param);
		return $jSignPdf->sign();
	}
}
