<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use \lithium\tests\mocks\net\http\MockService;

class ServiceTest extends \lithium\test\Unit {

	public $request = null;

	protected $_testConfig = array(
		'classes' => array(
			'socket' => '\lithium\tests\mocks\net\http\MockSocket'
		),
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2
	);

	public function testAllMethodsNoConnection() {
		$http = new MockService(array('classes' => array('socket' => false)));
		$this->assertFalse($http->connect());
		$this->assertTrue($http->disconnect());
		$this->assertFalse($http->get());
		$this->assertFalse($http->post());
		$this->assertFalse($http->put());
		$this->assertFalse($http->delete());
	}

	public function testConnect() {
		$http = new MockService($this->_testConfig);
		$result = $http->connect();
		$this->assertTrue($result);
	}

	public function testDisconnect() {
		$http = new MockService($this->_testConfig);
		$result = $http->connect();
		$this->assertTrue($result);

		$result = $http->disconnect();
		$this->assertTrue($result);
	}

	public function testPath() {
		$http = new MockService(array('host' => 'localhost') + $this->_testConfig);
		$result = $http->get();

		$expected = '/';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);

		$http = new MockService(array('host' => 'localhost/base/path/') + $this->_testConfig);
		$result = $http->get();

		$expected = '/base/path/';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);

		$http = new MockService(array('host' => 'localhost/base/path') + $this->_testConfig);
		$result = $http->get('/somewhere');

		$expected = '/base/path/somewhere';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);

		$http = new MockService(array('host' => 'localhost/base/path/') + $this->_testConfig);
		$result = $http->get('/somewhere');

		$expected = '/base/path/somewhere';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);
	}

	public function testGet() {
		$http = new MockService($this->_testConfig);
		$result = $http->get();
		$this->assertEqual('Test!', $result);

		$expected = 'HTTP/1.1';
		$result = $http->last->response->protocol;
		$this->assertEqual($expected, $result);

		$expected = '200';
		$result = $http->last->response->status['code'];
		$this->assertEqual($expected, $result);

		$expected = 'OK';
		$result = $http->last->response->status['message'];
		$this->assertEqual($expected, $result);

		$expected = 'text/html';
		$result = $http->last->response->type;
		$this->assertEqual($expected, $result);

		$expected = 'UTF-8';
		$result = $http->last->response->charset;
		$this->assertEqual($expected, $result);
	}

	public function testGetPath() {
		$http = new MockService($this->_testConfig);
		$result = $http->get('search.json');
		$this->assertEqual('Test!', $result);

		$expected = 'HTTP/1.1';
		$result = $http->last->response->protocol;
		$this->assertEqual($expected, $result);

		$expected = '200';
		$result = $http->last->response->status['code'];
		$this->assertEqual($expected, $result);

		$expected = 'OK';
		$result = $http->last->response->status['message'];
		$this->assertEqual($expected, $result);

		$expected = 'text/html';
		$result = $http->last->response->type;
		$this->assertEqual($expected, $result);

		$expected = 'UTF-8';
		$result = $http->last->response->charset;
		$this->assertEqual($expected, $result);
	}

	public function testPost() {
		$http = new MockService($this->_testConfig);
		$http->post('update.xml', array('status' => 'cool'));
		$expected = join("\r\n", array(
			'POST /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testPut() {
		$http = new MockService($this->_testConfig);
		$http->put('update.xml', array('status' => 'cool'));
		$expected = join("\r\n", array(
			'PUT /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$http = new MockService($this->_testConfig);
		$http->delete('posts/1');
		$expected = join("\r\n", array(
			'DELETE /posts/1 HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'', ''
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testJsonPost() {
		$http = new MockService($this->_testConfig);
		$http->post('update.xml', array('status' => 'cool'), array('type' => 'json'));
		$expected = join("\r\n", array(
			'POST /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'Content-Type: application/json',
			'Content-Length: 17',
			'', '{"status":"cool"}'
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}
}

?>