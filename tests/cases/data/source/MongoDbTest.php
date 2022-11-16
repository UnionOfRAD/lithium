<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\source;

use lithium\data\source\mongo_db\Schema;
use lithium\data\source\MongoDb;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Javascript;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Regex;
use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\data\entity\Document;
use lithium\tests\mocks\data\MockPost;
use lithium\tests\mocks\data\MockComment;
use lithium\tests\mocks\data\source\MockMongoManager;
use lithium\tests\mocks\data\source\MockMongoPost;

class MockMongoDb extends MongoDb {
	public function config() {
		return $this->_config;
	}
}

class MongoDbTest extends \lithium\test\Unit {

	protected $_db = null;

	protected $_model = 'lithium\tests\mocks\data\source\MockMongoPost';

	protected $_query = null;

	protected $_testConfig = [
		'adapter' => false,
		'database' => 'test',
		'host' => 'localhost',
		'port' => '27017',
	];

	protected $_schema = [
		'_id' => 'id',
		'guid' => 'id',
		'title' => 'string',
		'tags' => ['type' => 'string', 'array' => true],
		'comments' => 'MongoDB\BSON\ObjectId',
		'authors' => ['type' => 'MongoDB\BSON\ObjectId', 'array' => true],
		'created' => 'MongoDB\BSON\UTCDateTime',
		'modified' => 'datetime',
		'voters' => ['type' => 'id', 'array' => true],
		'rank_count' => ['type' => 'integer', 'default' => 0],
		'rank' => ['type' => 'float', 'default' => 0.0],
		'notifications.foo' => 'boolean',
		'notifications.bar' => 'boolean',
		'notifications.baz' => 'boolean'
	];

	protected $_configs = [];

	public function skip() {
		$this->skipIf(!MongoDb::enabled(), 'The `MongoDb` class is not enabled.');
	}

	public function setUp() {
		$this->_db = new MockMongoDb($this->_testConfig);
		$this->_db->manager = new MockMongoManager();
		$model = $this->_model;

		Connections::add('mockconn', ['object' => $this->_db]);
		MockMongoPost::config(['meta' => ['key' => '_id', 'connection' => 'mockconn']]);

		$type = 'create';
		$this->_query = new Query(compact('model', 'type') + ['entity' => new Document(compact('model'))]);
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockPost::reset();
		MockComment::reset();
		MockMongoPost::reset();
	}

	public function testConnectNoHost() {
		$config = $this->_testConfig;
		$config['host'] = '';
		$this->assertException('lithium\core\ConfigException', function() use ($config) {
			new MongoDb($config);
		});

		$config = $this->_testConfig;
		$config['host'] = null;
		$this->assertException('lithium\core\ConfigException', function() use ($config) {
			new MongoDb($config);
		});
	}

	public function testConnectInvalidDsn() {
		$config = $this->_testConfig;
		$config['dsn'] = 'foobar://user:pass@example.org';

		$this->assertException('lithium\core\ConfigException', function() use ($config) {
				$db = new MongoDb($config + ['autoConnect' => false]);
		});

		$config = $this->_testConfig;
		$config['dsn'] = 'foobar://user:pass@example.org/testdb';
		$this->assertException('MongoDB\Driver\Exception\InvalidArgumentException', function() use ($config) {
			$db = new MongoDb($config);
		});
	}

	public function testConnectDsn() {
		$config = $this->_testConfig;
		$config['dsn'] = 'mongodb://user:pass@cluster0-shard-00-00-foo.mongodb.net:27017,cluster0-shard-00-01-foo.mongodb.net:27017,cluster0-shard-00-02-foo.mongodb.net:27017/testdb';

		$db = new MockMongoDb($config + ['autoConnect' => false]);
		$this->assertEqual($db->config()['login'], 'user');
		$this->assertEqual($db->config()['password'], 'pass');
		$this->assertEqual($db->config()['database'], 'testdb');
		$this->assertEqual($db->config()['host'], [
			'cluster0-shard-00-00-foo.mongodb.net:27017',
			'cluster0-shard-00-01-foo.mongodb.net:27017',
			'cluster0-shard-00-02-foo.mongodb.net:27017',
		]);

		$config = $this->_testConfig;
		$config['dsn'] = 'mongodb://cluster0-shard-00-00-foo.mongodb.net:27017,cluster0-shard-00-01-foo.mongodb.net:27017,cluster0-shard-00-02-foo.mongodb.net:27017/testdb';

		$db = new MockMongoDb($config + ['autoConnect' => false]);
		$this->assertEqual($db->config()['database'], 'testdb');
		$this->assertEqual($db->config()['host'], [
			'cluster0-shard-00-00-foo.mongodb.net:27017',
			'cluster0-shard-00-01-foo.mongodb.net:27017',
			'cluster0-shard-00-02-foo.mongodb.net:27017',
		]);

		$config = $this->_testConfig;
		$config['dsn'] = 'mongodb+srv://user:pass@cluster0-foo.mongodb.net/testdb';

		$db = new MockMongoDb($config + ['autoConnect' => false]);
		$this->assertEqual($db->config()['login'], 'user');
		$this->assertEqual($db->config()['password'], 'pass');
		$this->assertEqual($db->config()['database'], 'testdb');
		$this->assertEqual($db->config()['host'], 'cluster0-foo.mongodb.net:27017');
	}

	public function testConnectHosts() {
		$config = $this->_testConfig;
		$config['host'] = ['host1', 'host2:27017', 'host3:27018', ':27019'];

		$db = new MockMongoDb($config);
		$this->assertEqual($db->config()['dsn'], 'mongodb://host1:27017,host2:27017,host3:27018,localhost:27019');
	}

	public function testConnectOptions() {
		$config = $this->_testConfig;
		$config['dsn'] = 'mongodb://user:pass@cluster0-shard-00-00-foo.mongodb.net:27017,cluster0-shard-00-01-foo.mongodb.net:27017,cluster0-shard-00-02-foo.mongodb.net:27017/testdb?ssl=true&replicaSet=Cluster0-shard-0&authSource=admin';

		$db = new MockMongoDb($config + ['autoConnect' => false]);
		$this->assertEqual($db->config()['login'], 'user');
		$this->assertEqual($db->config()['password'], 'pass');
		$this->assertEqual($db->config()['database'], 'testdb');
		$this->assertEqual($db->config()['host'], [
			'cluster0-shard-00-00-foo.mongodb.net:27017',
			'cluster0-shard-00-01-foo.mongodb.net:27017',
			'cluster0-shard-00-02-foo.mongodb.net:27017',
		]);
		$this->assertEqual($db->config()['uriOptions'], [
			'ssl' => true,
			'replicaSet' => 'Cluster0-shard-0',
			'authSource' => 'admin',
			'w' => 'majority',
			'wTimeoutMS' => 10000,
			'journal' => true,
			'readConcernLevel' => 'local',
			'readPreference' => 'primary',
			'readPreferenceTags' => [],
			'connectTimeoutMS' => 1000,
		]);

		$config = $this->_testConfig;
		$config['dsn'] = 'mongodb://user:pass@cluster0-shard-00-00-foo.mongodb.net:27017,cluster0-shard-00-01-foo.mongodb.net:27017,cluster0-shard-00-02-foo.mongodb.net:27017/testdb?ssl=true&replicaSet=Cluster0-shard-0&authSource=admin&connectTimeoutMS=500';

		$db = new MockMongoDb($config + ['autoConnect' => false]);
		$this->assertEqual($db->config()['login'], 'user');
		$this->assertEqual($db->config()['password'], 'pass');
		$this->assertEqual($db->config()['database'], 'testdb');
		$this->assertEqual($db->config()['host'], [
			'cluster0-shard-00-00-foo.mongodb.net:27017',
			'cluster0-shard-00-01-foo.mongodb.net:27017',
			'cluster0-shard-00-02-foo.mongodb.net:27017',
		]);
		$this->assertEqual($db->config()['uriOptions'], [
			'w' => 'majority',
			'wTimeoutMS' => 10000,
			'journal' => true,
			'readConcernLevel' => 'local',
			'readPreference' => 'primary',
			'readPreferenceTags' => [],
			'connectTimeoutMS' => 500,
			'ssl' => true,
			'replicaSet' => 'Cluster0-shard-0',
			'authSource' => 'admin',
		]);

		$config = $this->_testConfig;
		$config['dsn'] = 'mongodb://user:pass@cluster0-shard-00-00-foo.mongodb.net:27017,cluster0-shard-00-01-foo.mongodb.net:27017,cluster0-shard-00-02-foo.mongodb.net:27017/testdb?ssl=true&replicaSet=Cluster0-shard-0&authSource=admin';
		$config['uriOptions']['journal'] = false;

		$db = new MockMongoDb($config + ['autoConnect' => false]);
		$this->assertEqual($db->config()['login'], 'user');
		$this->assertEqual($db->config()['password'], 'pass');
		$this->assertEqual($db->config()['database'], 'testdb');
		$this->assertEqual($db->config()['host'], [
			'cluster0-shard-00-00-foo.mongodb.net:27017',
			'cluster0-shard-00-01-foo.mongodb.net:27017',
			'cluster0-shard-00-02-foo.mongodb.net:27017',
		]);
		$this->assertEqual($db->config()['uriOptions'], [
			'w' => 'majority',
			'wTimeoutMS' => 10000,
			'journal' => false,
			'readConcernLevel' => 'local',
			'readPreference' => 'primary',
			'readPreferenceTags' => [],
			'connectTimeoutMS' => 1000,
			'ssl' => true,
			'replicaSet' => 'Cluster0-shard-0',
			'authSource' => 'admin',
		]);
	}

	public function testSources() {
		$this->_db->manager->results = [[(object) ['name' => 'images']]];
		$this->assertEqual(['images'], $this->_db->sources());
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

	public function testDefaultReadPreferenceOnRead() {
		$model = $this->_model;
		$result = $this->_db->read(new Query(compact('model')));

		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $result);

		$query = array_pop($this->_db->manager->queries);

		$this->assertEmpty($this->_db->manager->queries);
		$this->assertIdentical('executeQuery', $query['type']);
		$this->assertIdentical('test.posts', $query['namespace']);

		$readPreference = $query['readPreference'];
		$this->assertIdentical(1, $readPreference->getMode());
		$this->assertIdentical([], $readPreference->getTagSets());
	}

	public function testOverridedReadPreferenceOnRead() {
		$model = $this->_model;
		$result = $this->_db->read(new Query(compact('model')), [
			'readPreference'     => 2,
			'readPreferenceTags' => [['dc' => 'east']],
		]);

		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $result);

		$query = array_pop($this->_db->manager->queries);

		$this->assertEmpty($this->_db->manager->queries);
		$this->assertIdentical('executeQuery', $query['type']);
		$this->assertIdentical('test.posts', $query['namespace']);

		$readPreference = $query['readPreference'];
		$this->assertIdentical(2, $readPreference->getMode());
		$this->assertIdentical([['dc' => 'east']], $readPreference->getTagSets());
	}

	public function testDefaultWriteConcernOnInserts() {
		$this->_query->data(['title' => 'Test Post']);
		$this->assertTrue($this->_db->create($this->_query));

		$query = array_pop($this->_db->manager->queries);

		$this->assertEmpty($this->_db->manager->queries);
		$this->assertIdentical('executeBulkWrite', $query['type']);
		$this->assertIdentical('test.posts', $query['namespace']);

		$writeConcern = $query['writeConcern'];
		$this->assertIdentical('majority', $writeConcern->getW());
		$this->assertIdentical(10000, $writeConcern->getWtimeout());
		$this->assertIdentical(true, $writeConcern->getJournal());
	}

	public function testOverridedWriteConcernOnInserts() {
		$this->_query->data(['title' => 'Test Post']);
		$this->assertTrue($this->_db->create($this->_query, [
			'w'          => 1,
			'wTimeoutMS' => 5000,
			'journal'    => false
		]));

		$query = array_pop($this->_db->manager->queries);

		$this->assertEmpty($this->_db->manager->queries);
		$this->assertIdentical('executeBulkWrite', $query['type']);
		$this->assertIdentical('test.posts', $query['namespace']);

		$writeConcern = $query['writeConcern'];
		$this->assertIdentical(1, $writeConcern->getW());
		$this->assertIdentical(5000, $writeConcern->getWtimeout());
		$this->assertIdentical(false, $writeConcern->getJournal());
	}

	public function testDefaultWriteConcernOnUpdates() {
		$model = $this->_model;
		$this->_query = new Query(compact('model') + [
			'data' => ['title' => 'New Test Post'],
			'conditions' => ['_id' => '123']
		]);

		$this->assertTrue($this->_db->update($this->_query));

		$query = array_pop($this->_db->manager->queries);

		$this->assertEmpty($this->_db->manager->queries);
		$this->assertIdentical('executeBulkWrite', $query['type']);
		$this->assertIdentical('test.posts', $query['namespace']);

		$writeConcern = $query['writeConcern'];
		$this->assertIdentical('majority', $writeConcern->getW());
		$this->assertIdentical(10000, $writeConcern->getWtimeout());
		$this->assertIdentical(true, $writeConcern->getJournal());
	}

	public function testOverridedWriteConcernOnUpdates() {
		$model = $this->_model;
		$this->_query = new Query(compact('model') + [
			'data' => ['title' => 'New Test Post'],
			'conditions' => ['_id' => '123']
		]);

		$this->assertTrue($this->_db->update($this->_query, [
			'w'          => 1,
			'wTimeoutMS' => 5000,
			'journal'    => false
		]));

		$query = array_pop($this->_db->manager->queries);

		$this->assertEmpty($this->_db->manager->queries);
		$this->assertIdentical('executeBulkWrite', $query['type']);
		$this->assertIdentical('test.posts', $query['namespace']);

		$writeConcern = $query['writeConcern'];
		$this->assertIdentical(1, $writeConcern->getW());
		$this->assertIdentical(5000, $writeConcern->getWtimeout());
		$this->assertIdentical(false, $writeConcern->getJournal());
	}

	public function testConditions() {
		$result = $this->_db->conditions(null, null);
		$this->assertEqual([], $result);

		$function = 'function() { return this.x < y;}';
		$conditions = new Javascript($function);
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
		$expected = ['key' => new Regex('/regex/i')];
		$this->assertEqual($expected, $result);
	}

	public function testConditionsWithSchema() {
		$schema = new Schema(['fields' => [
			'_id' => ['type' => 'id'],
			'tags' => ['type' => 'string', 'array' => true],
			'users' => ['type' => 'id', 'array' => true]
		]]);

		$query = new Query(['schema' => $schema, 'type' => 'read']);

		$id = new ObjectId();
		$userId = new ObjectId();

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

	public function testCreate() {
		$data = ['title' => 'New Item'];
		$result = MockMongoPost::create($data, ['defaults' => false]);
		$this->assertInstanceOf('lithium\data\entity\Document', $result);

		$expected = $data;
		$result = $result->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testEnabled() {
		$this->assertTrue(MongoDb::enabled());
		$this->assertTrue(MongoDb::enabled('arrays'));
		$this->assertTrue(MongoDb::enabled('booleans'));
		$this->assertTrue(MongoDb::enabled('relationships'));
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
		];
		$this->assertEqual($expected, $result->data());
	}

	public function testCastingConditionsValues() {
		$query = new Query(['schema' => new Schema(['fields' => $this->_schema])]);

		$conditions = ['_id' => new ObjectId("4c8f86167675abfabdbe0300")];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertEqual($conditions, $result);

		$conditions = ['_id' => "4c8f86167675abfabdbe0300"];
		$result = $this->_db->conditions($conditions, $query);

		$this->assertEqual(array_keys($conditions), array_keys($result));
		$this->assertInstanceOf('MongoDB\BSON\ObjectId', $result['_id']);
		$this->assertEqual($conditions['_id'], (string) $result['_id']);

		$conditions = ['_id' => [
			"4c8f86167675abfabdbe0300", "4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
		]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertCount(3, $result['_id']['$in']);

		foreach ([0, 1, 2] as $i) {
			$this->assertInstanceOf('MongoDB\BSON\ObjectId', $result['_id']['$in'][$i]);
		}

		$conditions = ['voters' => ['$all' => [
			"4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
		]]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertCount(2, $result['voters']['$all']);
		$result = $result['voters']['$all'];

		foreach ([0, 1] as $i) {
			$this->assertInstanceOf('MongoDB\BSON\ObjectId', $result[$i]);
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
			$this->assertInstanceOf('MongoDB\BSON\ObjectId', $result['$or'][$i][$key]);
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

		$user_id = new ObjectId();
		$conditions = ['members' => [
			'$elemMatch' => [
				'user_id' => (string) $user_id,
				'pattern' => '/test/i',
			]
		]];
		$result = $this->_db->conditions($conditions, $query);
		$this->assertInstanceOf('MongoDB\BSON\ObjectId', $result['members']['$elemMatch']['user_id']);
		$this->assertInstanceOf('MongoDB\BSON\Regex', $result['members']['$elemMatch']['pattern']);
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
			'_id' => new ObjectId(),
			'created' => new UTCDateTime(strtotime('-1 hour')),
			'list' => ['foo', 'bar', 'baz']
		];
		$entity = new Document(compact('data') + ['exists' => false]);
		$query = new Query(['type' => 'create'] + compact('entity'));
		$result = $query->export($this->_db);
		$this->assertIdentical($data, $result['data']['data']);
	}

	public function testUpdateWithEmbeddedObjects() {
		$data = [
			'_id' => new ObjectId(),
			'created' => new UTCDateTime(strtotime('-1 hour')),
			'list' => ['foo', 'bar', 'baz']
		];

		$fields = ['updated' => ['type' => 'MongoDB\BSON\UTCDateTime']];
		$schema = new Schema(compact('fields'));
		$entity = new Document(compact('data', 'schema') + ['exists' => true]);
		$entity->updated = time();
		$entity->list[] = 'dib';

		$query = new Query(['type' => 'update'] + compact('entity'));
		$result = $query->export($this->_db);
		$expected = ['_id', 'created', 'list', 'updated'];
		$this->assertEqual($expected, array_keys($result['data']['update']));
		$this->assertInstanceOf('MongoDB\BSON\UTCDateTime', $result['data']['update']['updated']);
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
		$db = new MockMongoDb(['schema' => function() use ($schema) {
			return $schema;
		}]);
		$this->assertEqual($schema, $db->describe(null)->fields());
	}
}

?>