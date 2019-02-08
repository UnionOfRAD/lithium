<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\source;

use lithium\data\source\Http;
use lithium\data\model\Query;

class HttpTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\source\MockHttpModel';

	protected $_testConfig = [
		'classes' => ['response' => 'lithium\net\http\Response'],
		'persistent' => false,
		'scheme' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2,
		'socket' => 'lithium\tests\mocks\data\source\http\adapter\MockSocket'
	];

	protected $_connectionConfig = [
		'methods' => [
			'something' => ['method' => 'get'],
			'do' => ['method' => 'post']
		]
	];

	public function setUp() {
		$model = $this->_model;
		$model::$connection = new Http($this->_connectionConfig);
	}

	public function testAllMethodsNoConnection() {
		$http = new Http(['socket' => false]);
		$this->assertNotEmpty($http->connect());
		$this->assertNotEmpty($http->disconnect());
		$this->assertEmpty($http->get());
		$this->assertEmpty($http->post());
		$this->assertEmpty($http->put());
	}

	public function testConnect() {
		$http = new Http();
		$result = $http->connect();
		$this->assertTrue($result);
	}

	public function testDisconnect() {
		$http = new Http($this->_testConfig);
		$result = $http->connect();
		$this->assertTrue($result);

		$result = $http->disconnect();
		$this->assertTrue($result);
	}

	public function testSources() {
		$http = new Http($this->_testConfig);
		$result = $http->sources();
	}

	public function testDescribe() {
		$http = new Http($this->_testConfig);
		$result = $http->describe(null, []);
	}

	public function testCreate() {
		$http = new Http($this->_testConfig);
		$result = $http->create(null);
		$expected = join("\r\n", [
			'POST / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testRead() {
		$http = new Http($this->_testConfig);
		$result = $http->read(null);
		$expected = join("\r\n", [
			'GET / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testUpdate() {
		$http = new Http($this->_testConfig);
		$result = $http->update(null);
		$expected = join("\r\n", [
			'PUT / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$http = new Http($this->_testConfig);
		$result = $http->delete(null);
		$expected = join("\r\n", [
			'DELETE / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCreateWithModel() {
		$model = $this->_model;
		$model::config(['meta' => ['key' => 'id']]);
		$http = new Http($this->_testConfig);
		$query = new Query(compact('model') + ['data' => ['title' => 'Test Title']]);
		$result = $http->create($query);

		$expected = join("\r\n", [
			'POST /posts HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 16',
			'', 'title=Test+Title'
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithModel() {
		$http = new Http($this->_testConfig);
		$query = new Query(['model' => $this->_model]);

		$result = $http->read($query);
		$expected = join("\r\n", [
			'GET /posts HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithModelConditions() {
		$http = new Http($this->_testConfig);
		$query = new Query([
			'model' => $this->_model,
			'conditions' => ['page' => 2]
		]);

		$result = $http->read($query);
		$expected = join("\r\n", [
			'GET /posts?page=2 HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testUpdateWithModel() {
		$http = new Http($this->_testConfig);
		$query = new Query([
			'model' => $this->_model,
			'data' => ['id' => '1', 'title' => 'Test Title']
		]);

		$result = $http->update($query);
		$expected = join("\r\n", [
			'PUT /posts/1 HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 16',
			'', 'title=Test+Title'
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testDeleteWithModel() {
		$http = new Http($this->_testConfig);
		$query = new Query(['model' => $this->_model, 'data' => ['id' => '1']]);

		$result = $http->delete($query);
		$expected = join("\r\n", [
			'DELETE /posts/1 HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCustomActionWithoutMethod() {
		$http = new Http($this->_testConfig);

		$result = $http->something();
		$expected = join("\r\n", [
			'GET /something HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCustomGetMethod() {
		$config = $this->_testConfig + ['methods' => [
			'something' => ['method' => 'get', 'path' => '/something']
		]];
		$http = new Http($config);

		$result = $http->something();
		$expected = join("\r\n", [
			'GET /something HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCustomGetMethodWithModel() {
		$config = $this->_testConfig + ['methods' => [
			'something' => ['method' => 'get', 'path' => '/something']
		]];
		$http = new Http($config);
		$query = new Query(['model' => $this->_model]);

		$result = $http->something($query);
		$expected = join("\r\n", [
			'GET /something HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCustomPostMethod() {
		$config = $this->_testConfig + ['methods' => [
			'do' => ['method' => 'post', 'path' => '/do']
		]];
		$http = new Http($config);

		$result = $http->do(['title' => 'sup']);
		$expected = join("\r\n", [
			'POST /do HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 9',
			'', 'title=sup'
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCustomPostMethodWithModel() {
		$config = $this->_testConfig + ['methods' => [
			'do' => ['method' => 'post', 'path' => '/do']
		]];
		$http = new Http($config);
		$query = new Query(['model' => $this->_model, 'data' => ['title' => 'sup']]);

		$result = $http->do($query);
		$expected = join("\r\n", [
			'POST /do HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 9',
			'', 'title=sup'
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testSendWithQueryObject() {
		$http = new Http($this->_testConfig);
		$query = new Query([
			'model' => $this->_model,
			'data' => ['title' => 'sup'],
			'method' => 'post',
			'path' => '/some/resource/path'
		]);
		$result = $http->send($query);
		$expected = join("\r\n", [
			'POST /some/resource/path HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 9',
			'', 'title=sup'
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testRespondsTo() {
		$http = new Http();
		$this->assertFalse($http->respondsTo('refactor'));
		$this->assertTrue($http->respondsTo('create'));
		$this->assertTrue($http->respondsTo('read'));
	}

	public function testRespondsToParentCall() {
		$http = new Http();
		$this->assertTrue($http->respondsTo('invokeMethod'));
		$this->assertFalse($http->respondsTo('fooBarBaz'));
	}
}

?>