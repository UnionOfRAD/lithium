<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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

	protected $_testConfig = [
		'adapter' => false,
		'database' => 'test',
		'host' => 'localhost',
		'port' => '27017',
		'autoConnect' => false
	];

	protected $_schema = [
		'_id'               => 'id',
		'guid'              => 'id',
		'title'             => 'string',
		'tags'              => ['type' => 'string', 'array' => true],
		'comments'          => 'MongoId',
		'authors'           => ['type' => 'MongoId', 'array' => true],
		'created'           => 'MongoDate',
		'modified'          => 'datetime',
		'voters'            => ['type' => 'id', 'array' => true],
		'rank_count'        => ['type' => 'integer', 'default' => 0],
		'rank'              => ['type' => 'float', 'default' => 0.0],
		'notifications.foo' => 'boolean',
		'notifications.bar' => 'boolean',
		'notifications.baz' => 'boolean'
	];

	protected $_configs = [];

	public function skip() {
		$this->skipIf(!MongoDb::enabled(), 'The `MongoDb` class is not enabled.');
	}

	public function setUp() {
		$this->_db = new MongoDb($this->_testConfig);
		$this->_db->server = new MockMongoConnection();
		$this->_db->connection = new MockMongoConnection();

		Connections::add('mockconn', ['object' => $this->_db]);
		MockMongoPost::config(['meta' => ['key' => '_id', 'connection' => 'mockconn']]);

		$type = 'create';
		$this->_query = new Query(compact('model', 'type') + [
			'entity' => new Document(['model' => $this->_model])
		]);
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockPost::reset();
		MockComment::reset();
		MockMongoPost::reset();
	}

	public function testBadConnection() {
		$db = new MongoDb(['host' => null, 'autoConnect' => false]);
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->connect();
		});
		$this->assertTrue($db->disconnect());
	}

	public function testGoodConnectionBadDatabase() {
		$db = new MongoDb(['database' => null, 'autoConnnect' => false]);

		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->connect();
		});
	}

	public function testSources() {
		$this->_db->connection->results = [[]];
		$this->assertEqual([], $this->_db->sources());
	}

	public function testDescribe() {
		$result = $this->_db->describe('test')->fields();
		$expected = ['_id' => ['type' => 'id']];
		$this->assertEqual($expected, $result);
	}

	public function testName() {
		$result = $this->_db->name('{(\'Li\':"∆")}');
		$expected = '{(\'Li\':"∆")}';
		$this->assertEqual($expected, $result);
	}

	public function testSchema() {
		$result = $this->_db->schema($this->_query);
		$expected = [];
		$this->assertEqual($expected, $result);
	}

	public function testCreateSuccess() {
		array_push($this->_db->connection->results, true);
		$this->_query->data(['title' => 'Test Post']);
		$this->assertTrue($this->_db->create($this->_query));

		$query = array_pop($this->_db->connection->queries);

		$this->assertEmpty($this->_db->connection->queries);
		$this->assertEqual('insert', $query['type']);
		$this->assertEqual('posts', $query['collection']);
		$this->assertEqual(['title', '_id'], array_keys($query['data']));
		$this->assertInstanceOf('MongoId', $query['data']['_id']);
	}

	public function testConditions() {
		$result = $this->_db->conditions(null, null);
		$this->assertEqual([], $result);

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

		$conditions = ['key' => 'value', 'anotherkey' => 'some other value'];
		$result = $this->_db->conditions($conditions, null);
		$this->assertInternalType('array', $result);
		$this->assertEqual($conditions, $result);

		$conditions = ['key' => ['one', 'two', 'three']];
		$result = $this->_db->conditions($conditions, null);
		$this->assertInternalType('array', $result);
		$this->assertTrue(isset($result['key']));
		$this->assertTrue(isset($result['key']['$in']));
		$this->assertEqual($conditions['key'], $result['key']['$in']);

		$conditions = ['$or' => [
			['key' => 'value'],
			['other key' => 'another value']
		]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertTrue(isset($result['$or']));
		$this->assertEqual($conditions['$or'][0]['key'], $result['$or'][0]['key']);

		$conditions = ['$and' => [
			['key' => 'value'],
			['other key' => 'another value']
		]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertTrue(isset($result['$and']));
		$this->assertEqual($conditions['$and'][0]['key'], $result['$and'][0]['key']);

		$conditions = ['$nor' => [
			['key' => 'value'],
			['other key' => 'another value']
		]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertTrue(isset($result['$nor']));
		$this->assertEqual($conditions['$nor'][0]['key'], $result['$nor'][0]['key']);

		$conditions = ['key' => ['or' => [1, 2]]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual(['key' => ['$or' => [1, 2]]], $result);
	}

	public function testMongoConditionalOperators() {
		$conditions = ['key' => ['<' => 10]];
		$expected = ['key' => ['$lt' => 10]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = ['key' => ['<=' => 10]];
		$expected = ['key' => ['$lte' => 10]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = ['key' => ['>' => 10]];
		$expected = ['key' => ['$gt' => 10]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = ['key' => ['>=' => 10]];
		$expected = ['key' => ['$gte' => 10]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = ['key' => ['!=' => 10]];
		$expected = ['key' => ['$ne' => 10]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = ['key' => ['<>' => 10]];
		$expected = ['key' => ['$ne' => 10]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = ['key' => ['!=' => [10, 20, 30]]];
		$expected = ['key' => ['$nin' => [10, 20, 30]]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = ['key' => ['<>' => [10, 20, 30]]];
		$expected = ['key' => ['$nin' => [10, 20, 30]]];
		$result = $this->_db->conditions($conditions, null);
		$this->assertEqual($expected, $result);

		$conditions = ['key' => ['like' => '/regex/i']];
		$result = $this->_db->conditions($conditions, null);
		$expected = ['key' => new MongoRegex('/regex/i')];
		$this->assertEqual($expected, $result);
	}

	public function testConditionsWithSchema() {
		$schema = new Schema(['fields' => [
			'_id' => ['type' => 'id'],
			'tags' => ['type' => 'string', 'array' => true],
			'users' => ['type' => 'id', 'array' => true]
		]]);

		$query = new Query(['schema' => $schema, 'type' => 'read']);

		$id = new MongoId();
		$userId = new MongoId();

		$conditions = [
			'_id' => (string) $id,
			'tags' => 'yellow',
			'users' => (string) $userId
		];
		$result = $this->_db->conditions($conditions, $query);

		$expected = [
			'_id' => $id,
			'tags' => 'yellow',
			'users' => $userId
		];
		$this->assertEqual($expected, $result);
	}

	public function testReadNoConditions() {
		$this->_db->connect();
		$connection = $this->_db->connection;
		$this->_db->connection = new MockMongoSource();
		$this->_db->connection->resultSets = [['ok' => true]];

		$data = ['title' => 'Test Post'];
		$options = ['w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false];
		$this->_query->data($data);
		$this->assertTrue($this->_db->create($this->_query));
		$this->assertEqual(compact('data', 'options'), end($this->_db->connection->queries));

		$this->_db->connection->resultSets = [[['_id' => new MongoId()] + $data]];
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
		$this->_db->connection->resultSets = [['ok' => true]];

		$data = ['title' => 'Test Post'];
		$options = ['w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false];
		$this->_query->data($data);
		$this->assertTrue($this->_db->create($this->_query));
		$this->_query->data(null);

		$this->_db->connection->resultSets = [[]];
		$this->_query->conditions(['title' => 'Nonexistent Post']);
		$result = $this->_db->read($this->_query);
		$this->assertNotEmpty($result);
		$this->assertEqual(0, $result->count());

		$this->_db->connection->resultSets = [[$data]];
		$this->_query->conditions($data);
		$result = $this->_db->read($this->_query);
		$this->assertNotEmpty($result);
		$this->assertEqual(1, $result->count());
		$this->_db->connection = $connection;
	}

	public function testUpdate() {
		$model = $this->_model;
		$data = ['title' => 'Test Post'];

		$this->_query->model($model);
		$this->_query->data($data);
		$this->_db->connection->results = [true];
		$this->_db->create($this->_query);

		$result = array_pop($this->_db->connection->queries);
		$data['_id'] = $result['data']['_id'];

		$expected = compact('data') + [
			'collection' => 'posts',
			'type' => 'insert',
			'options' => ['w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false]
		];
		$this->assertEqual($expected, $result);

		$this->_db->connection->results = [
			new MockResultResource(['data' => [$data]]),
			new MockResultResource(['data' => [$data]])
		];
		$this->_db->connection->queries = [];

		$result = $this->_db->read(new Query(compact('model')));
		$original = $result->first()->to('array');

		$this->assertEqual(['title', '_id'], array_keys($original));
		$this->assertEqual('Test Post', $original['title']);
		$this->assertPattern('/^[0-9a-f]{24}$/', $original['_id']);

		$this->_db->connection->results = [true];
		$this->_db->connection->queries = [];
		$update = ['title' => 'New Post Title'];

		$this->_query = new Query(compact('model') + [
			'data' => $update,
			'conditions' => ['_id' => $original['_id']]
		]);
		$this->assertTrue($this->_db->update($this->_query));

		$result = array_pop($this->_db->connection->queries);
		$expected = [
			'type' => 'update',
			'collection' => 'posts',
			'conditions' => ['_id' => '4f188fb17675ab167900010e'],
			'update' => ['$set' => ['title' => 'New Post Title']],
			'options' => [
				'upsert' => false, 'multiple' => true, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false
			]
		];

		array_push($this->_db->connection->results, new MockResultResource([
			'data' => [$update + $original]
		]));
		$this->_db->connection->queries = [];

		$result = $this->_db->read(new Query(compact('model') + [
			'conditions' => ['_id' => $original['_id']]
		]));
		$this->assertEqual(1, $result->count());

		$updated = $result->first();
		$updated = $updated ? $updated->to('array') : [];
		$this->assertEqual($original['_id'], $updated['_id']);
		$this->assertEqual('New Post Title', $updated['title']);

		$expected = [
			'type' => 'find',
			'collection' => 'posts',
			'fields' => [],
			'conditions' => ['_id' => $original['_id']]
		];
		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
	}

	public function testDelete() {
		$model = $this->_model;
		$data = ['title' => 'Delete Me'];

		array_push($this->_db->connection->results, true);
		$this->_query->data($data);
		$this->_db->create($this->_query);

		array_push($this->_db->connection->results, new MockResultResource([
			'data' => []
		]));
		$this->assertFalse($this->_db->read($this->_query)->first());

		$result = array_pop($this->_db->connection->queries);
		$conditions = ['_id' => $this->_query->entity()->_id];
		$this->assertEqual($conditions, $result['conditions']);
		$this->assertTrue($this->_query->entity()->exists());

		$id = new MongoId();
		$this->_query = new Query(compact('model') + [
			'entity' => new Document(compact('model') + ['data' => ['_id' => $id]])
		]);

		array_push($this->_db->connection->results, true);
		$this->_query->conditions($conditions);
		$this->assertTrue($this->_db->delete($this->_query));
		$this->assertFalse($this->_query->entity()->exists());

		$expected = compact('conditions') + [
			'type' => 'remove',
			'collection' => 'posts',
			'options' => ['justOne' => false, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false]
		];
		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
	}

	public function testCreate() {
		$data = ['title' => 'New Item'];
		$result = MockMongoPost::create($data, ['defaults' => false]);
		$this->assertInstanceOf('lithium\data\entity\Document', $result);

		$expected = $data;
		$result = $result->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testCalculation() {
		$this->_db->connection->results = [new MockResultResource(['data' => [5]])];
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
		MockMongoPost::config(['meta' => ['source' => 'ordered_docs', 'locked' => false]]);

		$first = ['title' => 'First document',  'position' => 1];
		$second = ['title' => 'Second document', 'position' => 2];
		$third = ['title' => 'Third document',  'position' => 3];

		MockMongoPost::create($third)->save();
		MockMongoPost::create($first)->save();
		MockMongoPost::create($second)->save();

		$result = $this->_db->connection->queries;
		$createOpts = [
			'validate' => true,
			'events' => 'create',
			'whitelist' => null,
			'callbacks' => true,
			'locked' => false,
			'w' => 1,
			'wTimeoutMS' => 10000,
			'fsync' => false
		];
		$baseInsert = [
			'type' => 'insert',
			'collection' => 'ordered_docs',
			'options' => $createOpts
		];

		$expected = [
			$baseInsert + ['data' => ['_id' => $result[0]['data']['_id']] + $third],
			$baseInsert + ['data' => ['_id' => $result[1]['data']['_id']] + $first],
			$baseInsert + ['data' => ['_id' => $result[2]['data']['_id']] + $second]
		];
		$this->assertEqual($expected, $result);

		array_push($this->_db->connection->results, new MockResultResource([
			'data' => [$first, $second, $third]
		]));
		$this->_db->connection->queries = [];
		$documents = MockMongoPost::all(['order' => 'position']);

		$this->assertEqual($first['title'], $documents[0]->title);
		$this->assertEqual($second['title'], $documents[1]->title);
		$this->assertEqual($third['title'], $documents[2]->title);

		$expected = [
			'type' => 'find',
			'collection' => 'ordered_docs',
			'conditions' => [],
			'fields' => []
		];
		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
		$result = $documents->result()->resource()->query['sort'];
		$this->assertEqual(['position' => 1], $result);

		array_push($this->_db->connection->results, new MockResultResource([
			'data' => [$first, $second, $third]
		]));
		$documents = MockMongoPost::all(['order' => ['position' => 'asc']]);

		$this->assertEqual($first['title'], $documents[0]->title);
		$this->assertEqual($second['title'], $documents[1]->title);
		$this->assertEqual($third['title'], $documents[2]->title);

		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
		$result = $documents->result()->resource()->query['sort'];
		$this->assertEqual(['position' => 1], $result);

		array_push($this->_db->connection->results, new MockResultResource([
			'data' => [$third, $second, $first]
		]));
		$documents = MockMongoPost::all(['order' => ['position' => 'desc']]);

		$this->assertEqual($third['title'], $documents[0]->title);
		$this->assertEqual($second['title'], $documents[1]->title);
		$this->assertEqual($first['title'], $documents[2]->title);

		$this->assertEqual($expected, array_pop($this->_db->connection->queries));
		$result = $documents->result()->resource()->query['sort'];
		$this->assertEqual(['position' => -1], $result);
	}

	public function testMongoIdPreservation() {
		$post = MockMongoPost::create(['_id' => new MongoId(), 'title' => 'A post']);
		$post->save();
		$result = array_pop($this->_db->connection->queries);
		$data = $result['data'];

		$this->assertEqual('A post', $data['title']);
		$this->assertInstanceOf('MongoId', $data['_id']);

		$post->sync();
		$post->title = 'An updated post';
		$post->save();

		$result = array_pop($this->_db->connection->queries);
		$this->assertEqual(['_id' => $post->_id], $result['conditions']);
		$this->assertEqual(['$set' => ['title' => 'An updated post']], $result['update']);
	}

	public function testRelationshipGeneration() {
		$from = 'lithium\tests\mocks\data\MockComment';
		$to = 'lithium\tests\mocks\data\MockPost';

		$from::config([
			'meta' => ['connection' => 'mockconn', 'key' => '_id'],
			'schema' => new Schema(['fields' => ['comment_id']])
		]);
		$to::config(['meta' => ['key' => '_id', 'connection' => 'mockconn']]);

		$result = $this->_db->relationship($from, 'belongsTo', 'MockPost');

		$expected = compact('to', 'from') + [
			'name' => 'MockPost',
			'type' => 'belongsTo',
			'key' => [],
			'link' => 'contained',
			'fields' => true,
			'fieldName' => 'mockPost',
			'constraints' => [],
			'init' => true
		];
		$this->assertEqual($expected, $result->data());

		$from::config(['meta' => ['name' => 'Groups'], 'schema' => new Schema([
			'fields' => ['_id' => 'id', 'users' => ['id', 'array' => true]]
		])]);

		$to::config(['meta' => ['name' => 'Users'], 'schema' => new Schema([
			'fields' => ['_id' => 'id', 'group' => 'id']
		])]);

		$result = $this->_db->relationship($from, 'hasMany', 'Users', compact('to'));
		$this->assertEqual('keylist', $result->link());
		$this->assertEqual(['users' => '_id'], $result->key());

		$to::config(['meta' => ['name' => 'Permissions']]);
		$result = $this->_db->relationship($from, 'hasMany', 'Permissions', compact('to'));
		$this->assertEqual('key', $result->link());
		$this->assertEqual(['_id' => 'group'], $result->key());
	}

	public function testRelationshipGenerationWithPluralNamingConvention() {
		$from = 'lithium\tests\mocks\data\MockComments';
		$to = 'lithium\tests\mocks\data\MockPosts';

		$from::config([
			'meta' => ['connection' => 'mockconn', 'key' => '_id'],
			'schema' => new Schema(['fields' => ['mockPost' => 'id']])
		]);
		$to::config([
			'meta' => ['connection' => 'mockconn', 'key' => '_id'],
			'schema' => new Schema(['fields' => ['mockComments' => 'id']])
		]);

		$result = $this->_db->relationship($from, 'belongsTo', 'MockPosts');

		$expected = compact('to', 'from') + [
			'name' => 'MockPosts',
			'type' => 'belongsTo',
			'key' => [
				'mockPost' => '_id'
			],
			'link' => 'key',
			'fields' => true,
			'fieldName' => 'mockPost',
			'constraints' => [],
			'init' => true
		];
		$this->assertEqual($expected, $result->data());
	}

	public function testCreateNoConnectionException() {
		$db = new MongoDb(['host' => '__invalid__', 'autoConnect' => false]);
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->create(null);
		});
	}

	public function testReadNoConnectionException() {
		$db = new MongoDb(['host' => '__invalid__', 'autoConnect' => false]);
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->read(null);
		});
	}

	public function testUpdateNoConnectionException() {
		$db = new MongoDb(['host' => '__invalid__', 'autoConnect' => false]);
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->update(null);
		});
	}

	public function testDeleteNoConnectionException() {
		$db = new MongoDb(['host' => '__invalid__', 'autoConnect' => false]);
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->delete(null);
		});
	}

	public function testSourcesNoConnectionException() {
		$db = new MongoDb(['host' => null, 'autoConnect' => false]);
		$this->assertException('Could not connect to the database.', function() use ($db) {
			$db->sources(null);
		});
	}

	public function testAtomicUpdate() {
		MockMongoPost::config(['meta' => ['source' => 'posts']]);
		$data = ['initial' => 'one', 'values' => 'two'];

		$this->_db->connection = new MockMongoConnection();
		$this->_db->connection->results = [true, true];

		$document = MockMongoPost::create($data);
		$this->assertTrue($document->save());

		$result = array_shift($this->_db->connection->queries);
		$expected = [
			'type' => 'insert',
			'collection' => 'posts',
			'data' => ['initial' => 'one', 'values' => 'two', '_id' => $document->_id],
			'options' => [
				'validate' => true, 'events' => 'create', 'whitelist' => null, 'callbacks' => true,
				'locked' => false, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false
			]
		];
		$this->assertEqual($expected, $result);

		$duplicate = MockMongoPost::create(['_id' => $document->_id], ['exists' => true]);
		$duplicate->values = 'new';
		$this->assertTrue($duplicate->save());

		$result = array_shift($this->_db->connection->queries);
		$expected = [
			'type' => 'update',
			'collection' => 'posts',
			'conditions' => ['_id' => $document->_id],
			'update' => ['$set' => ['values' => 'new']],
			'options' => [
				'validate' => true, 'events' => 'update', 'whitelist' => null,
				'callbacks' => true, 'locked' => false, 'upsert' => false, 'multiple' => true,
				'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false
			]
		];
		$this->assertEqual($expected, $result);

		array_push($this->_db->connection->results, new MockResultResource(['data' => [
			['_id' => $duplicate->_id, 'initial' => 'one', 'values' => 'new']
		]]));

		$document = MockMongoPost::find($duplicate->_id);
		$expected = ['_id' => (string) $duplicate->_id, 'initial' => 'one', 'values' => 'new'];
		$this->assertEqual($expected, $document->data());

		$result = array_shift($this->_db->connection->queries);
		$expected = [
			'type' => 'find', 'collection' => 'posts', 'fields' => [], 'conditions' => [
				'_id' => $duplicate->_id
			]
		];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that the MongoDB adapter will not attempt to overwrite the _id field on document
	 * update.
	 */
	public function testPreserveId() {
		$document = MockMongoPost::create(['_id' => 'custom'], ['exists' => true]);

		array_push($this->_db->connection->results, true);
		$this->assertTrue($document->save(['_id' => 'custom2', 'foo' => 'bar']));

		$result = array_shift($this->_db->connection->queries);
		$expected = ['$set' => ['foo' => 'bar']];
		$this->assertEqual($expected, $result['update']);
	}

	public function testCastingConditionsValues() {
		$query = new Query(['schema' => new Schema(['fields' => $this->_schema])]);

		$conditions = ['_id' => new MongoId("4c8f86167675abfabdbe0300")];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertEqual($conditions, $result);

		$conditions = ['_id' => "4c8f86167675abfabdbe0300"];
		$result = $this->_db->conditions($conditions, $query);

		$this->assertEqual(array_keys($conditions), array_keys($result));
		$this->assertInstanceOf('MongoId', $result['_id']);
		$this->assertEqual($conditions['_id'], (string) $result['_id']);

		$conditions = ['_id' => [
			"4c8f86167675abfabdbe0300", "4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
		]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertCount(3, $result['_id']['$in']);

		foreach ([0, 1, 2] as $i) {
			$this->assertInstanceOf('MongoId', $result['_id']['$in'][$i]);
		}

		$conditions = ['voters' => ['$all' => [
			"4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
		]]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertCount(2, $result['voters']['$all']);
		$result = $result['voters']['$all'];

		foreach ([0, 1] as $i) {
			$this->assertInstanceOf('MongoId', $result[$i]);
			$this->assertEqual($conditions['voters']['$all'][$i], (string) $result[$i]);
		}

		$conditions = ['$or' => [
			['_id' => "4c8f86167675abfabdbf0300"],
			['guid' => "4c8f86167675abfabdbf0300"]
		]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertEqual(['$or'], array_keys($result));
		$this->assertCount(2, $result['$or']);

		foreach (['_id', 'guid'] as $i => $key) {
			$this->assertInstanceOf('MongoId', $result['$or'][$i][$key]);
			$this->assertEqual($conditions['$or'][$i][$key], (string) $result['$or'][$i][$key]);
		}
	}

	public function testCastingElemMatchValuesInConditions(){
		$query = new Query([
			'schema' => new Schema([
				'fields' => [
					'_id' => ['type' => 'id'],
					'members' => ['type' => 'object', 'array' => true],
					'members.user_id' => ['type' => 'id'],
					'members.pattern' => ['type' => 'regex'],
				]
			])
		]);

		$user_id = new MongoId();
		$conditions = ['members' => [
			'$elemMatch' => [
				'user_id' => (string) $user_id,
				'pattern' => '/test/i',
			]
		]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertEqual($conditions, $result);
		$this->assertInstanceOf('MongoId', $result['members']['$elemMatch']['user_id']);
		$this->assertInstanceOf('MongoRegex', $result['members']['$elemMatch']['pattern']);
	}

	public function testNotCastingConditionsForSpecialQueryOpts(){
		$query = new Query([
			'schema' => new Schema(['fields' => $this->_schema])
		]);

		$conditions = ['title' => ['$exists' => true]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = ['title' => ['$size' => 1]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = ['title' => ['$type' => 1]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = ['title' => ['$mod' => [3]]];
		$result = $this->_db->conditions($conditions, $query);
		$expected = ['title' => ['$mod' => [3, 0]]];
		$this->assertIdentical($expected, $result);

		$conditions = ['tags' => ['$exists' => true]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = ['tags' => ['$size' => 1]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = ['tags' => ['$type' => 1]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);

		$conditions = ['created' => ['$mod' => [7, 0]]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertIdentical($conditions, $result);
	}

	public function testMultiOperationConditions() {
		$conditions = ['loc' => ['$near' => [50, 50], '$maxDistance' => 5]];
		$result = $this->_db->conditions($conditions, $this->_query);
		$this->assertEqual($conditions, $result);
	}

	public function testCreateWithEmbeddedObjects() {
		$data = [
			'_id' => new MongoId(),
			'created' => new MongoDate(strtotime('-1 hour')),
			'list' => ['foo', 'bar', 'baz']
		];
		$entity = new Document(compact('data') + ['exists' => false]);
		$query = new Query(['type' => 'create'] + compact('entity'));
		$result = $query->export($this->_db);
		$this->assertIdentical($data, $result['data']['data']);
	}

	public function testUpdateWithEmbeddedObjects() {
		$data = [
			'_id' => new MongoId(),
			'created' => new MongoDate(strtotime('-1 hour')),
			'list' => ['foo', 'bar', 'baz']
		];

		$fields = ['updated' => ['type' => 'MongoDate']];
		$schema = new Schema(compact('fields'));
		$entity = new Document(compact('data', 'schema', 'model') + ['exists' => true]);
		$entity->updated = time();
		$entity->list[] = 'dib';

		$query = new Query(['type' => 'update'] + compact('entity'));
		$result = $query->export($this->_db);
		$expected = ['_id', 'created', 'list', 'updated'];
		$this->assertEqual($expected, array_keys($result['data']['update']));
		$this->assertInstanceOf('MongoDate', $result['data']['update']['updated']);
	}

	/**
	 * Assert that Mongo and the Mongo Exporter don't mangle manual geospatial queries.
	 */
	public function testGeoQueries() {
		$coords = [84.13, 11.38];
		$coords2 = array_map(function($point) { return $point + 5; }, $coords);
		$conditions = ['location' => ['$near' => $coords]];

		$query = new Query(compact('conditions') + ['model' => $this->_model]);
		$result = $query->export($this->_db);
		$this->assertEqual($result['conditions'], $conditions);

		$conditions = ['location' => [
			'$within' => ['$box' => [$coords2, $coords]]
		]];
		$query = new Query(compact('conditions') + ['model' => $this->_model]);
		$result = $query->export($this->_db);
		$this->assertEqual($conditions, $result['conditions']);
	}

	public function testSchemaCallback() {
		$schema = ['_id' => ['type' => 'id'], 'created' => ['type' => 'date']];
		$db = new MongoDb(['autoConnect' => false, 'schema' => function() use ($schema) {
			return $schema;
		}]);
		$this->assertEqual($schema, $db->describe(null)->fields());
	}

	public function testSetReadPreference() {
		$prefs = [
			"SECONDARY",
			['dc' => 'east', 'use' => 'reporting']
		];
		$db = new MongoDb([
			'autoConnect' => true,
			'readPreference' => $prefs,
			'classes' => [
				'server' => 'lithium\tests\mocks\core\MockCallable'
			]
		]);

		$result = $db->server->call;
		$this->assertEqual('setReadPreference', $result['method']);
		$this->assertEqual($prefs, $result['params']);
	}

	public function testSetReadPreferenceBeforeAccessCollection() {
		$prefs = [
			"SECONDARY",
			['dc' => 'east', 'use' => 'reporting']
		];
		$db = new MongoDb([
			'database' => 'test',
			'autoConnect' => true,
			'readPreference' => $prefs,
			'classes' => [
				'server' => 'lithium\tests\mocks\core\MockCallable'
			]
		]);

		$trace = $db->server->trace;
		$this->assertEqual('__call', $trace[1][0]);
		$this->assertEqual('setReadPreference', $trace[1][1][0]);
		$this->assertEqual('__get', $trace[2][0]);
		$this->assertEqual('test', $trace[2][1][0]);
	}

	public function testDefaultSafeOptions() {
		$this->_db = new MongoDb($this->_testConfig + ['w' => 1, 'wTimeoutMS' => 10000]);
		$this->_db->server = new MockMongoConnection();
		$this->_db->connection = new MockCallable();
		$this->_db->connection->custom = new MockCallable();

		$query = new Query(['type' => 'read', 'source' => 'custom']);
		$this->_db->create($query);
		$result = $this->_db->connection->custom->call;
		$expected = [null, ['w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false]];
		$this->assertEqual('insert', $result['method']);
		$this->assertEqual($expected, $result['params']);

		$query = new Query(['type' => 'read', 'source' => 'custom', 'data' => ['something']]);
		$this->_db->update($query);
		$result = $this->_db->connection->custom->call;
		$expected = ['upsert' => false, 'multiple' => true, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false];
		$this->assertEqual('update', $result['method']);
		$this->assertEqual($expected, $result['params'][2]);

		$query = new Query(['type' => 'read', 'source' => 'custom']);
		$this->_db->delete($query);
		$result = $this->_db->connection->custom->call;
		$expected = ['justOne' => false, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false];
		$this->assertEqual('remove', $result['method']);
		$this->assertEqual($expected, $result['params'][1]);

		$this->_db = new MongoDb($this->_testConfig + ['w' => 1, 'wTimeoutMS' => 10000]);
		$this->_db->server = new MockMongoConnection();
		$this->_db->connection = new MockCallable();
		$this->_db->connection->custom = new MockCallable();

		$query = new Query(['type' => 'read', 'source' => 'custom']);
		$this->_db->create($query);
		$result = $this->_db->connection->custom->call;
		$expected = [null, ['w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false]];
		$this->assertEqual('insert', $result['method']);
		$this->assertEqual($expected, $result['params']);

		$query = new Query(['type' => 'read', 'source' => 'custom', 'data' => ['something']]);
		$this->_db->update($query);
		$result = $this->_db->connection->custom->call;
		$expected = [
			'upsert' => false, 'multiple' => true, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false
		];
		$this->assertEqual('update', $result['method']);
		$this->assertEqual($expected, $result['params'][2]);

		$query = new Query(['type' => 'read', 'source' => 'custom']);
		$this->_db->delete($query);
		$result = $this->_db->connection->custom->call;
		$expected = ['justOne' => false, 'w' => 1, 'wTimeoutMS' => 10000, 'fsync' => false];
		$this->assertEqual('remove', $result['method']);
		$this->assertEqual($expected, $result['params'][1]);
	}

	public function testGridFsCRUDWithDefaultPrefix() {
		$source = 'fs.files';
		$data = ['filename' => 'lithium', 'file' => 'some_datas'];

		MockMongoPost::config(['meta' => ['source' => $source]]);
		$this->assertTrue(MockMongoPost::create()->save($data));
		$this->assertIdentical('fs', $this->_db->connection->gridFsPrefix);
		$this->_db->connection->gridFsPrefix = null;

		MockMongoPost::config(['meta' => ['source' => $source]]);
		$this->_db->connection->results = [new MockResultResource(['data' => $data])];
		$this->assertNotEmpty(MockMongoPost::find('all'));
		$this->assertIdentical('fs', $this->_db->connection->gridFsPrefix);
		$this->_db->connection->gridFsPrefix = null;

		MockMongoPost::create($data + ['_id' => new MongoId], ['exists' => true])->delete();
		$this->assertIdentical('fs', $this->_db->connection->gridFsPrefix);
		$this->_db->connection->gridFsPrefix = null;

	}

	public function testGridFsCreateWithCustomPrefix() {
		$data = ['filename' => 'lithium', 'file' => 'some_datas'];

		$db = new MongoDb($this->_testConfig + ['gridPrefix' => 'custom']);
		$db->server = new MockMongoConnection();
		$db->connection = new MockMongoConnection();
		Connections::add('temp', ['object' => $db]);
		MockMongoPost::config(['meta' => ['source' => 'fs.files', 'connection' => 'temp']]);

		MockMongoPost::config(['meta' => ['source' => 'fs.files']]);
		$this->assertFalse(MockMongoPost::create()->save($data));
		$this->assertIdentical(null, $db->connection->gridFsPrefix);

		MockMongoPost::config(['meta' => ['source' => 'custom.files']]);
		$this->assertTrue(MockMongoPost::create()->save($data));
		$this->assertIdentical('custom', $db->connection->gridFsPrefix);
		Connections::remove('temp');
	}

	public function testGridFsReadWithCustomPrefix() {
		$data = ['filename' => 'lithium', 'file' => 'some_datas'];
		$result = new MockResultResource(['data' => [
			['filename' => 'lithium', 'file' => 'some_datas']
		]]);

		$db = new MongoDb($this->_testConfig + ['gridPrefix' => 'custom']);
		$db->server = new MockMongoConnection();
		$db->connection = new MockMongoConnection();
		Connections::add('temp', ['object' => $db]);
		MockMongoPost::config(['meta' => ['source' => 'fs.files', 'connection' => 'temp']]);
		$db->connection->results = [$result];
		$this->assertNotEmpty(MockMongoPost::find('all'));
		$this->assertIdentical(null, $db->connection->gridFsPrefix);

		MockMongoPost::config(['meta' => ['source' => 'custom.files']]);
		$db->connection->results = [$result];
		$this->assertNotEmpty(MockMongoPost::find('all'));
		$this->assertIdentical('custom', $db->connection->gridFsPrefix);
		Connections::remove('temp');
	}

	public function testGridFsDeleteWithCustomPrefix() {
		$data = ['_id' => new MongoId];

		$db = new MongoDb($this->_testConfig + ['gridPrefix' => 'custom']);
		$db->server = new MockMongoConnection();
		$db->connection = new MockMongoConnection();
		Connections::add('temp', ['object' => $db]);
		MockMongoPost::config(['meta' => ['source' => 'fs.files', 'connection' => 'temp']]);

		MockMongoPost::create($data, ['exists' => true])->delete();
		$this->assertIdentical(null, $db->connection->gridFsPrefix);

		MockMongoPost::config(['meta' => ['source' => 'custom.files']]);
		MockMongoPost::create($data, ['exists' => true])->delete();
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