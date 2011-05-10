<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\http\adapter;

use lithium\data\model\Query;
use lithium\data\Connections;
use lithium\data\entity\Document;
use lithium\data\source\http\adapter\CouchDb;

class CouchDbTest extends \lithium\test\Unit {

	public $db;

	protected $_configs = array();

	protected $_testConfig = array(
		'database' => 'lithium-test',
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2,
		'socket' => 'lithium\tests\mocks\data\source\http\adapter\MockSocket'
	);

	protected $_model = 'lithium\tests\mocks\data\source\http\adapter\MockCouchPost';

	public function setUp() {
		$this->_configs = Connections::config();

		Connections::reset();
		$this->db = new CouchDb(array('socket' => false));

		Connections::config(array(
			'mock-couchdb-connection' => array('object' => &$this->db, 'adapter' => 'CouchDb')
		));

		$model = $this->_model;
		$entity = new Document(compact('model'));
		$this->query = new Query(compact('model', 'entity'));
	}

	public function tearDown() {
		Connections::reset();
		Connections::config($this->_configs);
		unset($this->query);
	}

	public function testAllMethodsNoConnection() {
		$this->assertTrue($this->db->connect());
		$this->assertTrue($this->db->disconnect());
		$this->assertFalse($this->db->get());
		$this->assertFalse($this->db->post());
		$this->assertFalse($this->db->put());
	}

	public function testConnect() {
		$this->db = new CouchDb($this->_testConfig);
		$result = $this->db->connect();
		$this->assertTrue($result);
	}

	public function testDisconnect() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->connect();
		$this->assertTrue($result);

		$result = $couchdb->disconnect();
		$this->assertTrue($result);
	}

	public function testSources() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->sources();
		$this->assertNull($result);
	}

	public function testDescribe() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->expectException('/companies is not available/');
		$result = $couchdb->describe('companies');
		$this->assertNull($result);
	}

	public function testItem() {
		$couchdb = new CouchDb($this->_testConfig);
		$data = array(
			'_id' => 'a1', '_rev' => '1-2', 'author' => 'author 1', 'body' => 'body 1'
		);
		$expected = array(
			'id' => 'a1', 'rev' => '1-2',
			'author' => 'author 1', 'body' => 'body 1'
		);
		$item = $couchdb->item($this->query->model(), $data);
		$result = $item->data();
		$this->assertEqual($expected, $result);
	}

	public function testCreateNoId() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->query->data(array('name' => 'Acme Inc.'));

		$result = $couchdb->create($this->query);
		$this->assertTrue($result);

		$expected = '/lithium-test';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = array();
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);
	}

	public function testCreateWithId() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->query->data(array('id' => 12345, 'name' => 'Acme Inc.'));

		$result = $couchdb->create($this->query);
		$this->assertTrue($result);

		$expected = '/lithium-test/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = array();
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);
	}

	public function testReadNoConditions() {
		$couchdb = new CouchDb($this->_testConfig);

		$result = $couchdb->read($this->query);
		$this->assertTrue($result);
		$this->assertEqual(array('total_rows' => null, 'offset' => null), $result->stats());

		$expected = '/lithium-test/_all_docs';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = 'include_docs=true';
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithConditions() {
		$couchdb = new CouchDb($this->_testConfig);

		$this->query->conditions(array('id' => 12345));
		$result = $couchdb->read($this->query);
		$this->assertTrue($result);
		$this->assertEqual(array('total_rows' => null, 'offset' => null), $result->stats());

		$expected = '/lithium-test/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = '';
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);

		$this->query->conditions(array('id' => 12345, 'path' => '/lithium-test/12345'));
		$result = $couchdb->read($this->query);
		$this->assertTrue($result);
	}

	public function testReadWithViewConditions() {
		$couchdb = new CouchDb($this->_testConfig);

		$this->query->conditions(array(
			'design' => 'latest', 'view' => 'all', 'limit' => 10, 'descending' => 'true'
		));
		$result = $couchdb->read($this->query);
		$this->assertEqual(array('total_rows' => null, 'offset' => null), $result->stats());
		$this->assertTrue($result);

		$expected = '/lithium-test/_design/latest/_view/all/';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = 'limit=10&descending=true';
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);
	}

	public function testFlatResult() {
		$couchdb = new CouchDb($this->_testConfig);
		$rows = (object) array(
			'_id' => 'a1', '_rev' => '1-2', 'author' => 'author 1', 'body' => 'body 1'
		);
		$expected = array(
			'id' => 'a1', 'rev' => '1-2',
			'author' => 'author 1', 'body' => 'body 1'
		);
		$result = $couchdb->result('next', $rows, $this->query);
		$this->assertEqual($expected, $result);
	}

	public function testRowsResult() {
		$couchdb = new CouchDb($this->_testConfig);

		$rows = (object) array('total_rows' => 11, 'offset' => 0, 'rows' => array(
			(object) array('id' => 'a1', 'key' => null, 'value' => array(
				'author' => 'author 1',
				'body' => 'body 1'
			)),
			(object) array('id' => 'a2', 'key' => null, 'value' => array(
				'author' => 'author 2',
				'body' => 'body 2'
			)),
			(object) array('id' => 'a3', 'key' => null, 'value' => array(
				'author' => 'author 3',
				'body' => 'body 3'
			))
		));
		$expected = array(
			'id' => 'a1', 'author' => 'author 1', 'body' => 'body 1'
		);
		$result = $couchdb->result('next', $rows, $this->query);
		$this->assertEqual($expected, $result);

		$expected = array(
			'id' => 'a2', 'author' => 'author 2', 'body' => 'body 2'
		);
		$result = $couchdb->result('next', $rows, $this->query);
		$this->assertEqual($expected, $result);
	}

	public function testRowsResultFromAllDocs() {
		$couchdb = new CouchDb($this->_testConfig);

		$rows = (object) array('total_rows' => 3, 'offset' => 0, 'rows' => array(
			(object) array('id' => 'a1', 'key' => null, 'value' => array(
				'author' => 'author 1',
				'body' => 'body 1'
			)),
			(object) array('id' => 'a2', 'key' => null, 'value' => array(
				'author' => 'author 2',
				'body' => 'body 2'
			)),
			(object) array('id' => 'a3', 'key' => null, 'value' => array(
				'author' => 'author 3',
				'body' => 'body 3'
			))
		));
		$expected = array(
			'id' => 'a1', 'author' => 'author 1', 'body' => 'body 1'
		);
		$result = $couchdb->result('next', $rows, $this->query);
		$this->assertEqual($expected, $result);

		$expected = array(
			'id' => 'a2', 'author' => 'author 2', 'body' => 'body 2'
		);
		$result = $couchdb->result('next', $rows, $this->query);
		$this->assertEqual($expected, $result);
	}

	public function testRowsResultFromAllDocsIncludDocs() {
		$couchdb = new CouchDb($this->_testConfig);

		$rows = (object) array('total_rows' => 3, 'offset' => 0, 'rows' => array(
			(object) array('doc' => array(
				'_id' => 'a1', '_rev' => '1-1',
				'author' => 'author 1',
				'body' => 'body 1'
			)),
			(object) array('doc' => array(
				'_id' => 'a2', '_rev' => '1-2',
				'author' => 'author 2',
				'body' => 'body 2'
			)),
			(object) array('doc' => array(
				'_id' => 'a3', '_rev' => '1-3',
				'author' => 'author 3',
				'body' => 'body 3'
			))
		));
		$expected = array(
			'id' => 'a1', 'rev' => '1-1', 'author' => 'author 1', 'body' => 'body 1'
		);
		$result = $couchdb->result('next', $rows, $this->query);
		$this->assertEqual($expected, $result);

		$expected = array(
			'id' => 'a2', 'rev' => '1-2', 'author' => 'author 2', 'body' => 'body 2'
		);
		$result = $couchdb->result('next', $rows, $this->query);
		$this->assertEqual($expected, $result);
	}

	public function testResultClose() {
		$couchdb = new CouchDb($this->_testConfig);

		$result = $couchdb->result('close', (object) array(), $this->query);
		$this->assertNull($result);
	}

	public function testUpdate() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->query->data(array('id' => 12345, 'rev' => '1-1', 'title' => 'One'));

		$result = $couchdb->update($this->query);
		$this->assertTrue($result);

		$expected = '/lithium-test/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = array();
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->query->data(array('id' => 12345, 'rev'=> '1-1', 'name' => 'Acme Inc'));

		$result = $couchdb->delete($this->query);
		$this->assertTrue($result);

		$expected = '/lithium-test/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = 'rev=1-1';
		$result = $couchdb->last->request->params;
		$this->assertEqual($expected, $result);
	}

	public function testEnabled() {
		$this->assertEqual(CouchDb::enabled(), true);

		$this->assertEqual(CouchDb::enabled('arrays'), true);
		$this->assertEqual(CouchDb::enabled('transactions'), false);
		$this->assertEqual(CouchDb::enabled('booleans'), true);
		$this->assertEqual(CouchDb::enabled('relationships'), false);
	}
}

?>