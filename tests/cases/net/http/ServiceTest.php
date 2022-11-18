<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\net\http;

use lithium\net\http\Media;
use lithium\net\http\Service;

class ServiceTest extends \lithium\test\Unit {

	public $request = null;

	protected $_testConfig = [
		'classes' => ['response' => 'lithium\net\http\Response'],
		'socket' => 'lithium\tests\mocks\net\http\MockSocket',
		'host' => 'localhost',
		'port' => 80,
		'timeout' => 2
	];

	public function setUp() {
		Media::reset();
	}

	public function testAllMethodsNoConnection() {
		$http = new Service([AUTO_INIT_CLASS => false]);
		$this->assertEmpty($http->get());
		$this->assertEmpty($http->post());
		$this->assertEmpty($http->put());
		$this->assertEmpty($http->delete());
	}

	public function testRequestPath() {
		$http = new Service(['host' => 'localhost'] + $this->_testConfig);
		$result = $http->get();

		$expected = '/';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);

		$http = new Service(['host' => 'localhost/base/path/'] + $this->_testConfig);
		$result = $http->get();

		$expected = '/base/path/';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);

		$http = new Service(['host' => 'localhost/base/path'] + $this->_testConfig);
		$result = $http->get('/somewhere');

		$expected = '/base/path/somewhere';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);

		$http = new Service(['host' => 'localhost/base/path/'] + $this->_testConfig);
		$result = $http->get('/somewhere');

		$expected = '/base/path/somewhere';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);
	}

	public function testReturnHandlers() {
		$http = new Service($this->_testConfig);
		$result = $http->get(null, null, ['return' => 'headers']);
		$this->assertEqual('localhost:80', $result['Host']);

		$result = $http->get(null, null, ['return' => 'response']);
		$this->assertEqual($result, $http->last->response);

		$result = $http->get(null, null, ['return' => 'body']);
		$this->assertEqual($result, $http->last->response->body());
	}

	public function testHead() {
		$http = new Service($this->_testConfig);
		$result = $http->head();
		$this->assertEqual('localhost:80', $result['Host']);
		$this->assertEqual('HTTP/1.1', $http->last->response->protocol);
		$this->assertEqual('200', $http->last->response->status['code']);
		$this->assertEqual('OK', $http->last->response->status['message']);
		$this->assertEqual(null, $http->last->response->type());
		$this->assertEqual('UTF-8', $http->last->response->encoding);
		$this->assertEqual('', $http->last->response->body());
	}

	public function testHeadPath() {
		$http = new Service($this->_testConfig);
		$expected = '/somewhere';
		$result = $http->head('/somewhere');
		$this->assertEqual($expected, $http->last->request->path);
	}

	public function testHeadQueryString() {
		$http = new Service($this->_testConfig);
		$expected = ['foo' => 'bar'];
		$result = $http->head('/', $expected);
		$this->assertEqual($expected, $http->last->request->query);
	}

	public function testGet() {
		$http = new Service($this->_testConfig);
		$this->assertEqual('', $http->get());
		$this->assertEqual('HTTP/1.1', $http->last->response->protocol);
		$this->assertEqual('200', $http->last->response->status['code']);
		$this->assertEqual('OK', $http->last->response->status['message']);
		$this->assertEqual(null, $http->last->response->type());
		$this->assertEqual('UTF-8', $http->last->response->encoding);
	}

	public function testGetPath() {
		$http = new Service($this->_testConfig);
		$this->assertEqual('', $http->get('search.json'));
		$this->assertEqual('HTTP/1.1', $http->last->response->protocol);
		$this->assertEqual('200', $http->last->response->status['code']);
		$this->assertEqual('OK', $http->last->response->status['message']);
		$this->assertEqual(null, $http->last->response->type());
		$this->assertEqual('UTF-8', $http->last->response->encoding);
	}

	public function testPost() {
		$http = new Service($this->_testConfig);
		$http->post('update.xml', ['status' => 'cool']);
		$expected = join("\r\n", [
			'POST /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);

		$expected = join("\r\n", [
			'HTTP/1.1 200 OK',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
			'Content-Length: 11',
			'', 'status=cool'
		]);
		$result = (string) $http->last->response;
		$this->assertEqual($expected, $result);
	}

	public function testPut() {
		$http = new Service($this->_testConfig);
		$http->put('update.xml', ['status' => 'cool']);
		$expected = join("\r\n", [
			'PUT /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);

		$expected = join("\r\n", [
			'HTTP/1.1 200 OK',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
			'Content-Length: 11',
			'', 'status=cool'
		]);
		$result = (string) $http->last->response;
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$http = new Service($this->_testConfig);
		$http->delete('posts/1');
		$expected = join("\r\n", [
			'DELETE /posts/1 HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);

		$expected = join("\r\n", [
			'HTTP/1.1 200 OK',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $http->last->response;
		$this->assertEqual($expected, $result);
	}

	public function testJsonPost() {
		$http = new Service($this->_testConfig);
		$data = ['status' => ['cool', 'awesome']];
		$http->post('update.xml', $data, ['type' => 'json']);
		$expected = join("\r\n", [
			'POST /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/json',
			'Content-Length: 29',
			'', '{"status":["cool","awesome"]}'
		]);
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);

		$expected = join("\r\n", [
			'HTTP/1.1 200 OK',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/json;charset=UTF-8',
			'Content-Length: 29',
			'', '{"status":["cool","awesome"]}'
		]);
		$result = (string) $http->last->response;
		$this->assertEqual($expected, $result);
	}

	public function testConnection() {
		$http = new Service($this->_testConfig);
		$connection = $http->connection;
		$this->assertEqual('lithium\tests\mocks\net\http\MockSocket', get_class($connection));

		$http->connection->open(['scheme' => 'https']);
		$config = $http->connection->config();
		$this->assertEqual('https', $config['scheme']);
	}

	public function testSendConfiguringConnection() {
		$http = new Service($this->_testConfig);
		$result = $http->send('get', 'some-path/stuff', [], ['someKey' => 'someValue']);
		$config = $http->connection->config();
		$this->assertEqual('someValue', $config['someKey']);
	}

	public function testPatchMethod() {
		$http = new Service($this->_testConfig);
		$response = $http->patch(
			'some-path/stuff',
			['someData' => 'someValue'],
			['return' => 'response']
		);
		$result = $http->last->request;
		$this->assertEqual('PATCH', $result->method);
		$this->assertEqual('lithium\net\http\Response', get_class($response));
		$this->assertEqual('someData=someValue', $result->body());
	}

	public function testPatchWithJson() {
		$http = new Service($this->_testConfig);
		$response = $http->patch(
			'some-path/stuff',
			['someData' => 'someValue'],
			['return' => 'response', 'type' => 'json']
		);
		$result = $http->last->request;
		$this->assertEqual('{"someData":"someValue"}', $result->body());
		$this->assertEqual('application/json', $result->headers['Content-Type']);
	}

	public function testMagicMethod() {
		$http = new Service($this->_testConfig);
		$response = $http->magic('some-path/stuff');
		$expected = "http://localhost:80/some-path/stuff";
		$result = $http->last->request;
		$this->assertEqual($expected, $result->to('url'));
		$this->assertEqual('MAGIC', $result->method);
	}

	public function testDigestAuth() {
		$this->_testConfig += ['auth' => 'digest', 'username' => 'gwoo', 'password' => 'li3'];
		$http = new Service($this->_testConfig);
		$response = $http->get('/http_auth/', [], ['return' => 'response']);
		$this->assertEqual('success', $response->body());
	}
}

?>