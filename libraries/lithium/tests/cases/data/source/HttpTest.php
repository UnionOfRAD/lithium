<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

class SocketMock extends \lithium\util\Socket {

	public function open() {
		return true;
	}

	public function close() {
		return true;
	}

	public function eof() {
		return true;
	}

	public function read() {
		return join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'',
			'Test!'
		));
	}

	public function write($data) {
		return $data;
	}

	public function timeout($time) {
		return true;
	}

	public function encoding($charset) {
		return true;
	}
}

class HttpMock extends \lithium\data\source\Http {

	public $testRequest = null;

	public function response($message) {
		$this->response = new $this->_classes['response'](compact('message'));
		return $this->_response->body;
	}

	protected function _send($path = null) {
		$this->testRequest = $this->request;
		return parent::_send($path);
	}
}

class HttpTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'socket' => '\lithium\tests\cases\data\source\SocketMock',
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2
	);

	public function testAllMethodsNoConnection() {
		$http = new HttpMock(array('protocol' => null));
		$this->assertFalse($http->connect());
		$this->assertTrue($http->disconnect());
		$this->assertFalse($http->get());
		$this->assertFalse($http->post());
		$this->assertFalse($http->put());
		$this->assertFalse($http->del());
	}

	public function testConnect() {
		$http = new HttpMock($this->_testConfig);
		$result = $http->connect();
		$this->assertTrue($result);
	}

	public function testDisconnect() {
		$http = new HttpMock($this->_testConfig);
		$result = $http->connect();
		$this->assertTrue($result);

		$result = $http->disconnect();
		$this->assertTrue($result);
	}

	public function testEntities() {
		$http = new HttpMock($this->_testConfig);
		$result = $http->entities();
	}

	public function testDescribe() {
		$http = new HttpMock($this->_testConfig);
		$result = $http->describe(null, null);
	}

	public function testGet() {
		$http = new HttpMock($this->_testConfig);
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
		$http = new HttpMock($this->_testConfig);
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
		$http = new HttpMock($this->_testConfig);
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

	public function testPut() {
		$http = new HttpMock($this->_testConfig);
		$result = $http->put();
		$this->assertEqual('Test!', $result);
	}

	public function testDelete() {
		$http = new HttpMock($this->_testConfig);
		$result = $http->delete(null);
		$this->assertEqual('Test!', $result);
	}

	public function testCreate() {
		$http = new HttpMock($this->_testConfig);
		$result = $http->create(null);
		$this->assertEqual('Test!', $result);
	}

	public function testRead() {
		$http = new HttpMock($this->_testConfig);
		$result = $http->read(null);
		$this->assertEqual('Test!', $result);
	}

	public function testUpdate() {
		$http = new HttpMock($this->_testConfig);
		$result = $http->update(null);
		$this->assertEqual('Test!', $result);
	}
}

?>