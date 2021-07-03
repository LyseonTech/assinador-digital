<?php

namespace OCA\Libresign\Tests\Api\Controller;

use OCA\Libresign\Tests\Api\ApiTestCase;

/**
 * @internal
 * @group DB
 */
final class LibreSignFileControllerTest extends ApiTestCase {
	/**
	 * @runInSeparateProcess
	 */
	public function testValidateUsignUuidWithInvalidData() {
		$this->request
			->withPath('/file/validate/uuid/invalid')
			->assertResponseCode(404);

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$this->assertEquals('Invalid data to validate file', $body['errors'][0]);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testValidateUsignFileIdWithInvalidData() {
		$this->request
			->withPath('/file/validate/file_id/171')
			->assertResponseCode(404);

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$this->assertEquals('Invalid data to validate file', $body['errors'][0]);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testValidateWithSuccessUsingUnloggedUser() {
		$user = $this->createUser('username', 'password');

		$user->setEMailAddress('person@test.coop');
		$file = $this->requestSignFile([
			'file' => ['base64' => base64_encode(file_get_contents(__DIR__ . '/../../fixtures/small_valid.pdf'))],
			'name' => 'test',
			'users' => [
				[
					'email' => 'person@test.coop'
				]
			],
			'userManager' => $user
		]);

		$this->request
			->withPath('/file/validate/uuid/' . $file['uuid']);

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$this->assertFalse($body['signers'][0]['me'], "It's me");
		$this->assertFalse($body['settings']['canRequestSign'], 'Can permission to request sign');
		$this->assertFalse($body['settings']['canSign'], 'Can permission to sign');
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testValidateWithSuccessUsingLoggedUserAndOutsideAllowedRequestSignGroups() {
		$user = $this->createUser('username', 'password');

		$user->setEMailAddress('person@test.coop');
		$file = $this->requestSignFile([
			'file' => ['base64' => base64_encode(file_get_contents(__DIR__ . '/../../fixtures/small_valid.pdf'))],
			'name' => 'test',
			'users' => [
				[
					'email' => 'person@test.coop'
				]
			],
			'userManager' => $user
		]);

		$this->mockConfig(['libresign' => ['webhook_authorized' => '[]']]);

		$this->request
			->withRequestHeader([
				'Authorization' => 'Basic ' . base64_encode('username:password')
			])
			->withPath('/file/validate/uuid/' . $file['uuid']);

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$this->assertTrue($body['signers'][0]['me'], "It's me");
		$this->assertFalse($body['settings']['canRequestSign'], 'Can permission to request sign');
		$this->assertTrue($body['settings']['canSign'], 'Can permission to sign');
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testControllerListWithEmptyData() {
		$this->createUser('testControllerListWithSuccess', 'password');
		$this->request
			->withRequestHeader([
				'Authorization' => 'Basic ' . base64_encode('testControllerListWithSuccess:password')
			])
			->withPath('/file/list');

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$this->assertCount(0, $body['data']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testControllerListWithOneFileOneSignerUnsignedNotMe() {
		$this->createUser('testControllerListWithOneFileOneSignerUnsignedNotMe', 'password');

		$user = $this->createUser('testControllerListWithOneFileOneSignerUnsignedNotMe', 'password');
		$user->setEMailAddress('testControllerListWithOneFileOneSignerUnsignedNotMe' . '@test.coop');
		$file = $this->requestSignFile([
			'file' => ['base64' => base64_encode(file_get_contents(__DIR__ . '/../../fixtures/small_valid.pdf'))],
			'name' => 'test',
			'users' => [
				[
					'email' => 'person01@test.coop'
				]
			],
			'userManager' => $user
		]);

		$this->request
			->withRequestHeader([
				'Authorization' => 'Basic ' . base64_encode('testControllerListWithOneFileOneSignerUnsignedNotMe:password')
			])
			->withPath('/file/list');

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$this->assertCount(1, $body['data']);
		$this->assertCount(1, $body['data'][0]['signers']);
		$this->assertNull($body['data'][0]['signers'][0]['sign_date']);
		$this->assertFalse($body['data'][0]['signers'][0]['me']);
		$this->assertEquals('pending', $body['data'][0]['status']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testControllerListWithoutSigners() {
		$username = 'testControllerListWithoutSigners_' . time();
		$user = $this->createUser($username, 'password');
		$user->setEMailAddress($username . '@test.coop');
		$file = $this->requestSignFile([
			'file' => ['base64' => base64_encode(file_get_contents(__DIR__ . '/../../fixtures/small_valid.pdf'))],
			'name' => 'test',
			'users' => [
				[
					'email' => 'person01@test.coop'
				]
			],
			'userManager' => $user
		]);

		$fileUserMapper = \OC::$server->get(\OCA\Libresign\Db\FileUserMapper::class);
		$fileUserMapper->delete($file['users'][0]);

		$this->request
			->withRequestHeader([
				'Authorization' => 'Basic ' . base64_encode($username . ':password')
			])
			->withPath('/file/list');

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$this->assertCount(1, $body['data']);
		$this->assertCount(0, $body['data'][0]['signers']);
		$this->assertEquals('no signers', $body['data'][0]['status']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testControllerListSigned() {
		$username = 'testControllerListSigned_' . time();
		$user = $this->createUser($username, 'password');
		$user->setEMailAddress($username . '@test.coop');
		$file = $this->requestSignFile([
			'file' => ['base64' => base64_encode(file_get_contents(__DIR__ . '/../../fixtures/small_valid.pdf'))],
			'name' => 'test',
			'users' => [
				[
					'email' => 'person01@test.coop'
				]
			],
			'userManager' => $user
		]);

		$fileUserMapper = \OC::$server->get(\OCA\Libresign\Db\FileUserMapper::class);
		$file['users'][0]->setSigned(time());
		$fileUserMapper->update($file['users'][0]);

		$this->request
			->withRequestHeader([
				'Authorization' => 'Basic ' . base64_encode($username . ':password')
			])
			->withPath('/file/list');

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$this->assertCount(1, $body['data']);
		$this->assertCount(1, $body['data'][0]['signers']);
		$this->assertNotEmpty($body['data'][0]['signers'][0]['sign_date']);
		$this->assertNotEmpty($body['data'][0]['status_date']);
		$this->assertEquals('signed', $body['data'][0]['status']);
	}
}
