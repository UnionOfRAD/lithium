<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */

namespace lithium\tests\cases\data\source;

use \lithium\data\source\MongoDb;

use \lithium\data\Connections;
use \lithium\data\Model;
use \lithium\data\model\Query;
use \lithium\data\collection\Document;

class MongoDbTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'type' => 'MongoDb',
		'adapter' => false,
		'database' => 'lithium_test',
		'host' => 'localhost',
		'port' => '27017',
		'persistent' => false
	);

	public function skip() {
		$message = 'MongoDb Extension is not loaded';
		$this->skipIf(!MongoDb::enabled(), $message);

		$db = new MongoDb($this->_testConfig);
		$this->skipIf(
			!$db->isConnected(),
			"`{$this->_testConfig['database']}` database or connection unavailable"
		);

		Connections::add('lithium_mongo_test', array(
			$this->_testConfig
		));
	}

	public function setUp() {
		$this->db = Connections::get('lithium_mongo_test');

		$model = '\lithium\tests\mocks\data\source\MockMongoPost';
		$this->query = new Query(compact('model') + array(
			'record' => new Document(compact('model'))
		));
	}

	public function tearDown() {
		unset($this->query);
		$this->db->dropDB('lithium_test');
	}

	public function testBadConnection() {
		$db = new MongoDb(array('host' => null, 'autoConnect' => false));
		$this->assertFalse($db->connect());
		$this->assertTrue($db->disconnect());
	}

	public function testGoodConnectionBadDatabase() {
		$db = new MongoDb(array('database' => null, 'autoConnnect' => false));
		$this->assertFalse($db->connect());
	}

	public function testGoodConnectionGoodDatabase() {
		$db = new MongoDb(array('autoConnect' => false) + $this->_testConfig);
		$this->assertTrue($db->connect());
	}

	public function testEntities() {
		$result = $this->db->entities();
		$expected = array();
		$this->assertEqual($expected, $result);
	}

	public function testDescribe() {
		$result = $this->db->describe('test');
		$expected = array();
		$this->assertEqual($expected, $result);
	}

	public function testName() {
		$result = $this->db->name('{(\'Li\':"∆")}');
		$expected = '{(\'Li\':"∆")}';
		$this->assertEqual($expected, $result);
	}

	public function testSchema() {
		$result = $this->db->schema($this->query);
		$expected = array();
		$this->assertEqual($expected, $result);
	}

	public function testCreateFail() {
		$this->expectException('couldn\'t create insert msg');
		$result = $this->db->create($this->query);
	}

	public function testCreateSuccess() {
		$this->query->data(array('title' => 'Test Post'));
		$result = $this->db->create($this->query);
		$this->assertTrue($result);
	}

	public function testReadNoConditions() {
		$data = array('title' => 'Test Post');
		$this->query->data($data);
		$this->db->create($this->query);

		$result = $this->db->read($this->query);
		$this->assertTrue($result);

		$expected = 1;
		$this->assertEqual($expected, $result->count());

		$expected = $data['title'];
		$this->assertEqual($expected, $result->first()->title);
	}

	public function testReadWithConditions() {
		$data = array('title' => 'Test Post');
		$this->query->data($data);
		$this->db->create($this->query);
		$this->query->data(null);

		$this->query->conditions(array('title' => 'Nonexistent Post'));
		$result = $this->db->read($this->query);
		$this->assertTrue($result);

		$expected = 0;
		$this->assertEqual($expected, $result->count());

		$this->query->conditions($data);
		$result = $this->db->read($this->query);
		$this->assertTrue($result);

		$expected = 1;
		$this->assertEqual($expected, $result->count());
	}

	public function testUpdate() {
		$data = array('title' => 'Test Post');
		$this->query->data($data);
		$this->db->create($this->query);

		$result = $this->db->read($this->query);
		$original = $result->first()->to('array');

		$model = '\lithium\tests\mocks\data\source\MockMongoPost';
		$this->query = new Query(compact('model') + array(
			'record' => new Document(compact('model'))
		));
		$newData = array('title' => 'New Post Title');
		$this->query->data($newData);
		$this->query->conditions(array('_id' => $original['_id']));

		$result = $this->db->update($this->query);
		$this->assertTrue($result);

		$result = $this->db->read($this->query);

		$expected = 1;
		$this->assertEqual($expected, $result->count());

		$updated = $result->first()->to('array');
		$expected = $original['_id'];
		$result = $updated['_id'];
		$this->assertEqual($expected, $result);

		$expected = $newData['title'];
		$result = $updated['title'];
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$data = array('title' => 'Delete Me');
		$this->query->data($data);
		$this->db->create($this->query);

		$result = $this->db->read($this->query);
		$expected = 1;
		$this->assertEqual($expected, $result->count());

		$record = $result->first()->to('array');

		$model = '\lithium\tests\mocks\data\source\MockMongoPost';
		$this->query = new Query(compact('model') + array(
			'record' => new Document(compact('model'))
		));
		$this->query->conditions(array('_id' => $record['_id']));
		$result = $this->db->delete($this->query);

		$this->assertTrue($result);

		$result = $this->db->read($this->query);
		$this->assertTrue($result);

		$expected = 0;
		$this->assertEqual($expected, $result->count());
	}

	public function testItem() {
		$model = '\lithium\tests\mocks\data\source\MockMongoPost';
		$data = array('title' => 'New Item');
		$result = $this->db->item($model, $data);

		$this->assertTrue($result instanceof \lithium\data\collection\Document);

		$expected = $data;
		$result = $result->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testCalculation() {
		$result = $this->db->calculation('count', $this->query);
		$expected = 0;
		$this->assertEqual($expected, $result);
	}

}

?>