<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use lithium\data\source\mongo_db\Schema;
use lithium\data\source\MongoDb;
use MongoId;
use MongoCode;
use MongoDate;
use MongoRegex;
use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\data\entity\Document;
use lithium\tests\mocks\data\MockPost;
use lithium\tests\mocks\data\MockComment;
use lithium\tests\mocks\core\MockCallable;
use lithium\tests\mocks\data\source\MockMongoSource;
use lithium\tests\mocks\data\source\MockMongoConnection;
use lithium\tests\mocks\data\source\mongo_db\MockResultResource;
use lithium\tests\mocks\data\source\MockMongoPost;

class MongoDbTest extends \lithium\test\Unit {

	protected $_db = null;

	protected $_model = 'lithium\tests\mocks\data\source\MockMongoPost';

	protected $_query = null;

	protected $_testConfig = array(
		'adapter' => false,
		'database' => 'test',
		'host' => 'localhost',
		'port' => '27017',
		'autoConnect' => false
	);

	protected $_schema = array(
		'_id'               => 'id',
		'guid'              => 'id',
		'title'             => 'string',
		'tags'              => array('type' => 'string', 'array' => true),
		'comments'          => 'MongoId',
		'authors'           => array('type' => 'MongoId', 'array' => true),
		'created'           => 'MongoDate',
		'modified'          => 'datetime',
		'voters'            => array('type' => 'id', 'array' => true),
		'rank_count'        => array('type' => 'integer', 'default' => 0),
		'rank'              => array('type' => 'float', 'default' => 0.0),
		'notifications.foo' => 'boolean',
		'notifications.bar' => 'boolean',
		'notifications.baz' => 'boolean'
	);

	protected $_configs = array();

	public function skip() {
		$this->skipIf(!MongoDb::enabled(), 'The `MongoDb` class is not enabled.');
	}

	public function setUp() {
		$this->_db = new MongoDb($this->_testConfig);
		$this->_db->server = new MockMongoConnection();
		$this->_db->connection = new MockMongoConnection();

		Connections::add('mockconn', array('object' => $this->_db));
		MockMongoPost::config(array('meta' => array('key' => '_id', 'connection' => 'mockconn')));

		$type = 'create';
		$this->_query = new Query(compact('model', 'type') + array(
			'entity' => new Document(array('model' => $this->_model))
		));
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockPost::reset();
		MockComment::reset();
		MockMongoPost::reset();
	}

	public function testBadConnection() {
		$db = new MongoDb(array('host' => null, 'autoConnect' => false));
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->connect();
		});
		$this->assertTrue($db->disconnect());
	}

	public function testGoodConnectionBadDatabase() {
		$db = new MongoDb(array('database' => null, 'autoConnnect' => false));

		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->connect();
		});
	}

	public function testSources() {
		$this->_db->connection->results = array(array());
		$this->assertEqual(array(), $this->_db->sources());
	}

	public function testDescribe() {
		$result = $this->_db->describe('test')->fields();
		$expected = array('_id' => array('type' => 'id'));
		$this->assertEqual($expected, $result);
	}

	public function testName() {
		$result = $this->_db->name('{(\'Li\':"∆")}');
		$expected = '{(\'Li\':"∆")}';
		$this->assertEqual($expected, $result);
	}

	public function testSchema() {
		$result = $this->_db->schema($this->_query);
		$expected = array();
		$this->assertEqual($expected, $result);
	}

	public function testCreateSuccess() {
		array_push($this->_db->connection->results, true);
		$this->_query->data(array('title' => 'Test Post'));
		$this->assertTrue($this->_db->create($this->_query));

		$query = array_pop($this->_db->connection->queries);

		$this->assertEmpty($this->_db->connection->queries);
		$this->assertEqual('insert', $query['type']);
		$this->assertEqual('posts', $query['collection']);
		$this->assertEqual(array('title', '_id'), array_keys($query['data']));
		$this->assertInstanceOf('MongoId', $query['data']['_id']);
	}

	public function testConditions() {
		$result = $this->_db->conditions(null, null);
		$this->assertEqual(array(), $result);

		$function = 'function() { return this.x < y;}';
		$conditions = new MongoCode($function);
		$result = $this->_db->conditions($conditions, null);

		$this->assertInternalType('array', $result);
		$this->assertTrue(isset($result['$where']));
		$this->assertEqual($conditions, $result['$where']);

		$conditions = $function;
		$result = $this->_db->conditions($conditions, null);
		$this->assertInternalType('array', $result);
		$this->assertTrue(isset($result['$where']));
		$this->assertEqual($conditions, $result['$where']);

		$conditions = array('key' => 'value', 'anotherkey' => 'some other value');
		$result = $this->_db->conditions($conditions, null);
		$this->assertInternalType('array', $result);
		$this->assertEqual($conditions, $result);

		$conditions = array('key' => array('one', 'two', 'three'));
		$result = $this->_db->conditions($conditions, null);
		$this->assertInternalType('array', $result);
		$this->assertTrue(isset($result['key']));
		$this->assertTrue(isset($result['key']['$in']));
		$this->assertEqual($conditions['key'], $result['key']['$in']);

		$conditions = array('$or' => array(
			array('key' => 'value'),
			array('other key' => 'another value')
		));
		$result = $this->_db->conditions($conditions, null);
		$this->assertTrue(isset($result['$or']));
		$this->assertEqual($conditions['$or'][0]['key'], $result['$or'][0]['key']);

		$conditions = array('$and' => array(
			array('key' => 'value'),
			array('other key' => 'another value')
		));
		$result = $this->_db->conditions($conditions, null);
		$this->assertTrue(isset($result['$and']));
		$this->assertEqual($conditions['$and'][0]['key'], $result['$and'][0]['key']);

		$conditions = array('$nor' => array(
			array('key' => 'value'),
			array('other key' => 'another value')
		));
		$result = $this->_db->conditions($conditions, null);
		$this->assertTrue(isset($result['$nor']));
		$this->assertEqual($conditions['$nor'][0]['key'], $result['$nor'][0]['key']);

		$conditions = array('key' => array('or' => array(1, 2)));
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual(array('key' => array('$or' => array(1, 2))), $result);
	}

	public function testMongoConditionalOperators() {
		$conditions = array('key' => array('<' => 10));
		$expected = array('key' => array('$lt' => 10));
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('<=' => 10));
		$expected = array('key' => array('$lte' => 10));
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('>' => 10));
		$expected = array('key' => array('$gt' => 10));
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('>=' => 10));
		$expected = array('key' => array('$gte' => 10));
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('!=' => 10));
		$expected = array('key' => array('$ne' => 10));
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('<>' => 10));
		$expected = array('key' => array('$ne' => 10));
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('!=' => array(10, 20, 30)));
		$expected = array('key' => array('$nin' => array(10, 20, 30)));
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('<>' => array(10, 20, 30)));
		$expected = array('key' => array('$nin' => array(10, 20, 30)));
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = array('key' => array('like' => '/regex/i'));
		$result = $this->_db->conditions($conditions, null);
		$expected = array('key' => new MongoRegex('/regex/i'));
		$this->assertEqual($expected, $result);
	}

	public function testConditionsWithSchema() {
		$schema = new Schema(array('fields' => array(
			'_id' => array('type' => 'id'),
			'tags' => array('type' => 'string', 'array' => true),
			'users' => array('type' => 'id', 'array' => true)
		)));

		$query = new Query(array('schema' => $schema, 'type' => 'read'));

		$id = new MongoId();
		$userId = new MongoId();

		$conditions = array(
			'_id' => (string) $id,
			'tags' => 'yellow',
			'users' => (string) $userId
		);
		$result = $this->_db->conditions($conditions, $query);

		$expected = array(
			'_id' => $id,
			'tags' => 'yellow',
			'users' => $userId
		);
		$this->assertEqual($expected, $result);
	}

	public function testReadNoConditions() {
		$this->_db->connect();
		$connection = $this->_db->connection;
		$this->_db->connection = new MockMongoSource();
		$this->_db->connection->resultSets = array(array('ok' => true));

		$data = array('title' => 'Test Post');
		$options = array('w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false);
		$this->_query->data($data);
		$this->assertTrue($this->_db->create($this->_query));
		$this->assertEqual(compact('data', 'options'), end($this->_db->connection->queries));

		$this->_db->connection->resultSets = array(array(array('_id' => new MongoId()) + $data));
		$result = $this->_db->read($this->_query);

		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $result);
		$this->assertEqual(1, $result->count());
		$this->assertEqual('Test Post', $result->first()->title);
		$this->_db->connection = $connection;
	}

	public function testReadWithConditions() {
		$this->_db->connect();
		$connection = $this->_db->connection;
		$this->_db->connection = new MockMongoSource();
		$this->_db->connection->resultSets = array(array('ok' => true));

		$data = array('title' => 'Test Post');
		$options = array('w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false);
		$this->_query->data($data);
		$this->assertTrue($this->_db->create($this->_query));
		$this->_query->data(null);

		$this->_db->connection->resultSets = array(array());
		$this->_query->conditions(array('title' => 'Nonexistent Post'));
		$result = $this->_db->read($this->_query);
		$this->assertNotEmpty($result);
		$this->assertEqual(0, $result->count());

		$this->_db->connection->resultSets = array(array($data));
		$this->_query->conditions($data);
		$result = $this->_db->read($this->_query);
		$this->assertNotEmpty($result);
		$this->assertEqual(1, $result->count());
		$this->_db->connection = $connection;
	}

	public function testUpdate() {
		$model = $this->_model;
		$data = array('title' => 'Test Post');

		$this->_query->model($model);
		$this->_query->data($data);
		$this->_db->connection->results = array(true);
		$this->_db->create($this->_query);

		$result = array_pop($this->_db->connection->queries);
		$data['_id'] = $result['data']['_id'];

		$expected = compact('data') + array(
			'collection' => 'posts',
			'type' => 'insert',
			'options' => array('w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false)
		);
		$this->assertEqual($expected, $result);

		$this->_db->connection->results = array(
			new MockResultResource(array('data' => array($data))),
			new MockResultResource(array('data' => array($data)))
		);
		$this->_db->connection->queries = array();

		$result = $this->_db->read(new Query(compact('model')));
		$original = $result->first()->to('array');

		$this->assertEqual(array('title', '_id'), array_keys($original));
		$this->assertEqual('Test Post', $original['title']);
		$this->assertPattern('/^[0-9a-f]{24}$/', $original['_id']);

		$this->_db->connection->results = array(true);
		$this->_db->connection->queries = array();
		$update = array('title' => 'New Post Title');

		$this->_query = new Query(compact('model') + array(
			'data' => $update,
			'conditions' => array('_id' => $original['_id'])
		));
		$this->assertTrue($this->_db->update($this->_query));

		$result = array_pop($this->_db->connection->queries);
		$expected = array(
			'type' => 'update',
			'collection' => 'posts',
			'conditions' => array('_id' => '4f188fb17675ab167900010e'),
			'update' => array('$set' => array('title' => 'New Post Title')),
			'options' => array(
				'upsert' => false, 'multiple' => true, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false
			)
		);

		array_push($this->_db->connection->results, new MockResultResource(array(
			'data' => array($update + $original)
		)));
		$this->_db->connection->queries = array();

		$result = $this->_db->read(new Query(compact('model') + array(
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
		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
	}

	public function testDelete() {
		$model = $this->_model;
		$data = array('title' => 'Delete Me');

		array_push($this->_db->connection->results, true);
		$this->_query->data($data);
		$this->_db->create($this->_query);

		array_push($this->_db->connection->results, new MockResultResource(array(
			'data' => array()
		)));
		$this->assertFalse($this->_db->read($this->_query)->first());

		$result = array_pop($this->_db->connection->queries);
		$conditions = array('_id' => $this->_query->entity()->_id);
		$this->assertEqual($conditions, $result['conditions']);
		$this->assertTrue($this->_query->entity()->exists());

		$id = new MongoId();
		$this->_query = new Query(compact('model') + array(
			'entity' => new Document(compact('model') + array('data' => array('_id' => $id)))
		));

		array_push($this->_db->connection->results, true);
		$this->_query->conditions($conditions);
		$this->assertTrue($this->_db->delete($this->_query));
		$this->assertFalse($this->_query->entity()->exists());

		$expected = compact('conditions') + array(
			'type' => 'remove',
			'collection' => 'posts',
			'options' => array('justOne' => false, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false)
		);
		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
	}

	public function testCreate() {
		$data = array('title' => 'New Item');
		$result = MockMongoPost::create($data, array('defaults' => false));
		$this->assertInstanceOf('lithium\data\entity\Document', $result);

		$expected = $data;
		$result = $result->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testCalculation() {
		$this->_db->connection->results = array(new MockResultResource(array('data' => array(5))));
		$this->assertIdentical(5, $this->_db->calculation('count', $this->_query));
	}

	public function testEnabled() {
		$this->assertTrue(MongoDb::enabled());
		$this->assertTrue(MongoDb::enabled('arrays'));
		$this->assertTrue(MongoDb::enabled('booleans'));
		$this->assertTrue(MongoDb::enabled('relationships'));
	}

	public function testArbitraryMethodCalls() {
		$this->assertInternalType('array', $this->_db->listDBs());
	}

	public function testDocumentSorting() {
		MockMongoPost::config(array('meta' => array('source' => 'ordered_docs', 'locked' => false)));

		$first = array('title' => 'First document',  'position' => 1);
		$second = array('title' => 'Second document', 'position' => 2);
		$third = array('title' => 'Third document',  'position' => 3);

		MockMongoPost::create($third)->save();
		MockMongoPost::create($first)->save();
		MockMongoPost::create($second)->save();

		$result = $this->_db->connection->queries;
		$createOpts = array(
			'validate' => true,
			'events' => 'create',
			'whitelist' => null,
			'callbacks' => true,
			'locked' => false,
			'w' => 1,
			'wTimeoutMS' => 10000,
			'fsync' => false
		);
		$baseInsert = array(
			'type' => 'insert',
			'collection' => 'ordered_docs',
			'options' => $createOpts
		);

		$expected = array(
			$baseInsert + array('data' => array('_id' => $result[0]['data']['_id']) + $third),
			$baseInsert + array('data' => array('_id' => $result[1]['data']['_id']) + $first),
			$baseInsert + array('data' => array('_id' => $result[2]['data']['_id']) + $second)
		);
		$this->assertEqual($expected, $result);

		array_push($this->_db->connection->results, new MockResultResource(array(
			'data' => array($first, $second, $third)
		)));
		$this->_db->connection->queries = array();
		$documents = MockMongoPost::all(array('order' => 'position'));

		$this->assertEqual($first['title'], $documents[0]->title);
		$this->assertEqual($second['title'], $documents[1]->title);
		$this->assertEqual($third['title'], $documents[2]->title);

		$expected = array(
			'type' => 'find',
			'collection' => 'ordered_docs',
			'conditions' => array(),
			'fields' => array()
		);
		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
		$result = $documents->result()->resource()->query['sort'];
		$this->assertEqual(array('position' => 1), $result);

		array_push($this->_db->connection->results, new MockResultResource(array(
			'data' => array($first, $second, $third)
		)));
		$documents = MockMongoPost::all(array('order' => array('position' => 'asc')));

		$this->assertEqual($first['title'], $documents[0]->title);
		$this->assertEqual($second['title'], $documents[1]->title);
		$this->assertEqual($third['title'], $documents[2]->title);

		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
		$result = $documents->result()->resource()->query['sort'];
		$this->assertEqual(array('position' => 1), $result);

		array_push($this->_db->connection->results, new MockResultResource(array(
			'data' => array($third, $second, $first)
		)));
		$documents = MockMongoPost::all(array('order' => array('position' => 'desc')));

		$this->assertEqual($third['title'], $documents[0]->title);
		$this->assertEqual($second['title'], $documents[1]->title);
		$this->assertEqual($first['title'], $documents[2]->title);

		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
		$result = $documents->result()->resource()->query['sort'];
		$this->assertEqual(array('position' => -1), $result);
	}

	public function testMongoIdPreservation() {
		$post = MockMongoPost::create(array('_id' => new MongoId(), 'title' => 'A post'));
		$post->save();
		$result = array_pop($this->_db->connection->queries);
		$data = $result['data'];

		$this->assertEqual('A post', $data['title']);
		$this->assertInstanceOf('MongoId', $data['_id']);

		$post->sync();
		$post->title = 'An updated post';
		$post->save();

		$result = array_pop($this->_db->connection->queries);
		$this->assertEqual(array('_id' => $post->_id), $result['conditions']);
		$this->assertEqual(array('$set' => array('title' => 'An updated post')), $result['update']);
	}

	public function testRelationshipGeneration() {
		$from = 'lithium\tests\mocks\data\MockComment';
		$to = 'lithium\tests\mocks\data\MockPost';

		$from::config(array(
			'meta' => array('connection' => 'mockconn', 'key' => '_id'),
			'schema' => new Schema(array('fields' => array('comment_id')))
		));
		$to::config(array('meta' => array('key' => '_id', 'connection' => 'mockconn')));

		$result = $this->_db->relationship($from, 'belongsTo', 'MockPost');

		$expected = compact('to', 'from') + array(
			'name' => 'MockPost',
			'type' => 'belongsTo',
			'key' => array(),
			'link' => 'contained',
			'fields' => true,
			'fieldName' => 'mockPost',
			'constraints' => array(),
			'init' => true
		);
		$this->assertEqual($expected, $result->data());

		$from::config(array('meta' => array('name' => 'Groups'), 'schema' => new Schema(array(
			'fields' => array('_id' => 'id', 'users' => array('id', 'array' => true))
		))));

		$to::config(array('meta' => array('name' => 'Users'), 'schema' => new Schema(array(
			'fields' => array('_id' => 'id', 'group' => 'id')
		))));

		$result = $this->_db->relationship($from, 'hasMany', 'Users', compact('to'));
		$this->assertEqual('keylist', $result->link());
		$this->assertEqual(array('users' => '_id'), $result->key());

		$to::config(array('meta' => array('name' => 'Permissions')));
		$result = $this->_db->relationship($from, 'hasMany', 'Permissions', compact('to'));
		$this->assertEqual('key', $result->link());
		$this->assertEqual(array('_id' => 'group'), $result->key());
	}

	public function testRelationshipGenerationWithPluralNamingConvention() {
		$from = 'lithium\tests\mocks\data\MockComments';
		$to = 'lithium\tests\mocks\data\MockPosts';

		$from::config(array(
			'meta' => array('connection' => 'mockconn', 'key' => '_id'),
			'schema' => new Schema(array('fields' => array('mockPost' => 'id')))
		));
		$to::config(array(
			'meta' => array('connection' => 'mockconn', 'key' => '_id'),
			'schema' => new Schema(array('fields' => array('mockComments' => 'id')))
		));

		$result = $this->_db->relationship($from, 'belongsTo', 'MockPosts');

		$expected = compact('to', 'from') + array(
			'name' => 'MockPosts',
			'type' => 'belongsTo',
			'key' => array(
				'mockPost' => '_id'
			),
			'link' => 'key',
			'fields' => true,
			'fieldName' => 'mockPost',
			'constraints' => array(),
			'init' => true
		);
		$this->assertEqual($expected, $result->data());
	}

	public function testCreateNoConnectionException() {
		$db = new MongoDb(array('host' => '__invalid__', 'autoConnect' => false));
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->create(null);
		});
	}

	public function testReadNoConnectionException() {
		$db = new MongoDb(array('host' => '__invalid__', 'autoConnect' => false));
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->read(null);
		});
	}

	public function testUpdateNoConnectionException() {
		$db = new MongoDb(array('host' => '__invalid__', 'autoConnect' => false));
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->update(null);
		});
	}

	public function testDeleteNoConnectionException() {
		$db = new MongoDb(array('host' => '__invalid__', 'autoConnect' => false));
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->delete(null);
		});
	}

	public function testSourcesNoConnectionException() {
		$db = new MongoDb(array('host' => null, 'autoConnect' => false));
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->sources(null);
		});
	}

	public function testAtomicUpdate() {
		MockMongoPost::config(array('meta' => array('source' => 'posts')));
		$data = array('initial' => 'one', 'values' => 'two');

		$this->_db->connection = new MockMongoConnection();
		$this->_db->connection->results = array(true, true);

		$document = MockMongoPost::create($data);
		$this->assertTrue($document->save());

		$result = array_shift($this->_db->connection->queries);
		$expected = array(
			'type' => 'insert',
			'collection' => 'posts',
			'data' => array('initial' => 'one', 'values' => 'two', '_id' => $document->_id),
			'options' => array(
				'validate' => true, 'events' => 'create', 'whitelist' => null, 'callbacks' => true,
				'locked' => false, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false
			)
		);
		$this->assertEqual($expected, $result);

		$duplicate = MockMongoPost::create(array('_id' => $document->_id), array('exists' => true));
		$duplicate->values = 'new';
		$this->assertTrue($duplicate->save());

		$result = array_shift($this->_db->connection->queries);
		$expected = array(
			'type' => 'update',
			'collection' => 'posts',
			'conditions' => array('_id' => $document->_id),
			'update' => array('$set' => array('values' => 'new')),
			'options' => array(
				'validate' => true, 'events' => 'update', 'whitelist' => null,
				'callbacks' => true, 'locked' => false, 'upsert' => false, 'multiple' => true,
				'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false
			)
		);
		$this->assertEqual($expected, $result);

		array_push($this->_db->connection->results, new MockResultResource(array('data' => array(
			array('_id' => $duplicate->_id, 'initial' => 'one', 'values' => 'new')
		))));

		$document = MockMongoPost::find($duplicate->_id);
		$expected = array('_id' => (string) $duplicate->_id, 'initial' => 'one', 'values' => 'new');
		$this->assertEqual($expected, $document->data());

		$result = array_shift($this->_db->connection->queries);
		$expected = array(
			'type' => 'find', 'collection' => 'posts', 'fields' => array(), 'conditions' => array(
				'_id' => $duplicate->_id
			)
		);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that the MongoDB adapter will not attempt to overwrite the _id field on document
	 * update.
	 */
	public function testPreserveId() {
		$document = MockMongoPost::create(array('_id' => 'custom'), array('exists' => true));

		array_push($this->_db->connection->results, true);
		$this->assertTrue($document->save(array('_id' => 'custom2', 'foo' => 'bar')));

		$result = array_shift($this->_db->connection->queries);
		$expected = array('$set' => array('foo' => 'bar'));
		$this->assertEqual($expected, $result['update']);
	}

	public function testCastingConditionsValues() {
		$query = new Query(array('schema' => new Schema(array('fields' => $this->_schema))));

		$conditions = array('_id' => new MongoId("4c8f86167675abfabdbe0300"));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertEqual($conditions, $result);

		$conditions = array('_id' => "4c8f86167675abfabdbe0300");
		$result = $this->_db->conditions($conditions, $query);

		$this->assertEqual(array_keys($conditions), array_keys($result));
		$this->assertInstanceOf('MongoId', $result['_id']);
		$this->assertEqual($conditions['_id'], (string) $result['_id']);

		$conditions = array('_id' => array(
			"4c8f86167675abfabdbe0300", "4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
		));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertCount(3, $result['_id']['$in']);

		foreach (array(0, 1, 2) as $i) {
			$this->assertInstanceOf('MongoId', $result['_id']['$in'][$i]);
		}

		$conditions = array('voters' => array('$all' => array(
			"4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
		)));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertCount(2, $result['voters']['$all']);
		$result = $result['voters']['$all'];

		foreach (array(0, 1) as $i) {
			$this->assertInstanceOf('MongoId', $result[$i]);
			$this->assertEqual($conditions['voters']['$all'][$i], (string) $result[$i]);
		}

		$conditions = array('$or' => array(
			array('_id' => "4c8f86167675abfabdbf0300"),
			array('guid' => "4c8f86167675abfabdbf0300")
		));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertEqual(array('$or'), array_keys($result));
		$this->assertCount(2, $result['$or']);

		foreach (array('_id', 'guid') as $i => $key) {
			$this->assertInstanceOf('MongoId', $result['$or'][$i][$key]);
			$this->assertEqual($conditions['$or'][$i][$key], (string) $result['$or'][$i][$key]);
		}
	}

	public function testCastingElemMatchValuesInConditions(){
		$query = new Query(array(
			'schema' => new Schema(array(
				'fields' => array(
					'_id' => array('type' => 'id'),
					'members' => array('type' => 'object', 'array' => true),
					'members.user_id' => array('type' => 'id'),
					'members.pattern' => array('type' => 'regex'),
				)
			))
		));

		$user_id = new MongoId();
		$conditions = array('members' => array(
			'$elemMatch' => array(
				'user_id' => (string) $user_id,
				'pattern' => '/test/i',
			)
		));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertEqual($conditions, $result);
		$this->assertInstanceOf('MongoId', $result['members']['$elemMatch']['user_id']);
		$this->assertInstanceOf('MongoRegex', $result['members']['$elemMatch']['pattern']);
	}

	public function testNotCastingConditionsForSpecialQueryOpts(){
		$query = new Query(array(
			'schema' => new Schema(array('fields' => $this->_schema))
		));

		$conditions = array('title' => array('$exists' => true));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = array('title' => array('$size' => 1));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = array('title' => array('$type' => 1));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = array('title' => array('$mod' => array(3)));
		$result = $this->_db->conditions($conditions, $query);
		$expected = array('title' => array('$mod' => array(3, 0)));
		$this->assertIdentical($expected, $result);

		$conditions = array('tags' => array('$exists' => true));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = array('tags' => array('$size' => 1));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = array('tags' => array('$type' => 1));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = array('created' => array('$mod' => array(7, 0)));
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);
	}

	public function testMultiOperationConditions() {
		$conditions = array('loc' => array('$near' => array(50, 50), '$maxDistance' => 5));
		$result = $this->_db->conditions($conditions, $this->_query);
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
		$result = $query->export($this->_db);
		$this->assertIdentical($data, $result['data']['data']);
	}

	public function testUpdateWithEmbeddedObjects() {
		$data = array(
			'_id' => new MongoId(),
			'created' => new MongoDate(strtotime('-1 hour')),
			'list' => array('foo', 'bar', 'baz')
		);

		$fields = array('updated' => array('type' => 'MongoDate'));
		$schema = new Schema(compact('fields'));
		$entity = new Document(compact('data', 'schema', 'model') + array('exists' => true));
		$entity->updated = time();
		$entity->list[] = 'dib';

		$query = new Query(array('type' => 'update') + compact('entity'));
		$result = $query->export($this->_db);
		$expected = array('_id', 'created', 'list', 'updated');
		$this->assertEqual($expected, array_keys($result['data']['update']));
		$this->assertInstanceOf('MongoDate', $result['data']['update']['updated']);
	}

	/**
	 * Assert that Mongo and the Mongo Exporter don't mangle manual geospatial queries.
	 */
	public function testGeoQueries() {
		$coords = array(84.13, 11.38);
		$coords2 = array_map(function($point) { return $point + 5; }, $coords);
		$conditions = array('location' => array('$near' => $coords));

		$query = new Query(compact('conditions') + array('model' => $this->_model));
		$result = $query->export($this->_db);
		$this->assertEqual($result['conditions'], $conditions);

		$conditions = array('location' => array(
			'$within' => array('$box' => array($coords2, $coords))
		));
		$query = new Query(compact('conditions') + array('model' => $this->_model));
		$result = $query->export($this->_db);
		$this->assertEqual($conditions, $result['conditions']);
	}

	public function testSchemaCallback() {
		$schema = array('_id' => array('type' => 'id'), 'created' => array('type' => 'date'));
		$db = new MongoDb(array('autoConnect' => false, 'schema' => function() use ($schema) {
			return $schema;
		}));
		$this->assertEqual($schema, $db->describe(null)->fields());
	}

	public function testSetReadPreference() {
		$prefs = array(
			"SECONDARY",
			array('dc' => 'east', 'use' => 'reporting')
		);
		$db = new MongoDb(array(
			'autoConnect' => true,
			'readPreference' => $prefs,
			'classes' => array(
				'server' => 'lithium\tests\mocks\core\MockCallable'
			)
		));

		$result = $db->server->call;
		$this->assertEqual('setReadPreference', $result['method']);
		$this->assertEqual($prefs, $result['params']);
	}

	public function testDefaultSafeOptions() {
		$this->_db = new MongoDb($this->_testConfig + array('w' => 1, 'wTimeoutMS' => 10000));
		$this->_db->server = new MockMongoConnection();
		$this->_db->connection = new MockCallable();
		$this->_db->connection->custom = new MockCallable();

		$query = new Query(array('type' => 'read', 'source' => 'custom'));
		$this->_db->create($query);
		$result = $this->_db->connection->custom->call;
		$expected = array(null, array('w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false));
		$this->assertEqual('insert', $result['method']);
		$this->assertEqual($expected, $result['params']);

		$query = new Query(array('type' => 'read', 'source' => 'custom', 'data' => array('something')));
		$this->_db->update($query);
		$result = $this->_db->connection->custom->call;
		$expected = array('upsert' => false, 'multiple' => true, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false);
		$this->assertEqual('update', $result['method']);
		$this->assertEqual($expected, $result['params'][2]);

		$query = new Query(array('type' => 'read', 'source' => 'custom'));
		$this->_db->delete($query);
		$result = $this->_db->connection->custom->call;
		$expected = array('justOne' => false, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false);
		$this->assertEqual('remove', $result['method']);
		$this->assertEqual($expected, $result['params'][1]);

		$this->_db = new MongoDb($this->_testConfig + array('w' => 1, 'wTimeoutMS' => 10000));
		$this->_db->server = new MockMongoConnection();
		$this->_db->connection = new MockCallable();
		$this->_db->connection->custom = new MockCallable();

		$query = new Query(array('type' => 'read', 'source' => 'custom'));
		$this->_db->create($query);
		$result = $this->_db->connection->custom->call;
		$expected = array(null, array('w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false));
		$this->assertEqual('insert', $result['method']);
		$this->assertEqual($expected, $result['params']);

		$query = new Query(array('type' => 'read', 'source' => 'custom', 'data' => array('something')));
		$this->_db->update($query);
		$result = $this->_db->connection->custom->call;
		$expected = array(
			'upsert' => false, 'multiple' => true, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false
		);
		$this->assertEqual('update', $result['method']);
		$this->assertEqual($expected, $result['params'][2]);

		$query = new Query(array('type' => 'read', 'source' => 'custom'));
		$this->_db->delete($query);
		$result = $this->_db->connection->custom->call;
		$expected = array('justOne' => false, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false);
		$this->assertEqual('remove', $result['method']);
		$this->assertEqual($expected, $result['params'][1]);
	}

	public function testGridFsCRUDWithDefaultPrefix() {
		$source = 'fs.files';
		$data = array('filename' => 'lithium', 'file' => 'some_datas');

		MockMongoPost::config(array('meta' => array('source' => $source)));
		$this->assertTrue(MockMongoPost::create()->save($data));
		$this->assertIdentical('fs', $this->_db->connection->gridFsPrefix);
		$this->_db->connection->gridFsPrefix = null;

		MockMongoPost::config(array('meta' => array('source' => $source)));
		$this->_db->connection->results = array(new MockResultResource(array('data' => $data)));
		$this->assertNotEmpty(MockMongoPost::find('all'));
		$this->assertIdentical('fs', $this->_db->connection->gridFsPrefix);
		$this->_db->connection->gridFsPrefix = null;

		MockMongoPost::create($data + array('_id' => new MongoId), array('exists' => true))->delete();
		$this->assertIdentical('fs', $this->_db->connection->gridFsPrefix);
		$this->_db->connection->gridFsPrefix = null;

	}

	public function testGridFsCreateWithCustomPrefix() {
		$data = array('filename' => 'lithium', 'file' => 'some_datas');

		$db = new MongoDb($this->_testConfig + array('gridPrefix' => 'custom'));
		$db->server = new MockMongoConnection();
		$db->connection = new MockMongoConnection();
		Connections::add('temp', array('object' => $db));
		MockMongoPost::config(array('meta' => array('source' => 'fs.files', 'connection' => 'temp')));

		MockMongoPost::config(array('meta' => array('source' => 'fs.files')));
		$this->assertFalse(MockMongoPost::create()->save($data));
		$this->assertIdentical(null, $db->connection->gridFsPrefix);

		MockMongoPost::config(array('meta' => array('source' => 'custom.files')));
		$this->assertTrue(MockMongoPost::create()->save($data));
		$this->assertIdentical('custom', $db->connection->gridFsPrefix);
		Connections::remove('temp');
	}

	public function testGridFsReadWithCustomPrefix() {
		$data = array('filename' => 'lithium', 'file' => 'some_datas');
		$result = new MockResultResource(array('data' => array(
			array('filename' => 'lithium', 'file' => 'some_datas')
		)));

		$db = new MongoDb($this->_testConfig + array('gridPrefix' => 'custom'));
		$db->server = new MockMongoConnection();
		$db->connection = new MockMongoConnection();
		Connections::add('temp', array('object' => $db));
		MockMongoPost::config(array('meta' => array('source' => 'fs.files', 'connection' => 'temp')));
		$db->connection->results = array($result);
		$this->assertNotEmpty(MockMongoPost::find('all'));
		$this->assertIdentical(null, $db->connection->gridFsPrefix);

		MockMongoPost::config(array('meta' => array('source' => 'custom.files')));
		$db->connection->results = array($result);
		$this->assertNotEmpty(MockMongoPost::find('all'));
		$this->assertIdentical('custom', $db->connection->gridFsPrefix);
		Connections::remove('temp');
	}

	public function testGridFsDeleteWithCustomPrefix() {
		$data = array('_id' => new MongoId);

		$db = new MongoDb($this->_testConfig + array('gridPrefix' => 'custom'));
		$db->server = new MockMongoConnection();
		$db->connection = new MockMongoConnection();
		Connections::add('temp', array('object' => $db));
		MockMongoPost::config(array('meta' => array('source' => 'fs.files', 'connection' => 'temp')));

		MockMongoPost::create($data, array('exists' => true))->delete();
		$this->assertIdentical(null, $db->connection->gridFsPrefix);

		MockMongoPost::config(array('meta' => array('source' => 'custom.files')));
		MockMongoPost::create($data, array('exists' => true))->delete();
		$this->assertIdentical('custom', $db->connection->gridFsPrefix);
		Connections::remove('temp');
	}

	public function testRespondsToParentCall() {
		$db = new MongoDb($this->_testConfig);
		$this->assertTrue($db->respondsTo('_parents'));
		$this->assertFalse($db->respondsTo('fooBarBaz'));
	}

	public function testRespondsToWithNoServer() {
		$db = new MongoDb($this->_testConfig);
		$this->assertFalse($db->respondsTo('listDBs'));
		$this->assertFalse($db->respondsTo('foobarbaz'));
	}

	public function testRespondsToWithServer() {
		$db = new MongoDb($this->_testConfig);
		$db->server = new MockMongoConnection();
		$this->assertTrue($db->respondsTo('listDBs'));
		$this->assertFalse($db->respondsTo('foobarbaz'));
	}

}

?>