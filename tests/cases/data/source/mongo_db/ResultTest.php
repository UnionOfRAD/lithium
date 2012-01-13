<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\mongo_db;

use lithium\tests\mocks\data\collection\MockDocumentSet;
use lithium\data\source\mongo_db\Result;
use Mongo;
use MongoCollection;
use MongoCursor;
use lithium\data\source\MongoDb;
use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\data\entity\Document;

class ResultTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'type' => 'MongoDb',
		'adapter' => false,
		'database' => 'lithium_test',
		'host' => 'localhost',
		'port' => '27017',
		'persistent' => null,
		'autoConnect' => false
	);

	protected $_mockData = array(
		array('_id' => 1, 'name' => 'Foo Company'),
		array('_id' => 2, 'name' => 'Bar Company')
	);

	protected $_configs = array();
	
	protected $_resource = null;

	public function skip() {
		$this->skipIf(!MongoDb::enabled(), 'MongoDb is not enabled');

		$db = new MongoDb($this->_testConfig);
		$message = "`{$this->_testConfig['database']}` database or connection unavailable";
		$this->skipIf(!$db->isConnected(array('autoConnect' => true)), $message);
	}

	public function setUp() {
		$m = new Mongo();
		$db = $m->selectDB($this->_testConfig['database']);
		$collection = new  MongoCollection($db, 'posts');
		foreach($this->_mockData as $data) {
			$collection->insert($data);
		}
		$this->_collection = $collection;
	}

	public function tearDown() {
		try {
			$this->_collection->remove();
		} catch (Exception $e) {}
		unset($this->_collection);
	}

	public function testConstruct() {
		$m = new Mongo();
		$db = $m->selectDB('lithium_test');
		$collection = new  MongoCollection($db, 'posts');
		$collection->find();
		
		$resource = $this->_collection->find();
		$result = new Result();
		$this->assertNull($result->resource());
		$result = new Result(compact('resource'));
		$this->assertTrue($result->resource() instanceof MongoCursor);
	}

	public function testNext() {
		
		$resource = $this->_collection->find();
		$result = new Result(compact('resource'));
		
		$this->assertEqual($this->_mockData[0], $result->next());
		$this->assertEqual($this->_mockData[1], $result->next());
		$this->assertNull($result->next());
	}

	public function testPrev() {
		
		$resource = $this->_collection->find();
		$result = new Result(compact('resource'));
		
		$this->assertNull($result->prev());
		$this->assertEqual($this->_mockData[0], $result->next());
		$this->assertEqual($this->_mockData[1], $result->next());
		$this->assertEqual($this->_mockData[0], $result->prev());
		$this->assertEqual($this->_mockData[1], $result->next());
		$this->assertEqual($this->_mockData[0], $result->prev());
		$this->assertNull($result->prev());
		
	}

	public function testRewind() {
		$resource = $this->_collection->find();
		$result = new Result(compact('resource'));

		$this->assertEqual($this->_mockData[0], $result->next());
		$this->assertEqual($this->_mockData[1], $result->next());
		$result->rewind();
		$this->assertEqual($this->_mockData[0], $result->current());
	}

	public function testCurrent() {
		$resource = $this->_collection->find();
		$result = new Result(compact('resource'));

		$this->assertEqual($this->_mockData[0], $result->next());
		$this->assertEqual($this->_mockData[0], $result->current());
		$this->assertEqual($this->_mockData[1], $result->next());
		$this->assertEqual($this->_mockData[1], $result->current());
		$this->assertEqual($this->_mockData[0], $result->prev());
		$this->assertEqual($this->_mockData[0], $result->current());
	}

	public function testKey() {
		$resource = $this->_collection->find();
		$result = new Result(compact('resource'));

		$this->assertEqual(0, $result->key());
		$result->next();
		$this->assertEqual(1, $result->key());
		$result->next();
		$this->assertEqual(2, $result->key());
		$result->rewind();
		$this->assertEqual(1, $result->key());
	}
}

?>