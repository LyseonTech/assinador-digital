<?php

namespace OCA\Libresign\Service;

use OCA\Libresign\AppInfo\Application;
use OCA\Libresign\Handler\CfsslHandler;
use OCA\Libresign\Handler\CfsslServerHandler;
use OCP\IConfig;

class AdminSignatureService {
	/** @var CfsslServerHandler */
	private $cfsslServerHandler;
	/** @var CfsslHandler */
	private $cfsslHandler;

	/** @var IConfig */
	private $config;

	public function __construct(
		CfsslServerHandler $cfsslServerHandler,
		CfsslHandler $cfsslHandler,
		IConfig $config
	) {
		$this->cfsslServerHandler = $cfsslServerHandler;
		$this->cfsslHandler = $cfsslHandler;
		$this->config = $config;
	}

	public function generate(
		string $commonName,
		string $country,
		string $organization,
		string $organizationUnit,
		string $cfsslUri,
		string $configPath
	) {
		$key = bin2hex(random_bytes(16));

		$this->cfsslServerHandler->createConfigServer(
			$commonName,
			$country,
			$organization,
			$organizationUnit,
			$key,
			$configPath
		);
		for ($i = 1;$i <= 4;$i++) {
			if ($this->cfsslHandler->health($cfsslUri)) {
				break;
			}
			// @codeCoverageIgnoreStart
			sleep($i);
			// @codeCoverageIgnoreEnd
		}

		$this->config->setAppValue(Application::APP_ID, 'authkey', $key);
		$this->config->setAppValue(Application::APP_ID, 'commonName', $commonName);
		$this->config->setAppValue(Application::APP_ID, 'country', $country);
		$this->config->setAppValue(Application::APP_ID, 'organization', $organization);
		$this->config->setAppValue(Application::APP_ID, 'organizationUnit', $organizationUnit);
		$this->config->setAppValue(Application::APP_ID, 'cfsslUri', $cfsslUri);
		$this->config->setAppValue(Application::APP_ID, 'configPath', $configPath);
		$this->config->setAppValue(Application::APP_ID, 'notifyUnsignedUser', 1);
	}

	public function loadKeys() {
		return [
			'commonName' => $this->config->getAppValue(Application::APP_ID, 'commonName'),
			'country' => $this->config->getAppValue(Application::APP_ID, 'country'),
			'organization' => $this->config->getAppValue(Application::APP_ID, 'organization'),
			'organizationUnit' => $this->config->getAppValue(Application::APP_ID, 'organizationUnit'),
			'cfsslUri' => $this->config->getAppValue(Application::APP_ID, 'cfsslUri'),
			'configPath' => $this->config->getAppValue(Application::APP_ID, 'configPath'),
		];
	}
}
