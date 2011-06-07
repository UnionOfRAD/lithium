<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use lithium\net\http\Media;
use lithium\net\http\Service;

class ServiceTest extends \lithium\test\Unit {

	public $request = null;

	protected $_testConfig = array(
		'classes' => array('response' => 'lithium\net\http\Response'),
		'socket' => 'lithium\tests\mocks\net\http\MockSocket',
		'host' => 'localhost',
		'port' => 80,
		'timeout' => 2
	);

	public function setUp() {
		Media::reset();
	}

	public function testAllMethodsNoConnection() {
		$http = new Service(array('socket' => false));
		$this->assertFalse($http->get());
		$this->assertFalse($http->post());
		$this->assertFalse($http->put());
		$this->assertFalse($http->delete());
	}

	public function testRequestPath() {
		$http = new Service(array('host' => 'localhost') + $this->_testConfig);
		$result = $http->get();

		$expected = '/';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);

		$http = new Service(array('host' => 'localhost/base/path/') + $this->_testConfig);
		$result = $http->get();

		$expected = '/base/path/';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);

		$http = new Service(array('host' => 'localhost/base/path') + $this->_testConfig);
		$result = $http->get('/somewhere');

		$expected = '/base/path/somewhere';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);

		$http = new Service(array('host' => 'localhost/base/path/') + $this->_testConfig);
		$result = $http->get('/somewhere');

		$expected = '/base/path/somewhere';
		$result = $http->last->request->path;
		$this->assertEqual($expected, $result);
	}

	public function testHead() {
		$http = new Service($this->_testConfig);
		$this->assertEqual('', $http->head());
		$this->assertEqual('HTTP/1.1', $http->last->response->protocol);
		$this->assertEqual('200', $http->last->response->status['code']);
		$this->assertEqual('OK', $http->last->response->status['message']);
		$this->assertEqual('text/html', $http->last->response->type);
		$this->assertEqual('UTF-8', $http->last->response->encoding);
		$this->assertEqual('', $http->last->response->body());
	}

	public function testGet() {
		$http = new Service($this->_testConfig);
		$this->assertEqual('', $http->get());
		$this->assertEqual('HTTP/1.1', $http->last->response->protocol);
		$this->assertEqual('200', $http->last->response->status['code']);
		$this->assertEqual('OK', $http->last->response->status['message']);
		$this->assertEqual('text/html', $http->last->response->type);
		$this->assertEqual('UTF-8', $http->last->response->encoding);
	}

	public function testGetPath() {
		$http = new Service($this->_testConfig);
		$this->assertEqual('', $http->get('search.json'));
		$this->assertEqual('HTTP/1.1', $http->last->response->protocol);
		$this->assertEqual('200', $http->last->response->status['code']);
		$this->assertEqual('OK', $http->last->response->status['message']);
		$this->assertEqual('text/html', $http->last->response->type);
		$this->assertEqual('UTF-8', $http->last->response->encoding);
	}

	public function testPost() {
		$http = new Service($this->_testConfig);
		$http->post('update.xml', array('status' => 'cool'));
		$expected = join("\r\n", array(
			'POST /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);

		$expected = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		));
		$result = (string) $http->last->response;
		$this->assertEqual($expected, $result);
	}

	public function testPut() {
		$http = new Service($this->_testConfig);
		$http->put('update.xml', array('status' => 'cool'));
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

		$expected = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		));
		$result = (string) $http->last->response;
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$http = new Service($this->_testConfig);
		$http->delete('posts/1');
		$expected = join("\r\n", array(
			'DELETE /posts/1 HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);

		$expected = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		));
		$result = (string) $http->last->response;
		$this->assertEqual($expected, $result);
	}

	public function testJsonPost() {
		$http = new Service($this->_testConfig);
		$http->post('update.xml', array('status' => 'cool'), array('type' => 'json'));
		$expected = join("\r\n", array(
			'POST /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/json',
			'Content-Length: 17',
			'', '{"status":"cool"}'
		));
		$result = (string) $http->last->request;
		$this->assertEqual($expected, $result);

		$expected = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/json',
			'Content-Length: 17',
			'', '{"status":"cool"}'
		));
		$result = (string) $http->last->response;
		$this->assertEqual($expected, $result);
	}

	public function testConnection() {
		$http = new Service($this->_testConfig);
		$connection = $http->connection();
		$this->assertEqual('lithium\tests\mocks\net\http\MockSocket', get_class($connection));

		$connection = $http->connection(array('scheme' => 'https'));
		$config = $connection->config();
		$this->assertEqual('https', $config['scheme']);
	}

	public function testSendConfiguringConnection() {
		$http = new Service($this->_testConfig);
		$result = $http->send('get', 'some-path/stuff', array(), array('someKey' => 'someValue'));
		$config = array_pop($http->connection->configs);
		$this->assertEqual('someValue', $config['someKey']);

	}
}

?>