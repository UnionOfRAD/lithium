<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use lithium\data\source\MongoDb;
use Exception;
use stdClass;
use MongoId;
use MongoCode;
use MongoDate;
use MongoRegex;
use lithium\data\Schema;
use lithium\data\model\Query;
use lithium\data\entity\Document;
use lithium\tests\mocks\data\MockPost;
use lithium\tests\mocks\data\MockComment;
use lithium\data\collection\DocumentSet;
use lithium\tests\mocks\data\source\MockMongoSource;
use lithium\tests\mocks\data\source\MockMongoConnection;
use lithium\tests\mocks\data\source\mongo_db\MockResult;

class MongoDbTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\source\MockMongoPost';

	protected $_testConfig = array(
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
		$this->skipIf(!MongoDb::enabled(), 'The `MongoDb` class is not enabled.');

		$db = new MongoDb($this->_testConfig);
		$message = "`{$this->_testConfig['database']}` database or connection unavailable";
		$this->skipIf(!$db->isConnected(array('autoConnect' => true)), $message);
	}

	public function setUp() {
		$model = $this->_model;
		$this->db = new MongoDb($this->_testConfig);
		$this->db->server = (object) array('connected' => true);
		$this->db->connection = new MockMongoConnection();

		MockPost::resetSchema(true);
		MockComment::resetSchema(true);

		$model::config(array('key' => '_id'));
		$model::$connection = $this->db;

		$this->query = new Query(compact('model') + array('entity' => new Document(compact('model'))));
	}

	public function tearDown() {
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
		$this->db->connection->results = array(array());
		$this->assertEqual(array(), $this->db->sources());
	}

	public function testDescribe() {
		$result = $this->db->describe('test')->fields();
		$expected = array('_id' => array('type' => 'id'));
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
		array_push($this->db->connection->results, true);
		$this->query->data(array('title' => 'Test Post'));
		$this->assertTrue($this->db->create($this->query));

		$query = array_pop($this->db->connection->queries);

		$this->assertFalse($this->db->connection->queries);
		$this->assertEqual('insert', $query['type']);
		$this->assertEqual('posts', $query['collection']);
		$this->assertEqual(array('title', '_id'), array_keys($query['data']));
		$this->assertTrue($query['data']['_id'] instanceof MongoId);
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
		$data = array('title' => 'Test Post');

		$this->query->model($model);
		$this->query->data($data);
		$this->db->connection->results = array(true);
		$this->db->create($this->query);

		$result = array_pop($this->db->connection->queries);
		$data['_id'] = $result['data']['_id'];

		$expected = compact('data') + array(
			'collection' => 'posts',
			'type' => 'insert',
			'options' => array('safe' => false, 'fsync' => false)
		);
		$this->assertEqual($expected, $result);

		$this->db->connection->results = array(
			new MockResult(array('data' => array($data))),
			new MockResult(array('data' => array($data)))
		);
		$this->db->connection->queries = array();

		$result = $this->db->read(new Query(compact('model')));
		$original = $result->first()->to('array');

		$this->assertEqual(array('title', '_id'), array_keys($original));
		$this->assertEqual('Test Post', $original['title']);
		$this->assertPattern('/^[0-9a-f]{24}$/', $original['_id']);

		$this->db->connection->results = array(true);
		$this->db->connection->queries = array();
		$update = array('title' => 'New Post Title');

		$this->query = new Query(compact('model') + array(
			'data' => $update,
			'conditions' => array('_id' => $original['_id'])
		));
		$this->assertTrue($this->db->update($this->query));

		$result = array_pop($this->db->connection->queries);
		$expected = array(
			'type' => 'update',
			'collection' => 'posts',
			'conditions' => array('_id' => '4f188fb17675ab167900010e'),
				'update' => array('$set' => array('title' => 'New Post Title')
			),
			'options' => array('upsert' => false, 'multiple' => true, 'safe' => false, 'fsync' => false)
		);

		array_push($this->db->connection->results, new MockResult(array(
			'data' => array($update + $original)
		)));
		$this->db->connection->queries = array();

		$result = $this->db->read(new Query(compact('model') + array(
			'conditions' => array('_id' => $original['_id'])
		)));
		$this->assertEqual(1, $result->count());

		$updated = $result->first();
		$updated = $updated ? $updated->to('array') : array();
		$this->assertEqual($original['_id'], $updated['_id']);
		$this->assertEqual('New Post Title', $updated['title']);

		$expected = array(
			'type' => 'find',
			'collection' => 'posts',
			'fields' => array(),
			'conditions' => array('_id' => $original['_id'])
		);
		$this->assertEqual($expected, array_pop($this->db->connection->queries));
	}

	public function testDelete() {
		$data = array('title' => 'Delete Me');

		array_push($this->db->connection->results, true);
		$this->query->data($data);
		$this->db->create($this->query);

		array_push($this->db->connection->results, new MockResult(array(
			'data' => array()
		)));
		$this->assertNull($this->db->read($this->query)->first());

		$result = array_pop($this->db->connection->queries);
		$conditions = array('_id' => $this->query->entity()->_id);
		$this->assertEqual($conditions, $result['conditions']);

		$model = $this->_model;
		$this->query = new Query(compact('model') + array(
			'entity' => new Document(compact('model'))
		));

		array_push($this->db->connection->results, true);
		$this->query->conditions($conditions);
		$this->assertTrue($this->db->delete($this->query));

		$expected = compact('conditions') + array(
			'type' => 'remove',
			'collection' => 'posts',
			'options' => array('justOne' => false, 'safe' => false, 'fsync' => false)
		);
		$this->assertEqual($expected, array_pop($this->db->connection->queries));
	}

	public function testItem() {
		$model = $this->_model;
		$data = array('title' => 'New Item');
		$result = $this->db->item($model, $data);

		$this->assertTrue($result instanceof Document);

		$expected = $data;
		$result = $result->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testCalculation() {
		$this->db->connection->results = array(new MockResult(array('data' => array(5))));
		$this->assertIdentical(5, $this->db->calculation('count', $this->query));
	}

	public function testEnabled() {
		$this->assertTrue(MongoDb::enabled());
		$this->assertTrue(MongoDb::enabled('arrays'));
		$this->assertTrue(MongoDb::enabled('booleans'));
		$this->assertTrue(MongoDb::enabled('relationships'));
	}

	public function testArbitraryMethodCalls() {
		$db = new MongoDb($config = $this->_testConfig);
		$result = $db->__toString();
		$this->assertTrue(strpos($result, $config['host']) !== false);
		$this->assertTrue(strpos($result, $config['port']) !== false);
		$this->assertTrue(is_array($db->listDBs()));
	}

	public function testDocumentSorting() {
		$model = $this->_model;
		$model::config(array('source' => 'ordered_docs', 'locked' => false));

		$first = array('title' => 'First document',  'position' => 1);
		$second = array('title' => 'Second document', 'position' => 2);
		$third = array('title' => 'Third document',  'position' => 3);

		$model::create($third)->save();
		$model::create($first)->save();
		$model::create($second)->save();

		$result = $this->db->connection->queries;
		$createOpts = array(
			'validate' => true,
			'events' => 'create',
			'whitelist' => null,
			'callbacks' => true,
			'locked' => false,
			'safe' => false,
			'fsync' => false
		);
		$baseInsert = array('type' => 'insert', 'collection' => 'ordered_docs', 'options' => $createOpts);

		$expected = array(
			$baseInsert + array('data' => array('_id' => $result[0]['data']['_id']) + $third),
			$baseInsert + array('data' => array('_id' => $result[1]['data']['_id']) + $first),
			$baseInsert + array('data' => array('_id' => $result[2]['data']['_id']) + $second)
		);
		$this->assertEqual($expected, $result);

		array_push($this->db->connection->results, new MockResult(array(
			'data' => array($first, $second, $third)
		)));
		$this->db->connection->queries = array();
		$documents = $model::all(array('order' => 'position'));

		$this->assertEqual($first['title'], $documents[0]->title);
		$this->assertEqual($second['title'], $documents[1]->title);
		$this->assertEqual($third['title'], $documents[2]->title);

		$expected = array(
			'type' => 'find', 'collection' => 'ordered_docs', 'conditions' => array(), 'fields' => array()
		);
		$this->assertEqual($expected, array_pop($this->db->connection->queries));
		$this->assertEqual(array('position' => 1), $documents->result()->resource()->query['sort']);

		array_push($this->db->connection->results, new MockResult(array(
			'data' => array($first, $second, $third)
		)));
		$documents = $model::all(array('order' => array('position' => 'asc')));

		$this->assertEqual($first['title'], $documents[0]->title);
		$this->assertEqual($second['title'], $documents[1]->title);
		$this->assertEqual($third['title'], $documents[2]->title);

		$this->assertEqual($expected, array_pop($this->db->connection->queries));
		$this->assertEqual(array('position' => 1), $documents->result()->resource()->query['sort']);

		array_push($this->db->connection->results, new MockResult(array(
			'data' => array($third, $second, $first)
		)));
		$documents = $model::all(array('order' => array('position' => 'desc')));

		$this->assertEqual($third['title'], $documents[0]->title);
		$this->assertEqual($second['title'], $documents[1]->title);
		$this->assertEqual($first['title'], $documents[2]->title);

		$this->assertEqual($expected, array_pop($this->db->connection->queries));
		$this->assertEqual(array('position' => -1), $documents->result()->resource()->query['sort']);
	}

	public function testMongoIdPreservation() {
		$model = $this->_model;
		$model::resetSchema(true);
		$model::config(array('locked' => false));

		$post = $model::create(array('_id' => new MongoId(), 'title' => 'A post'));
		$post->save();
		$result = array_pop($this->db->connection->queries);
		$data = $result['data'];

		$this->assertEqual('A post', $data['title']);
		$this->assertTrue($data['_id'] instanceof MongoId);

		$post->sync();
		$post->title = 'An updated post';
		$post->save();

		$result = array_pop($this->db->connection->queries);
		$this->assertEqual(array('_id' => $post->_id), $result['conditions']);
		$this->assertEqual(array('$set' => array('title' => 'An updated post')), $result['update']);
	}

	public function testRelationshipGeneration() {
		$from = 'lithium\tests\mocks\data\MockComment';
		$to = 'lithium\tests\mocks\data\MockPost';

		$from::$connection = $this->db;
		$to::$connection = $this->db;
		$to::config(array('key' => '_id'));

		$result = $this->db->relationship($from, 'belongsTo', 'MockPost');
		$expected = array(
			'name' => 'MockPost',
			'type' => 'belongsTo',
			'key' => array('mockComment' => '_id'),
			'from' => $from,
			'link' => 'contained',
			'to'   => $to,
			'fields' => true,
			'fieldName' => 'mockPost',
			'constraint' => null,
			'init' => true
		);
		$this->assertEqual($expected, $result->data());
	}

	public function testCreateNoConnectionException() {
		$db = new MongoDb(array('host' => '__invalid__', 'autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->create(null);
	}

	public function testReadNoConnectionException() {
		$db = new MongoDb(array('host' => '__invalid__', 'autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->read(null);
	}

	public function testUpdateNoConnectionException() {
		$db = new MongoDb(array('host' => '__invalid__', 'autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->update(null);
	}

	public function testDeleteNoConnectionException() {
		$db = new MongoDb(array('host' => '__invalid__', 'autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->delete(null);
	}

	public function testSourcesNoConnectionException() {
		$db = new MongoDb(array('host' => null, 'autoConnect' => false));
		$this->expectException('Could not connect to the database.');
		$result = $db->sources(null);
	}

	public function testAtomicUpdate() {
		$model = $this->_model;
		$model::config(array('source' => 'posts'));
		$model::resetSchema();
		$data = array('initial' => 'one', 'values' => 'two');

		$this->db->connection = new MockMongoConnection();
		$this->db->connection->results = array(true, true);

		$document = $model::create($data);
		$this->assertTrue($document->save());

		$duplicate = $model::create(array('_id' => $document->_id), array('exists' => true));
		$duplicate->values = 'new';
		$this->assertTrue($duplicate->save());

		array_push($this->db->connection->results, new MockResult(array(
			'data' => array($data)
		)));
		$document = $model::find((string) $duplicate->_id);
		$expected = array('_id' => (string) $duplicate->_id, 'initial' => 'one', 'values' => 'new');
		$this->assertEqual($expected, $document->data());
		$queries = array();

		foreach (array('find', 'update', 'insert') as $key) {
			$queries[$key] = array_pop($this->db->connection->queries);
			$this->assertEqual($key, $queries[$key]['type']);
			$this->assertEqual('posts', $queries[$key]['collection']);
		}
		$_id = $queries['insert']['data']['_id'];

		$this->assertEqual($data + compact('_id'), $queries['insert']['data']);
		$this->assertEqual(compact('_id'), $queries['find']['conditions']);
	}

	/**
	 * Tests that the MongoDB adapter will not attempt to overwrite the _id field on document
	 * update.
	 */
	public function testPreserveId() {
		$model = $this->_model;
		$model::config(array('source' => 'posts'));

		$document = $model::create(array('_id' => 'custom'));
		$document->save();

		$document->_id = 'custom2';
		$document->foo = 'bar';
		$this->assertTrue($document->save());

		array_push($this->db->connection->results, new MockResult(array(
			'data' => array()
		)));
		$this->assertNull($model::first('custom2'));

		array_push($this->db->connection->results, new MockResult(array(
			'data' => array(array('_id' => new MongoId('custom'), 'foo' => 'bar'))
		)));
		$this->assertEqual(array('_id' => 'custom'), $model::first('custom')->data());
	}

	public function testCastingConditionsValues() {
		$query = new Query(array('schema' => new Schema(array('fields' => $this->_schema))));

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
		$expected = array('updated', '_id', 'created', 'list');
		$this->assertEqual($expected, array_keys($result['data']['update']));
		$this->assertTrue($result['data']['update']['updated'] instanceof MongoDate);
	}

	/**
	 * Assert that Mongo and the Mongo Exporter don't mangle manual geospatial queries.
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
		$this->assertEqual($schema, $db->describe(null)->fields());
	}
}

?>