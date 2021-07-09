<?php

namespace OCA\Libresign\Tests\Api\Controller;

use OCA\Libresign\Tests\Api\ApiTestCase;

/**
 * @group DB
 */
final class SignFileDeprecatedControllerTest extends ApiTestCase {
	/**
	 * @runInSeparateProcess
	 */
	public function testDeleteRegisterWithValidationFailure() {
		$user = $this->createUser('username', 'password');

		$this->request
			->withMethod('DELETE')
			->withPath('/webhook/register/signature')
			->withRequestHeader([
				'Authorization' => 'Basic ' . base64_encode('username:password'),
				'Content-Type' => 'application/json'
			])
			->withRequestBody([
				'uuid' => 'invalid',
				'users' => []
			])
			->assertResponseCode(422);

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$this->assertEquals('You are not allowed to request signing', $body['message']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testDeleteRegisterWithSuccess() {
		$user = $this->createUser('username', 'password');

		$this->mockConfig([
			'libresign' => [
				'webhook_authorized' => '["admin","testGroup"]',
				'notifyUnsignedUser' => 0
			]
		]);

		$user->setEMailAddress('person@test.coop');
		$file = $this->requestSignFile([
			'file' => ['base64' => base64_encode(file_get_contents(__DIR__ . '/../../fixtures/small_valid.pdf'))],
			'name' => 'test',
			'users' => [
				[
					'email' => 'user01@test.coop'
				]
			],
			'userManager' => $user
		]);

		$this->request
			->withMethod('DELETE')
			->withPath('/webhook/register/signature')
			->withRequestHeader([
				'Authorization' => 'Basic ' . base64_encode('username:password'),
				'Content-Type' => 'application/json'
			])
			->withRequestBody([
				'uuid' => $file['uuid'],
				'users' => [
					[
						'email' => 'user01@test.coop'
					]
				]
			]);

		$this->assertRequest();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testPatchRegisterDeprecatedWithSuccess() {
		$user = $this->createUser('username', 'password');

		$this->mockConfig([
			'libresign' => [
				'webhook_authorized' => '["admin","testGroup"]',
				'notifyUnsignedUser' => 0
			]
		]);

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
			->withMethod('PATCH')
			->withPath('/webhook/register')
			->withRequestHeader([
				'Authorization' => 'Basic ' . base64_encode('username:password'),
				'Content-Type' => 'application/json'
			])
			->withRequestBody([
				'uuid' => $file['uuid'],
				'users' => [
					[
						'email' => 'user@test.coop'
					]
				]
			]);

		$response = $this->assertRequest();
		$body = json_decode($response->getBody()->getContents(), true);
		$body['data']['users'][] = ['email' => 'user@test.coop'];
	}
}
