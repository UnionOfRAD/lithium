<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use lithium\data\source\Http;
use lithium\data\Connections;
use lithium\data\model\Query;

class HttpTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\source\MockHttpModel';

	protected $_testConfig = array(
		'classes' => array('response' => 'lithium\net\http\Response'),
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2,
		'socket' => 'lithium\tests\mocks\data\source\http\adapter\MockSocket'
	);

	public function setUp() {
		$this->_configs = Connections::config();
		Connections::reset();

		Connections::config(array(
			'mock-http-connection' => array('type' => 'Http')
		));

		Connections::config(array(
			'mock-http-conn' => array(
				'type' => 'Http',
				'methods' => array(
					'something' => array('method' => 'get'),
					'do' => array('method' => 'post')
				)
			)
		));
	}

	public function tearDown() {
		Connections::reset();
		Connections::config($this->_configs);
		unset($this->query);
	}

	public function testAllMethodsNoConnection() {
		$http = new Http(array('socket' => false));
		$this->assertTrue($http->connect());
		$this->assertTrue($http->disconnect());
		$this->assertFalse($http->get());
		$this->assertFalse($http->post());
		$this->assertFalse($http->put());
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
		$result = $http->describe(null, array());
	}

	public function testGet() {
		$http = new Http($this->_testConfig);
		$result = $http->get();

		$result = $http->last->response->protocol;
		$this->assertEqual('HTTP/1.1', $result);

		$result = $http->last->response->status['code'];
		$this->assertEqual('200', $result);

		$result = $http->last->response->status['message'];
		$this->assertEqual('OK', $result);

		$result = $http->last->response->type;
		$this->assertEqual('text/html', $result);

		$result = $http->last->response->encoding;
		$this->assertEqual('UTF-8', $result);
	}

	public function testGetPath() {
		$http = new Http($this->_testConfig);
		$result = $http->get('search.json');

		$result = $http->last->response->protocol;
		$this->assertEqual('HTTP/1.1', $result);

		$result = $http->last->response->status['code'];
		$this->assertEqual('200', $result);

		$result = $http->last->response->status['message'];
		$this->assertEqual('OK', $result);

		$result = $http->last->response->type;
		$this->assertEqual('text/html', $result);

		$result = $http->last->response->encoding;
		$this->assertEqual('UTF-8', $result);
	}

	public function testPost() {
		$http = new Http($this->_testConfig);
		$http->post('add.xml', array('status' => 'cool'));
		$expected = join("\r\n", array(
			'POST /add.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testPut() {
		$http = new Http($this->_testConfig);
		$result = $http->put('update.xml', array('status' => 'cool'));
		$expected = join("\r\n", array(
			'PUT /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCreate() {
		$http = new Http($this->_testConfig);
		$result = $http->create(null);
		$expected = join("\r\n", array(
			'POST / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testRead() {
		$http = new Http($this->_testConfig);
		$result = $http->read(null);
		$expected = join("\r\n", array(
			'GET / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testUpdate() {
		$http = new Http($this->_testConfig);
		$result = $http->update(null);
		$expected = join("\r\n", array(
			'PUT / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$http = new Http($this->_testConfig);
		$result = $http->delete(null);
		$expected = join("\r\n", array(
			'DELETE / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCreateWithModel() {
		$model = $this->_model;
		$model::config(array('key' => 'id'));
		$http = new Http($this->_testConfig);
		$query = new Query(compact('model') + array('data' => array('title' => 'Test Title')));
		$result = $http->create($query);

		$expected = join("\r\n", array(
			'POST /posts HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 16',
			'', 'title=Test+Title'
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithModel() {
		$http = new Http($this->_testConfig);
		$query = new Query(array('model' => $this->_model));

		$result = $http->read($query);
		$expected = join("\r\n", array(
			'GET /posts HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithModelConditions() {
		$http = new Http($this->_testConfig);
		$query = new Query(array(
			'model' => $this->_model,
			'conditions' => array('page' => 2)
		));

		$result = $http->read($query);
		$expected = join("\r\n", array(
			'GET /posts?page=2 HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'', ''
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testUpdateWithModel() {
		$http = new Http($this->_testConfig);
		$query = new Query(array(
			'model' => $this->_model,
			'data' => array('id' => '1', 'title' => 'Test Title')
		));

		$result = $http->update($query);
		$expected = join("\r\n", array(
			'PUT /posts/1 HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 21',
			'', 'id=1&title=Test+Title'
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testDeleteWithModel() {
		$http = new Http($this->_testConfig);
		$query = new Query(array('model' => $this->_model, 'data' => array('id' => '1')));

		$result = $http->delete($query);
		$expected = join("\r\n", array(
			'DELETE /posts/1 HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCustomGetMethod() {
		$conn = Connections::get('mock-http-conn');

		$result = $conn->something();
		$expected = join("\r\n", array(
			'GET /something HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		));
		$result = (string) $conn->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCustomPostMethod() {
		$conn = Connections::get('mock-http-conn');

		$result = $conn->do(array('title' => 'sup'));
		$expected = join("\r\n", array(
			'POST /do HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 9',
			'', 'title=sup'
		));
		$result = (string) $conn->last->request;
		$this->assertEqual($expected, $result);
	}
}

?>