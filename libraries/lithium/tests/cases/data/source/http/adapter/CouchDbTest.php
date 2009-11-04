<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\http\adapter;

use \lithium\data\source\http\adapter\CouchDb;

class CouchDbTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'classes' => array(
			'service' => 'lithium\tests\mocks\http\MockService',
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
		$couchdb = new CouchDb(array('protocol' => null));
		$this->assertFalse($couchdb->connect());
		$this->assertTrue($couchdb->disconnect());
		$this->assertFalse($couchdb->get());
		$this->assertFalse($couchdb->post());
		$this->assertFalse($couchdb->put());
		$this->assertFalse($couchdb->delete());
	}

	public function testConnect() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->connect();
		$this->assertTrue($result);
	}

	public function testDisconnect() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->connect();
		$this->assertTrue($result);

		$result = $couchdb->disconnect();
		$this->assertTrue($result);
	}

	public function testEntities() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->entities();
	}

	public function testDescribe() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->describe(null, null);
	}

	public function testGet() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->get();
		$this->assertEqual('Test!', $result);

		$expected = 'HTTP/1.1';
		$result = $couchdb->response->protocol;
		$this->assertEqual($expected, $result);

		$expected = '200';
		$result = $couchdb->response->status['code'];
		$this->assertEqual($expected, $result);

		$expected = 'OK';
		$result = $couchdb->response->status['message'];
		$this->assertEqual($expected, $result);

		$expected = 'text/html';
		$result = $couchdb->response->type;
		$this->assertEqual($expected, $result);

		$expected = 'UTF-8';
		$result = $couchdb->response->charset;
		$this->assertEqual($expected, $result);
	}

	public function testGetPath() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->get('search.json');
		$this->assertEqual('Test!', $result);

		$expected = 'HTTP/1.1';
		$result = $couchdb->response->protocol;
		$this->assertEqual($expected, $result);

		$expected = '200';
		$result = $couchdb->response->status['code'];
		$this->assertEqual($expected, $result);

		$expected = 'OK';
		$result = $couchdb->response->status['message'];
		$this->assertEqual($expected, $result);

		$expected = 'text/html';
		$result = $couchdb->response->type;
		$this->assertEqual($expected, $result);

		$expected = 'UTF-8';
		$result = $couchdb->response->charset;
		$this->assertEqual($expected, $result);
	}

	public function testPost() {
		$couchdb = new CouchDb($this->_testConfig);
		$couchdb->post('update.xml', array('status' => 'cool'));
		$expected = join("\r\n", array(
			'POST /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 11',
			'', 'status=cool'
		));
		$result = (string)$couchdb->testRequest;
		$this->assertEqual($expected, $result);
	}

	public function testPut() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->put();
		$this->assertEqual('Test!', $result);
	}

	public function testDelete() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->delete(null);
		$this->assertEqual('Test!', $result);
	}

	public function testCreate() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->create(null);
		$this->assertEqual('Test!', $result);
	}

	public function testRead() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->read(null);
		$this->assertEqual('Test!', $result);
	}

	public function testUpdate() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->update(null);
		$this->assertEqual('Test!', $result);
	}
}

?>