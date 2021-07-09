<?php

namespace OCA\Libresign\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use OCA\Libresign\Exception\LibresignException;

/**
 * Class FileMapper
 *
 * @package OCA\Libresign\Handler
 *
 * @method CfsslHandler setPassword(string $password)
 * @method string getPassword()
 * @method CfsslHandler setCommonName(string $commonName)
 * @method string getCommonName()
 * @method CfsslHandler setHosts(string $hosts)
 * @method array getHosts()
 * @method CfsslHandler setCountry(string $country)
 * @method string getCountry()
 * @method CfsslHandler setOrganization(string $organization)
 * @method string getOrganization()
 * @method CfsslHandler setOrganizationUnit(string $organizationUnit)
 * @method string getOrganizationUnit()
 * @method CfsslHandler setCfsslUri(string $cfsslUri)
 * @method string getCfsslUri()
 * @method CfsslHandler setClient(Client $client)
 * @method Client getClient()
 */
class CfsslHandler {
	private $commonName;
	private $hosts = [];
	private $country;
	private $organization;
	private $organizationUnit;
	private $cfsslUri;
	private $password;
	private $client;
	public function __call($name, $arguments) {
		if (!preg_match('/^(?<type>get|set)(?<property>.+)/', $name, $matches)) {
			throw new \LogicException(sprintf('Cannot set non existing property %s->%s = %s.', \get_class($this), $name, var_export($arguments, true)));
		}
		$property = lcfirst($matches['property']);
		if (!property_exists($this, $property)) {
			throw new \LogicException(sprintf('Cannot set non existing property %s->%s = %s.', \get_class($this), $name, var_export($arguments, true)));
		}
		if ($matches['type'] === 'get') {
			return $this->$property;
		}
		$this->$property = $arguments[0] ?? null;
		return $this;
	}

	public function getClient() {
		if (!$this->client) {
			$this->setClient(new Client(['base_uri' => $this->getCfsslUri()]));
		}
		return $this->client;
	}

	public function generateCertificate(): string {
		$certKeys = $this->newCert();
		$certContent = null;
		try {
			openssl_pkcs12_export($certKeys['certificate'], $certContent, $certKeys['private_key'], $this->getPassword());
		} catch (\Throwable $th) {
			throw new LibresignException('Error while creating certificate file', 500);
		}

		return $certContent;
	}

	private function newCert() {
		$json = [
			'json' => [
				'profile' => 'CA',
				'request' => [
					'hosts' => $this->getHosts(),
					'CN' => $this->getCommonName(),
					'key' => [
						'algo' => 'rsa',
						'size' => 2048,
					],
					'names' => [
						[
							'C' => $this->getCountry(),
							'O' => $this->getOrganization(),
							'OU' => $this->getOrganizationUnit(),
							'CN' => $this->getCommonName(),
						],
					],
				],
			],
		];
		try {
			$response = $this->getClient()
				->post(
					'newcert',
					$json
				)
			;
		} catch (TransferException $th) {
			if ($th->getHandlerContext() && $th->getHandlerContext()['error']) {
				throw new \Exception($th->getHandlerContext()['error'], 1);
			}
			throw new LibresignException($th->getMessage(), 500);
		}

		$responseDecoded = json_decode($response->getBody(), true);
		if (!isset($responseDecoded['success']) || !$responseDecoded['success']) {
			throw new LibresignException('Error while generating certificate keys!', 500);
		}

		return $responseDecoded['result'];
	}

	public function health(string $cfsslUri) {
		try {
			$response = $this->getClient()
				->get(
					'health',
					[
						'base_uri' => $cfsslUri
					]
				)
			;
		} catch (TransferException $th) {
			if ($th->getHandlerContext() && $th->getHandlerContext()['error']) {
				throw new \Exception($th->getHandlerContext()['error'], 1);
			}
			throw new LibresignException($th->getMessage(), 500);
		}

		$responseDecoded = json_decode($response->getBody(), true);
		if (!isset($responseDecoded['success']) || !$responseDecoded['success']) {
			throw new LibresignException('Error while check cfssl API health!', 500);
		}

		return $responseDecoded['result'];
	}
}
