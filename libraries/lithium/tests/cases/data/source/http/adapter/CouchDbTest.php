<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\http\adapter;

use \lithium\data\source\http\adapter\CouchDb;

use \lithium\data\Model;
use \lithium\data\model\Query;
use \lithium\data\model\Record;

class CouchDbTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'classes' => array(
			'service' => '\lithium\tests\mocks\data\source\http\adapter\MockService',
			'socket' => '\lithium\tests\mocks\data\source\http\adapter\MockSocket'
		),
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2
	);

	public function setUp() {
		$this->query = new Query(array(
			'model' => '\lithium\tests\mocks\data\source\http\adapter\MockCouchPost',
			'record' => new Record()
		));
	}

	public function tearDown() {
		unset($this->query);
	}

	public function testAllMethodsNoConnection() {
		$couchdb = new CouchDb(array('protocol' => null));
		$this->assertFalse($couchdb->connect());
		$this->assertTrue($couchdb->disconnect());
		$this->assertFalse($couchdb->get());
		$this->assertFalse($couchdb->post());
		$this->assertFalse($couchdb->put());
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
		$result = $couchdb->describe('companies');
	}

	public function testGet() {
		$this->skipIf(true, 'HTTP methods no longer callable from Couch adapter');

		$couchdb = new CouchDb($this->_testConfig);
		$expected = (object)array('ok' => true, 'id' => '12345', 'body' => 'something');
		$result = $couchdb->get();
		$this->assertEqual($expected, $result);

		$expected = 'HTTP/1.1';
		$result = $couchdb->last->response->protocol;
		$this->assertEqual($expected, $result);

		$expected = '200';
		$result = $couchdb->last->response->status['code'];
		$this->assertEqual($expected, $result);

		$expected = 'OK';
		$result = $couchdb->last->response->status['message'];
		$this->assertEqual($expected, $result);

		$expected = 'text/html';
		$result = $couchdb->last->response->type;
		$this->assertEqual($expected, $result);

		$expected = 'UTF-8';
		$result = $couchdb->last->response->charset;
		$this->assertEqual($expected, $result);
	}

	public function testGetPath() {
		$this->skipIf(true, 'HTTP methods no longer callable from Couch adapter');

		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->get('search.json');
		$expected = (object) array('ok' => true, 'id' => '12345', 'body' => 'something');
		$this->assertEqual($expected, $result);

		$expected = 'HTTP/1.1';
		$result = $couchdb->last->response->protocol;
		$this->assertEqual($expected, $result);

		$expected = '200';
		$result = $couchdb->last->response->status['code'];
		$this->assertEqual($expected, $result);

		$expected = 'OK';
		$result = $couchdb->last->response->status['message'];
		$this->assertEqual($expected, $result);

		$expected = 'text/html';
		$result = $couchdb->last->response->type;
		$this->assertEqual($expected, $result);

		$expected = 'UTF-8';
		$result = $couchdb->last->response->charset;
		$this->assertEqual($expected, $result);
	}

	public function testPost() {
		$this->skipIf(true, 'HTTP methods no longer callable from Couch adapter');

		$couchdb = new CouchDb($this->_testConfig);
		$couchdb->post('update.json', array('status' => 'cool'));
		$expected = join("\r\n", array(
			'POST /update.xml HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'Content-Type: application/json',
			'Content-Length: 17',
			'', '{"status":"cool"}'
		));
		$result = (string)$couchdb->last->request;
		$this->assertEqual($expected, $result);
	}

	public function testCreate() {
		$couchdb = new CouchDb($this->_testConfig);
		$expected = true;
		$result = $couchdb->create($this->query);
		$this->assertEqual($expected, $result);
	}

	public function testReadNoConditions() {
		$couchdb = new CouchDb($this->_testConfig);
		$expected = true;
		$result = $couchdb->read($this->query);
		$this->assertEqual($expected, $result);

		$expected = '/posts/_all_docs';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = array();
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithConditions() {
		$couchdb = new CouchDb($this->_testConfig);
		$expected = true;
		$this->query->conditions(array('_id' => 12345));
		$result = $couchdb->read($this->query);
		$this->assertEqual($expected, $result);

		$expected = '/posts/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = array();
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithViewConditions() {
		$couchdb = new CouchDb($this->_testConfig);
		$expected = true;
		$this->query->conditions(array('design' => 'latest', 'view' => 'all'));
		$result = $couchdb->read($this->query);
		$this->assertEqual($expected, $result);

		$expected = '/posts/_design/latest/_view/all';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = array();
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);
	}

	public function testUpdate() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->query->data(array('id' => 12345, 'rev' => '1-1', 'title' => 'One'));

		$expected = true;
		$result = $couchdb->update($this->query);
		$this->assertEqual($expected, $result);

		$expected = '/posts/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = array();
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);

	}

	public function testDelete() {
		$couchdb = new CouchDb($this->_testConfig);
		$expected = true;
		$result = $couchdb->delete($this->query);
		$this->assertEqual($expected, $result);
	}
}

?>