<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use \lithium\data\source\Http;

class HttpTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'classes' => array(
			'socket' => '\lithium\tests\mocks\data\source\http\adapter\MockSocket'
		),
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2,
	);

	public function testAllMethodsNoConnection() {
		$http = new Http(array('classes' => array('socket' => false)));
		$this->assertFalse($http->connect());
		$this->assertTrue($http->disconnect());
		$this->assertFalse($http->get());
		$this->assertFalse($http->post());
		$this->assertFalse($http->put());
		$this->assertFalse($http->delete());
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

	public function testEntities() {
		$http = new Http($this->_testConfig);
		$result = $http->entities();
	}

	public function testDescribe() {
		$http = new Http($this->_testConfig);
		$result = $http->describe(null, null);
	}

	public function testGet() {
		$http = new Http($this->_testConfig);
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
		$http = new Http($this->_testConfig);
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
		$http = new Http($this->_testConfig);
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
		$http = new Http($this->_testConfig);
		$result = $http->put();
		$this->assertEqual('Test!', $result);
	}

	public function testDelete() {
		$http = new Http($this->_testConfig);
		$result = $http->delete(null);
		$this->assertEqual('Test!', $result);
	}

	public function testCreate() {
		$http = new Http($this->_testConfig);
		$result = $http->create(null);
		$this->assertEqual('Test!', $result);
	}

	public function testRead() {
		$http = new Http($this->_testConfig);
		$result = $http->read(null);
		$this->assertEqual('Test!', $result);
	}

	public function testUpdate() {
		$http = new Http($this->_testConfig);
		$result = $http->update(null);
		$this->assertEqual('Test!', $result);
	}
}

?>