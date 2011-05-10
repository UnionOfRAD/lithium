<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use lithium\data\source\MongoDb;
use Exception;
use MongoId;
use MongoCode;
use MongoDate;
use MongoRegex;
use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\data\entity\Document;
use lithium\data\collection\DocumentSet;
use lithium\tests\mocks\data\source\MockMongoSource;
use lithium\tests\mocks\data\source\MockMongoConnection;

class MongoDbTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\source\MockMongoPost';

	protected $_testConfig = array(
		'type' => 'MongoDb',
		'adapter' => false,
		'database' => 'lithium_test',
		'host' => 'localhost',
		'port' => '27017',
		'persistent' => null,
		'autoConnect' => false
	);

	protected $_schema = array(
		'_id' => array('type' => 'id'),
		'guid' => array('type' => 'id'),
		'title' => array('type' => 'string'),
		'tags' => array('type' => 'string', 'array' => true),
		'comments' => array('type' => 'MongoId'),
		'authors' => array('type' => 'MongoId', 'array' => true),
		'created' => array('type' => 'MongoDate'),
		'modified' => array('type' => 'datetime'),
		'voters' => array('type' => 'id', 'array' => true),
		'rank_count' => array('type' => 'integer', 'default' => 0),
		'rank' => array('type' => 'float', 'default' => 0.0),
		'notifications.foo' => array('type' => 'boolean'),
		'notifications.bar' => array('type' => 'boolean'),
		'notifications.baz' => array('type' => 'boolean')
	);

	protected $_configs = array();

	public function skip() {
		$this->skipIf(!MongoDb::enabled(), 'MongoDb is not enabled');

		$db = new MongoDb($this->_testConfig);
		$message = "`{$this->_testConfig['database']}` database or connection unavailable";
		$this->skipIf(!$db->isConnected(array('autoConnect' => true)), $message);
	}

	/**
	 * This hack is a necessary optimization until these tests are properly mocked out.
	 *
	 * @param array $options Options for the parent class' method.
	 * @return void
	 */
	public function run(array $options = array()) {
		$this->_results = array();

		try {
			$this->skip();
		} catch (Exception $e) {
			$this->_handleException($e);
			return $this->_results;
		}
		$this->_configs = Connections::config();
		$result = parent::run($options);
		Connections::get('lithium_mongo_test')->dropDB('lithium_test');
		Connections::reset();
		Connections::config($this->_configs);
		return $result;
	}

	public function setUp() {
		Connections::config(array('lithium_mongo_test' => $this->_testConfig));
		$this->db = Connections::get('lithium_mongo_test');
		$model = $this->_model;
		$model::config(array('key' => '_id'));
		$model::resetConnection(false);

		$this->query = new Query(compact('model') + array(
			'entity' => new Document(compact('model'))
		));
	}

	public function tearDown() {
		try {
			$this->db->delete($this->query);
		} catch (Exception $e) {}
		unset($this->query);
	}

	public function testBadConnection() {
		$db = new MongoDb(array('host' => null, 'autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$this->assertFalse($db->connect());
		$this->assertTrue($db->disconnect());
	}

	public function testGoodConnectionBadDatabase() {
		$this->expectException('Could not connect to the database.');
		$db = new MongoDb(array('database' => null, 'autoConnnect' => false));
	}

	public function testGoodConnectionGoodDatabase() {
		$db = new MongoDb(array('autoConnect' => false) + $this->_testConfig);
		$this->assertFalse($db->isConnected());
		$this->assertTrue($db->connect());
		$this->assertTrue($db->isConnected());
	}

	public function testSources() {
		$result = $this->db->sources();
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
		$this->expectException('no elements in doc');
		$result = $this->db->create($this->query);
	}

	public function testCreateSuccess() {
		$this->query->data(array('title' => 'Test Post'));
		$result = $this->db->create($this->query);
		$this->assertTrue($result);
	}

	public function testConditions() {
		$result = $this->db->conditions(null, null);
		$this->assertEqual(array(), $result);

		$function = 'function() { return this.x < y;}';
		$conditions = new MongoCode($function);
		$result = $this->db->conditions($conditions, null);

		$this->assertTrue(is_array($result));
		$this->assertTrue(isset($result['$where']));
		$this->assertEqual($conditions, $result['$where']);

		$conditions = $function;
		$result = $this->db->conditions($conditions, null);
		$this->assertTrue(is_array($result));
		$this->assertTrue(isset($result['$where']));
		$this->assertEqual($conditions, $result['$where']);

		$conditions = array('key' => 'value', 'anotherkey' => 'some other value');
		$result = $this->db->conditions($conditions, null);
		$this->assertTrue(is_array($result));
		$this->assertEqual($conditions, $result);

		$conditions = array('key' => array('one', 'two', 'three'));
		$result = $this->db->conditions($conditions, null);
		$this->assertTrue(is_array($result));
		$this->assertTrue(isset($result['key']));
		$this->assertTrue(isset($result['key']['$in']));
		$this->assertEqual($conditions['key'], $result['key']['$in']);
	}

	public function testMongoConditionalOperators() {
		$conditions = array('key' => array('<' => 10));
		$expected = array('key' => array('$lt' => 10));
		$result = $this->db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('<=' => 10));
		$expected = array('key' => array('$lte' => 10));
		$result = $this->db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('>' => 10));
		$expected = array('key' => array('$gt' => 10));
		$result = $this->db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('>=' => 10));
		$expected = array('key' => array('$gte' => 10));
		$result = $this->db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('!=' => 10));
		$expected = array('key' => array('$ne' => 10));
		$result = $this->db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('<>' => 10));
		$expected = array('key' => array('$ne' => 10));
		$result = $this->db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('!=' => array(10, 20, 30)));
		$expected = array('key' => array('$nin' => array(10, 20, 30)));
		$result = $this->db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('<>' => array(10, 20, 30)));
		$expected = array('key' => array('$nin' => array(10, 20, 30)));
		$result = $this->db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('like' => '/regex/i'));
		$result = $this->db->conditions($conditions, null);
		$expected = array('key' => new MongoRegex('/regex/i'));
		$this->assertEqual($expected, $result);
	}

	public function testReadNoConditions() {
		$this->db->connect();
		$connection = $this->db->connection;
		$this->db->connection = new MockMongoSource();
		$this->db->connection->resultSets = array(array('ok' => true));

		$data = array('title' => 'Test Post');
		$options = array('safe' => false, 'fsync' => false);
		$this->query->data($data);
		$this->assertIdentical(true, $this->db->create($this->query));
		$this->assertEqual(compact('data', 'options'), end($this->db->connection->queries));

		$this->db->connection->resultSets = array(array(array('_id' => new MongoId()) + $data));
		$result = $this->db->read($this->query);

		$this->assertTrue($result instanceof DocumentSet);
		$this->assertEqual(1, $result->count());
		$this->assertEqual('Test Post', $result->first()->title);
		$this->db->connection = $connection;
	}

	public function testReadWithConditions() {
		$this->db->connect();
		$connection = $this->db->connection;
		$this->db->connection = new MockMongoSource();
		$this->db->connection->resultSets = array(array('ok' => true));

		$data = array('title' => 'Test Post');
		$options = array('safe' => false, 'fsync' => false);
		$this->query->data($data);
		$this->assertTrue($this->db->create($this->query));
		$this->query->data(null);

		$this->db->connection->resultSets = array(array());
		$this->query->conditions(array('title' => 'Nonexistent Post'));
		$result = $this->db->read($this->query);
		$this->assertTrue($result == true);
		$this->assertEqual(0, $result->count());

		$this->db->connection->resultSets = array(array($data));
		$this->query->conditions($data);
		$result = $this->db->read($this->query);
		$this->assertTrue($result == true);
		$this->assertEqual(1, $result->count());
		$this->db->connection = $connection;
	}

	public function testUpdate() {
		$model = $this->_model;

		$this->query->model($model);
		$this->query->data(array('title' => 'Test Post'));
		$this->db->create($this->query);

		$result = $this->db->read(new Query(compact('model')));
		$original = $result->first()->to('array');

		$this->assertEqual(array('_id', 'title'), array_keys($original));
		$this->assertEqual('Test Post', $original['title']);
		$this->assertPattern('/^[0-9a-f]{24}$/', $original['_id']);

		$this->query = new Query(compact('model') + array(
			'data' => array('title' => 'New Post Title'),
			'conditions' => array('_id' => $original['_id'])
		));
		$this->assertTrue($this->db->update($this->query));

		$result = $this->db->read(new Query(compact('model') + array(
			'conditions' => array('_id' => $original['_id'])
		)));
		$this->assertEqual(1, $result->count());

		$updated = $result->first();
		$updated = $updated ? $updated->to('array') : array();
		$this->assertEqual($original['_id'], $updated['_id']);
		$this->assertEqual('New Post Title', $updated['title']);
	}

	public function testDelete() {
		$data = array('title' => 'Delete Me');
		$this->query->data($data);
		$this->db->create($this->query);

		$result = $this->db->read($this->query);
		$expected = 1;
		$this->assertEqual($expected, $result->count());

		$record = $result->first()->to('array');

		$model = $this->_model;
		$this->query = new Query(compact('model') + array(
			'entity' => new Document(compact('model'))
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
		$model = $this->_model;
		$data = array('title' => 'New Item');
		$result = $this->db->item($model, $data);

		$this->assertTrue($result instanceof \lithium\data\entity\Document);

		$expected = $data;
		$result = $result->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testCalculation() {
		$result = $this->db->calculation('count', $this->query);
		$expected = 0;
		$this->assertEqual($expected, $result);
	}

	public function testEnabled() {
		$this->assertTrue(MongoDb::enabled());
		$this->assertTrue(MongoDb::enabled('arrays'));
		$this->assertTrue(MongoDb::enabled('booleans'));
		$this->assertTrue(MongoDb::enabled('relationships'));
	}

	public function testArbitraryMethodCalls() {
		$config = $this->_testConfig;
		$result = $this->db->__toString();
		$this->assertTrue(strpos($result, $config['host']) !== false);
		$this->assertTrue(strpos($result, $config['port']) !== false);
		$this->assertTrue(is_array($this->db->listDBs()));
	}

	public function testDocumentSorting() {
		$model = $this->_model;
		$model::config(array('connection' => 'lithium_mongo_test', 'source' => 'ordered_docs'));

		$model::create(array('title' => 'Third document',  'position' => 3))->save();
		$model::create(array('title' => 'First document',  'position' => 1))->save();
		$model::create(array('title' => 'Second document', 'position' => 2))->save();

		$documents = $model::all(array('order' => 'position'));

		$this->assertEqual('First document', $documents[0]->title);
		$this->assertEqual('Second document', $documents[1]->title);
		$this->assertEqual('Third document', $documents[2]->title);

		$documents = $model::all(array('order' => array('position' => 'asc')));

		$this->assertEqual('First document', $documents[0]->title);
		$this->assertEqual('Second document', $documents[1]->title);
		$this->assertEqual('Third document', $documents[2]->title);

		$copy = $model::all(array('order' => array('position')));
		$this->assertIdentical($documents->data(), $copy->data());

		$documents = $model::all(array('order' => array('position' => 'desc')));

		$this->assertEqual('Third document', $documents[0]->title);
		$this->assertEqual('Second document', $documents[1]->title);
		$this->assertEqual('First document', $documents[2]->title);

		$list = $model::find('list');
		$this->assertEqual(3, count($list));

		foreach ($list as $id => $title) {
			$this->assertTrue(is_string($id));
			$this->assertPattern('/^[a-f0-9]{24}$/', $id);
			$this->assertNull($title);
		}
		$model::config(array('title' => 'title'));

		$list = $model::find('list');
		$this->assertEqual(3, count($list));

		foreach ($list as $id => $title) {
			$this->assertTrue(is_string($id));
			$this->assertPattern('/^[a-f0-9]{24}$/', $id);
			$this->assertPattern('/^(First|Second|Third) document$/', $title);
		}

		foreach ($documents as $i => $doc) {
			$this->assertTrue($doc->delete());
		}
	}

	public function testMongoIdPreservation() {
		$model = $this->_model;
		$model::config(array('connection' => 'lithium_mongo_test', 'source' => 'ordered_docs'));

		$post = $model::create(array('title' => 'A post'));
		$post->save();
		$id = $post->_id;

		$data = Connections::get('lithium_mongo_test')->connection->ordered_docs->findOne(array(
			'_id' => $id
		));
		$this->assertEqual('A post', $data['title']);
		$this->assertEqual($id, (string) $data['_id']);
		$this->assertTrue($data['_id'] instanceof MongoId);

		$post->title = 'An updated post';
		$post->save();

		$data = Connections::get('lithium_mongo_test')->connection->ordered_docs->findOne(array(
			'_id' => new MongoId($id)
		));
		$this->assertEqual('An updated post', $data['title']);
		$this->assertEqual($id, (string) $data['_id']);
	}

	public function testRelationshipGeneration() {
		Connections::add('mock-source', $this->_testConfig);
		$from = 'lithium\tests\mocks\data\MockComment';
		$to = 'lithium\tests\mocks\data\MockPost';

		$from::config(array('connection' => 'mock-source'));
		$to::config(array('connection' => 'mock-source', 'key' => '_id'));

		$result = $this->db->relationship($from, 'belongsTo', 'MockPost');
		$expected = array(
			'name' => 'MockPost',
			'type' => 'belongsTo',
			'keys' => array('mockComment' => '_id'),
			'from' => $from,
			'link' => 'contained',
			'to'   => $to,
			'fields' => true,
			'fieldName' => 'mockPost',
			'constraint' => null,
			'init' => true
		);
		$this->assertEqual($expected, $result->data());
		Connections::config(array('mock-source' => false));
	}

	public function testCreateNoConnectionException() {
		$db = new MockMongoConnection($this->_testConfig + array('autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->create(null);
	}

	public function testReadNoConnectionException() {
		$db = new MockMongoConnection($this->_testConfig + array('autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->read(null);
	}

	public function testUpdateNoConnectionException() {
		$db = new MockMongoConnection($this->_testConfig + array('autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->update(null);
	}

	public function testDeleteNoConnectionException() {
		$db = new MockMongoConnection($this->_testConfig + array('autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->delete(null);
	}

	public function testSourcesNoConnectionException() {
		$db = new MockMongoConnection($this->_testConfig + array('autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->sources(null);
	}

	public function testAtomicUpdate() {
		$model = $this->_model;
		$model::config(array('connection' => 'lithium_mongo_test', 'source' => 'posts'));

		$document = $model::create(array('initial' => 'one', 'values' => 'two'));
		$document->save();

		$duplicate = $model::create(array('_id' => $document->_id), array('exists' => true));
		$duplicate->values = 'new';
		$this->assertTrue($duplicate->save());

		$document = $model::find((string) $duplicate->_id);
		$expected = array(
			'_id' => (string) $duplicate->_id, 'initial' => 'one', 'values' => 'new'
		);
		$this->assertEqual($expected, $document->data());
	}

	/**
	 * Tests that the MongoDB adapter will not attempt to overwrite the _id field on document
	 * update.
	 *
	 * @return void
	 */
	public function testPreserveId() {
		$model = $this->_model;
		$model::config(array('connection' => 'lithium_mongo_test', 'source' => 'posts'));

		$document = $model::create(array('_id' => 'custom'));
		$document->save();

		$document->_id = 'custom2';
		$document->foo = 'bar';
		$this->assertTrue($document->save());
		$this->assertNull($model::first('custom2'));
		$this->assertEqual(array('_id' => 'custom'), $model::first('custom')->data());
	}

	public function testCastingConditionsValues() {
		$query = new Query(array('schema' => $this->_schema));

		$conditions = array('_id' => new MongoId("4c8f86167675abfabdbe0300"));
		$result = $this->db->conditions($conditions, $query);
		$this->assertEqual($conditions, $result);

		$conditions = array('_id' => "4c8f86167675abfabdbe0300");
		$result = $this->db->conditions($conditions, $query);

		$this->assertEqual(array_keys($conditions), array_keys($result));
		$this->assertTrue($result['_id'] instanceof MongoId);
		$this->assertEqual($conditions['_id'], (string) $result['_id']);

		$conditions = array('_id' => array(
			"4c8f86167675abfabdbe0300", "4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
		));
		$result = $this->db->conditions($conditions, $query);
		$this->assertEqual(3, count($result['_id']['$in']));
		$this->assertTrue($result['_id']['$in'][0] instanceof MongoId);
		$this->assertTrue($result['_id']['$in'][1] instanceof MongoId);
		$this->assertTrue($result['_id']['$in'][2] instanceof MongoId);

		$conditions = array('voters' => array('$all' => array(
			"4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
		)));
		$result = $this->db->conditions($conditions, $query);

		$this->assertEqual(2, count($result['voters']['$all']));
		$this->assertTrue($result['voters']['$all'][0] instanceof MongoId);
		$this->assertTrue($result['voters']['$all'][1] instanceof MongoId);

		$conditions = array('$or' => array(
			array('_id' => "4c8f86167675abfabdbf0300"),
			array('guid' => "4c8f86167675abfabdbf0300")
		));
		$result = $this->db->conditions($conditions, $query);
		$this->assertEqual(array('$or'), array_keys($result));
		$this->assertEqual(2, count($result['$or']));
		$this->assertTrue($result['$or'][0]['_id'] instanceof MongoId);
		$this->assertTrue($result['$or'][1]['guid'] instanceof MongoId);
	}

	public function testMultiOperationConditions() {
		$conditions = array('loc' => array('$near' => array(50, 50), '$maxDistance' => 5));
		$result = $this->db->conditions($conditions, $this->query);
		$this->assertEqual($conditions, $result);
	}

	public function testCreateWithEmbeddedObjects() {
		$data = array(
			'_id' => new MongoId(),
			'created' => new MongoDate(strtotime('-1 hour')),
			'list' => array('foo', 'bar', 'baz')
		);
		$entity = new Document(compact('data') + array('exists' => false));
		$query = new Query(array('type' => 'create') + compact('entity'));
		$result = $query->export($this->db);
		$this->assertIdentical($data, $result['data']['data']);
	}

	public function testUpdateWithEmbeddedObjects() {
		$data = array(
			'_id' => new MongoId(),
			'created' => new MongoDate(strtotime('-1 hour')),
			'list' => array('foo', 'bar', 'baz')
		);
		$model = $this->_model;
		$schema = array('updated' => array('type' => 'MongoDate'));
		$entity = new Document(compact('data', 'schema', 'model') + array('exists' => true));
		$entity->updated = time();
		$entity->list[] = 'dib';

		$query = new Query(array('type' => 'update') + compact('entity'));
		$result = $query->export($this->db);
		$this->assertEqual(array('updated'), array_keys($result['data']['update']));
		$this->assertTrue($result['data']['update']['updated'] instanceof MongoDate);
	}

	/**
	 * Assert that Mongo and the Mongo Exporter don't mangle manual geospatial queries.
	 *
	 * @return void
	 */
	public function testGeoQueries() {
		$coords = array(84.13, 11.38);
		$coords2 = array_map(function($point) { return $point + 5; }, $coords);
		$conditions = array('location' => array('$near' => $coords));

		$query = new Query(compact('conditions') + array('model' => $this->_model));
		$result = $query->export($this->db);
		$this->assertEqual($result['conditions'], $conditions);

		$conditions = array('location' => array(
			'$within' => array('$box' => array($coords2, $coords))
		));
		$query = new Query(compact('conditions') + array('model' => $this->_model));
		$result = $query->export($this->db);
		$this->assertEqual($conditions, $result['conditions']);
	}

	public function testSchemaCallback() {
		$schema = array('_id' => array('type' => 'id'), 'created' => array('type' => 'date'));
		$db = new MongoDb(array('autoConnect' => false, 'schema' => function() use ($schema) {
			return $schema;
		}));
		$this->assertEqual($schema, $db->describe(null));
	}
}

?>