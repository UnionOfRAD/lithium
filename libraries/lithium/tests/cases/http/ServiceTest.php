<?php

namespace lithium\tests\cases\http;

use \lithium\tests\mocks\http\MockService;

class ServiceTest extends \lithium\test\Unit {

	public $request = null;

	protected $_testConfig = array(
		'classes' => array(
			'socket' => '\lithium\tests\mocks\socket\MockSocket'
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
		$http = new MockService(array('protocol' => null));
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

	public function testGet() {
		$http = new MockService($this->_testConfig);
		$result = $http->get();
		$this->assertEqual('Test!', $result);

		$expected = 'HTTP/1.1';
		$result = $http->response->protocol;
		$this->assertEqual($expected, $result);

		$expected = '200';
		$result = $http->response->status['code'];
		$this->assertEqual($expected, $result);

		$expected = 'OK';
		$result = $http->response->status['message'];
		$this->assertEqual($expected, $result);

		$expected = 'text/html';
		$result = $http->response->type;
		$this->assertEqual($expected, $result);

		$expected = 'UTF-8';
		$result = $http->response->charset;
		$this->assertEqual($expected, $result);
	}

	public function testGetPath() {
		$http = new MockService($this->_testConfig);
		$result = $http->get('search.json');
		$this->assertEqual('Test!', $result);

		$expected = 'HTTP/1.1';
		$result = $http->response->protocol;
		$this->assertEqual($expected, $result);

		$expected = '200';
		$result = $http->response->status['code'];
		$this->assertEqual($expected, $result);

		$expected = 'OK';
		$result = $http->response->status['message'];
		$this->assertEqual($expected, $result);

		$expected = 'text/html';
		$result = $http->response->type;
		$this->assertEqual($expected, $result);

		$expected = 'UTF-8';
		$result = $http->response->charset;
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
		$result = (string)$http->testRequest;
		$this->assertEqual($expected, $result);
	}
}

?>