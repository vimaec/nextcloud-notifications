<?php
/**
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Notifications\Tests\Unit\Controller;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Security\IdentityProof\Key;
use OC\Security\IdentityProof\Manager;
use OCA\Notifications\Controller\PushController;
use OCA\Notifications\Tests\Unit\TestCase;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;

class PushControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;
	/** @var IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	protected $db;
	/** @var ISession|\PHPUnit_Framework_MockObject_MockObject */
	protected $session;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;
	/** @var IProvider|\PHPUnit_Framework_MockObject_MockObject */
	protected $tokenProvider;
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $identityProof;

	/** @var IUser|\PHPUnit_Framework_MockObject_MockObject */
	protected $user;
	/** @var PushController */
	protected $controller;

	protected $devicePublicKey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2Or1KumSDfk8dT0MuCW9
WS5wkVOpNsbz2OIJFBYrBvu6joC2iQo9StONMaXoTQj5Ucak9UBtC60PHyTkIDFb
HOpCST5onmIAtZdqHN/3ABOBeHVU/notdRIl/menGM64jiqGWvE06F1+yZ8GGcGQ
8RKzabqMd2K1iUohXP625uzTABVaiwz3u8nGEwui5R6Pf5Fy6DccuqdUMtJIfW21
Z4Tj48Tw+pR+fUrGpa1Wg+wiwlg7ISK8Symml1Rd6hSRXK2t8Opm/kjH9ZX8oVwn
RSO1ehjzRpTY+gdw/5gvwMZI0XmrIanZmZHwePRR4HC6FLPrL2OQG3gWikDIPyTS
hQIDAQAB
-----END PUBLIC KEY-----';

	protected $userPrivateKey = '-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDPR0uV6e1cNSoy
vsITBvGyYpOIn9vI7zpEhk7FGGwdOTd2dxxJ2ikegRJ6Fr2Ojce15K3zfiasXPen
TAQuFEXecGoP9WY+DS5X1LfCpj9EeAOBfVGKeQDst5z/GoXeU+YqWbayJTp6vFRj
7o5X6QDCCXy25Kt4snNDWTHPlMc44BLjZ6w+Wj0D2ySlz1dGpunc0vwYN/uEyjr9
ztmiN82TZtZHgzN43DJSv7tLufsZgGsWnVlytXmsi4QuCAKcm92X2ZtIXkn5niMW
DxJJepqFx7pC3ILXMZKYolAtt91VvLiGQjzURhq7HA4QdqvFyKXp0uLN2rKZjqQ0
2nUzC34XAgMBAAECggEAFrL/Ew7IIKXt1hrP1BeZlmh3MaoX/pw8LE7tB2aSSG0A
pueKYIgUorON23LsFVVvfnrpldXF1HBl6ptHhehQcnirFM5SAQ+eeJ3h9d4Q5aWi
9KZNrLVtpX7CIam86UkU1qR2fnHXQqOnNj5ktjndDGLPlpPaN2CLgN+etdXcL10g
G5fltrFnTzYgkYap/eNkY+ivA+0xqc1l3jP2i5PHihv1adcoiOuam36GARM9C51X
fyWvMtxMvkRAZsdTATtRcQsEoJuQ3Rvseei38forkQdRn9p61UW8VT6Wa/+DWebO
Ll4OAv1RH4H2V6nrYY2ILJNnPzP8V4hjP9OGEAUQ8QKBgQDssSBUmb8Ztt6SsHNr
fgnbJBGAYizB1oAr6W1kLTQCq+BYirSYWMcJ/rakx+VCPmZ1fbbGYjPX5yVUsskx
jQ/GUT7D8lMIQNZiI9CqWR0+fJpVJ/zxwrPT2jqu8lEJxq2i/WB0nRHCgosGBTmw
UqhRGLkE5Ds14Q0zePZbdpAAyQKBgQDgL+yftcJEam8c3ipkrv02aT7vghoB0pAg
JNSSwhXED1CTboccY4daOfTYdt/PnkVmndENrUGMRyEbAY0DDK6hclG6/gE3fwn4
mL33IIzQ9BCoXxr3tcS0r4iQjbGKorUNJW1OwmkqyMZ4POF9BSkLXpTTcJaM5WxU
8JU9PmLX3wKBgFNpuLMX27j8MUQQ2xwuttp7w48zCgLlzRWsldiP9ZxbZhzOBQcL
glmLYmJ/79OAmisduqP/R7X2x7kpqK3FwKFrUGtNouVttB+x73+ZGC1FTD5mcUXi
D+3BIp002EpRsi+Wi7+M+w1JZCUjAkmZV6f8xndq11MNlNFm96sUBXvBAoGAJ9hc
tgYYARDprrfN0RdI6eLKzMbS2IAUHaJuJadZNv+B0rJSUTlfVSn32oFGRiBbNWHX
RhcFD2mU+LfN2DzozMkEvbdnf/WUUBrVqJagcILwcvx0TpJ/451PKGIGrB0/EJcW
Vmk3R+NnYvdvHElOgjbNPMdF+sTL/EzGOZxc9QECgYBNY4LAAKqrw47p+lcRi31O
X4fhdGWAIFyiUliPDkxzEl8857FbT5c6qhdes3Gyc9tSF1wh0X7lpCDquWXYLP1V
9WNvdon+YMRi9BKpO0SlE07lwFANBpz+wJkhONVJBMzvKbxEnMRPRJ4lWa0VAAGE
j2ZL3j2Nwefj3HrR/AkeFA==
-----END PRIVATE KEY-----
';

	protected $userPublicKey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz0dLlentXDUqMr7CEwbx
smKTiJ/byO86RIZOxRhsHTk3dnccSdopHoESeha9jo3HteSt834mrFz3p0wELhRF
3nBqD/VmPg0uV9S3wqY/RHgDgX1RinkA7Lec/xqF3lPmKlm2siU6erxUY+6OV+kA
wgl8tuSreLJzQ1kxz5THOOAS42esPlo9A9skpc9XRqbp3NL8GDf7hMo6/c7ZojfN
k2bWR4MzeNwyUr+7S7n7GYBrFp1ZcrV5rIuELggCnJvdl9mbSF5J+Z4jFg8SSXqa
hce6QtyC1zGSmKJQLbfdVby4hkI81EYauxwOEHarxcil6dLizdqymY6kNNp1Mwt+
FwIDAQAB
-----END PUBLIC KEY-----
';


	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->db = $this->createMock(IDBConnection::class);
		$this->session = $this->createMock(ISession::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->identityProof = $this->createMock(Manager::class);
	}

	protected function getController(array $methods = []) {
		if (empty($methods)) {
			return new PushController(
				'notifications',
				$this->request,
				$this->db,
				$this->session,
				$this->userSession,
				$this->tokenProvider,
				$this->identityProof
			);
		}

		return $this->getMockBuilder(PushController::class)
			->setConstructorArgs([
				'notifications',
				$this->request,
				$this->db,
				$this->session,
				$this->userSession,
				$this->tokenProvider,
				$this->identityProof,
			])
			->setMethods($methods)
			->getMock();
	}

	public function dataRegisterDevice() {
		return [
			'not authenticated' => [
				'',
				'',
				'',
				false,
				0,
				false,
				null,
				[],
				Http::STATUS_UNAUTHORIZED
			],
			'too short token hash' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e47',
				'',
				'',
				true,
				0,
				false,
				null,
				['message' => 'INVALID_PUSHTOKEN_HASH'],
				Http::STATUS_BAD_REQUEST,
			],
			'too long token hash' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e4722',
				'',
				'',
				true,
				0,
				false,
				null,
				['message' => 'INVALID_PUSHTOKEN_HASH'],
				Http::STATUS_BAD_REQUEST,
			],
			'invalid char in token hash' => [
				'rb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				'',
				'',
				true,
				0,
				false,
				null,
				['message' => 'INVALID_PUSHTOKEN_HASH'],
				Http::STATUS_BAD_REQUEST,
			],
			'device key invalid start' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				substr($this->devicePublicKey, 1),
				'',
				true,
				0,
				false,
				null,
				['message' => 'INVALID_DEVICE_KEY'],
				Http::STATUS_BAD_REQUEST,
			],
			'device key invalid end' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				substr($this->devicePublicKey, 0, -1),
				'',
				true,
				0,
				false,
				null,
				['message' => 'INVALID_DEVICE_KEY'],
				Http::STATUS_BAD_REQUEST,
			],
			'device key too much end' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				$this->devicePublicKey . "\n\n",
				'',
				true,
				0,
				false,
				null,
				['message' => 'INVALID_DEVICE_KEY'],
				Http::STATUS_BAD_REQUEST,
			],
			'device key without trailing new line' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				$this->devicePublicKey,
				'',
				true,
				0,
				false,
				null,
				['message' => 'INVALID_PROXY_SERVER'],
				Http::STATUS_BAD_REQUEST,
			],
			'device key with trailing new line' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				$this->devicePublicKey . "\n",
				'',
				true,
				0,
				false,
				null,
				['message' => 'INVALID_PROXY_SERVER'],
				Http::STATUS_BAD_REQUEST,
			],
			'invalid push proxy' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				$this->devicePublicKey,
				'localhost',
				true,
				0,
				false,
				null,
				['message' => 'INVALID_PROXY_SERVER'],
				Http::STATUS_BAD_REQUEST,
			],
			'using localhost' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				$this->devicePublicKey,
				'http://localhost/',
				true,
				23,
				false,
				null,
				['message' => 'INVALID_SESSION_TOKEN'],
				Http::STATUS_BAD_REQUEST,
			],
			'using localhost with port' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				$this->devicePublicKey,
				'http://localhost:8088/',
				true,
				23,
				false,
				null,
				['message' => 'INVALID_SESSION_TOKEN'],
				Http::STATUS_BAD_REQUEST,
			],
			'using production' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				$this->devicePublicKey,
				'https://push-notifications.nextcloud.com/',
				true,
				23,
				false,
				null,
				['message' => 'INVALID_SESSION_TOKEN'],
				Http::STATUS_BAD_REQUEST,
			],
			'created or updated' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				$this->devicePublicKey,
				'https://push-notifications.nextcloud.com/',
				true,
				23,
				true,
				true,
				[
					'publicKey' => $this->userPublicKey,
					'deviceIdentifier' => 'XUCEZ1EHvTUcVhIvrQQQ1XcP0ZD2BFdFqw4EYbOhBfiEgXgirurR4x/ve4GSSyfivvbQOdOkZUM+g4m+tSb0Ew==',
					'signature' => 'X9+J7NNLfG9Ft6C36zrYLVJ5aH5euIROzdV937hsU81jL7WvOwzBfc7bImzxU3Bnev5wEKwkw7Ts/2q/+UUkOxgtEZinp52s87S5obKtsVXsczHbsqg4p/ueoBPhF17VsP1e8kMtxZ4snk/iArX4Eu1cfaM3+OckmpO0MYXy0rUbYpQPAJo4VgRFKKjFvfEVOj8N74DTIJ+TjRsvvDhJbb9KpeFe3a6Rv9mIo0AqoK+deAbUkWY0aM+74noVXvPtNzExgK4mWJ02+JHEuQEUbCuQsgoBia0vC3fILbwVxHzrieWGEnE7vkRyFEzlkeo7ZSMawDPxsPN5HxwBs2SZig==',
				],
				Http::STATUS_CREATED,
			],
			'not updated' => [
				'bb9b52140661ee4f2c31e02ea50a8f67ba353bffc58aa981718f90bd2aa2bd8fc08cad4c0b3ed8f7eb9d79d6a577be75d084bbeb963da1ad74d9279e0014e472',
				$this->devicePublicKey,
				'https://push-notifications.nextcloud.com/',
				true,
				42,
				true,
				false,
				[
					'publicKey' => $this->userPublicKey,
					'deviceIdentifier' => 'x9vSImcGjhzR9BfZ/XbbUqqCCNC4bHKsX7vkQWNZRd1/MiY+OuF02fx8K08My0RpkNnwj/rQ/gVSU1oEdFwkww==',
					'signature' => 'GFpnv3MO7mcBef2RJ4Ayrl6RQakGM7AvlKhoTr3DUWnv+iBzwGy8YV34HIPoArz4tyqonHRlLsxPYq4ENPfGO99KrIS16z4RUq0wiCBGf+S8/K8lM9cE9EBKE9yrkTsSvZGICEusvxQ+cTfVr30bnavvi1wL1UuxxDBlJebda9FJ9HfaS24j4rT7K78oMguqDVM+4hhr6BMhcpUVV+kTpOaBpluw5pRDwUP3jJBmkkOa57WRKFcu0Lr/XIx/G0c8Si+BAfM//CTMstwp5XDFn4W9EYSStjNrvsULdV+tOKFwnowqts+UFzEDvmZ1g4qIMWUUPBF4/pjaiDqtMojgrA==',
				],
				Http::STATUS_OK,
			],
		];
	}

	/**
	 * @dataProvider dataRegisterDevice
	 *
	 * @param string $pushTokenHash
	 * @param string $devicePublicKey
	 * @param string $proxyServer
	 * @param bool $userIsValid
	 * @param int $tokenId
	 * @param bool $tokenIsValid
	 * @param bool $deviceCreated
	 * @param array $payload
	 * @param int $status
	 */
	public function testRegisterDevice($pushTokenHash, $devicePublicKey, $proxyServer, $userIsValid, $tokenId, $tokenIsValid, $deviceCreated, $payload, $status) {
		$controller = $this->getController([
			'savePushToken',
		]);

		$user = $this->createMock(IUser::class);
		if ($userIsValid) {
			$this->userSession->expects($this->any())
				->method('getUser')
				->willReturn($user);
		} else {
			$this->userSession->expects($this->any())
				->method('getUser')
				->willReturn(null);
		}

		$this->session->expects($tokenId > 0 ? $this->once() : $this->never())
			->method('get')
			->with('token-id')
			->willReturn($tokenId);

		if ($tokenIsValid) {
			$token = $this->createMock(IToken::class);
			$token->expects($this->once())
				->method('getId')
				->willReturn($tokenId);
			$this->tokenProvider->expects($this->any())
				->method('getTokenById')
				->with($tokenId)
				->willReturn($token);

			$key = $this->createMock(Key::class);
			$key->expects($this->once())
				->method('getPrivate')
				->willReturn($this->userPrivateKey);
			$key->expects($this->once())
				->method('getPublic')
				->willReturn($this->userPublicKey);

			$this->identityProof->expects($this->once())
				->method('getKey')
				->with($user)
				->willReturn($key);

			$controller->expects($this->once())
				->method('savePushToken')
				->with($user, $token, $this->anything(), $devicePublicKey, $pushTokenHash, $proxyServer)
				->willReturn($deviceCreated);
		} else {
			$controller->expects($this->never())
				->method('savePushToken');

			$this->tokenProvider->expects($this->any())
				->method('getTokenById')
				->with($tokenId)
				->willThrowException(new InvalidTokenException());
		}

		$response = $controller->registerDevice($pushTokenHash, $devicePublicKey, $proxyServer);
		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame($status, $response->getStatus());
		$this->assertSame($payload, $response->getData());
	}

	public function dataRemoveDevice() {
		return [
			'not authenticated' => [
				false,
				0,
				false,
				null,
				[],
				Http::STATUS_UNAUTHORIZED
			],
			'invalid token' => [
				true,
				23,
				false,
				null,
				['message' => 'INVALID_SESSION_TOKEN'],
				Http::STATUS_BAD_REQUEST,
			],
			'using production' => [
				true,
				23,
				false,
				null,
				['message' => 'INVALID_SESSION_TOKEN'],
				Http::STATUS_BAD_REQUEST,
			],
			'created or updated' => [
				true,
				23,
				true,
				true,
				[],
				Http::STATUS_ACCEPTED,
			],
			'not updated' => [
				true,
				42,
				true,
				false,
				[],
				Http::STATUS_OK,
			],
		];
	}


	/**
	 * @dataProvider dataRemoveDevice
	 *
	 * @param bool $userIsValid
	 * @param int $tokenId
	 * @param bool $tokenIsValid
	 * @param bool $deviceDeleted
	 * @param array $payload
	 * @param int $status
	 */
	public function testRemoveDevice($userIsValid, $tokenId, $tokenIsValid, $deviceDeleted, $payload, $status) {
		$controller = $this->getController([
			'deletePushToken',
		]);

		$user = $this->createMock(IUser::class);
		if ($userIsValid) {
			$this->userSession->expects($this->any())
				->method('getUser')
				->willReturn($user);
		} else {
			$this->userSession->expects($this->any())
				->method('getUser')
				->willReturn(null);
		}

		$this->session->expects($tokenId > 0 ? $this->once() : $this->never())
			->method('get')
			->with('token-id')
			->willReturn($tokenId);

		if ($tokenIsValid) {
			$token = $this->createMock(IToken::class);
			$this->tokenProvider->expects($this->any())
				->method('getTokenById')
				->with($tokenId)
				->willReturn($token);

			$controller->expects($this->once())
				->method('deletePushToken')
				->with($user, $token)
				->willReturn($deviceDeleted);
		} else {
			$controller->expects($this->never())
				->method('deletePushToken');

			$this->tokenProvider->expects($this->any())
				->method('getTokenById')
				->with($tokenId)
				->willThrowException(new InvalidTokenException());
		}

		$response = $controller->removeDevice();
		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame($status, $response->getStatus());
		$this->assertSame($payload, $response->getData());
	}
}
