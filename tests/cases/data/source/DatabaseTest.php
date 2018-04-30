<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\source;

use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\data\entity\Record;
use lithium\tests\mocks\data\model\MockDatabase;
use lithium\tests\mocks\data\model\MockDatabasePost;
use lithium\tests\mocks\data\model\MockDatabaseComment;
use lithium\tests\mocks\data\model\MockDatabaseTagging;
use lithium\tests\mocks\data\model\MockDatabasePostRevision;
use lithium\tests\mocks\data\model\database\MockResult;
use lithium\tests\mocks\data\model\MockGallery;
use lithium\tests\mocks\data\model\MockImage;
use lithium\tests\mocks\data\model\MockImageTag;
use lithium\tests\mocks\data\model\MockTag;

class DatabaseTest extends \lithium\test\Unit {

	protected $_db = null;

	protected $_configs = [];

	protected $_model = 'lithium\tests\mocks\data\model\MockDatabasePost';

	protected $_comment = 'lithium\tests\mocks\data\model\MockDatabaseComment';

	protected $_gallery = 'lithium\tests\mocks\data\model\MockGallery';

	protected $_imageTag = 'lithium\tests\mocks\data\model\MockImageTag';

	public function setUp() {
		$this->_db = new MockDatabase();
		Connections::add('mockconn', ['object' => $this->_db]);

		$config = ['meta' => ['connection' => 'mockconn']];
		MockDatabasePost::config($config);
		MockDatabaseComment::config($config);
		MockDatabaseTagging::config($config);
		MockDatabasePostRevision::config($config);
		MockGallery::config($config);
		MockImage::config($config);
		MockImageTag::config($config);
		MockTag::config($config);
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockDatabasePost::reset();
		MockDatabaseComment::reset();
		MockDatabaseTagging::reset();
		MockDatabasePostRevision::reset();
		MockGallery::reset();
		MockImage::reset();
		MockImageTag::reset();
		MockTag::reset();
	}

	public function testDefaultConfig() {
		$expected = [
			'persistent'  => true,
			'host'        => 'localhost',
			'login'       => 'root',
			'password'    => '',
			'database'    => 'mock',
			'encoding'    => null,
			'dsn'         => null,
			'options'     => [],
			'autoConnect' => true,
		];
		$result = $this->_db->testConfig();
		$this->assertEqual($expected, $result);
	}

	public function testModifyConfig() {
		$db = new MockDatabase(['host' => '127.0.0.1', 'login' => 'bob']);
		$expected = [
			'persistent'    => true,
			'host'          => '127.0.0.1',
			'login'         => 'bob',
			'password'      => '',
			'database'      => 'mock',
			'encoding'      => null,
			'dsn'           => null,
			'options'       => [],
			'autoConnect'   => true,
		];
		$result = $db->testConfig();
		$this->assertEqual($expected, $result);
	}

	public function testName() {
		$result = $this->_db->name("name");
		$this->assertEqual("{name}", $result);

		$result = $this->_db->name("Model.name");
		$this->assertEqual("{Model}.{name}", $result);

		$result = $this->_db->name("Model.name name");
		$this->assertEqual("{Model}.{name name}", $result);
	}

	public function testNullValueWithSchemaFormatter() {
		$result = $this->_db->value(null);
		$this->assertIdentical('NULL', $result);
	}

	public function testStringValueWithSchemaFormatter() {
		$result = $this->_db->value('string', ['type' => 'string']);
		$this->assertEqual("'string'", $result);

		$result = $this->_db->value('1', ['type' => 'string']);
		$this->assertIdentical("'1'", $result);
	}

	public function testBooleanValueWithSchemaFormatter() {
		$result = $this->_db->value('true', ['type' => 'boolean']);
		$this->assertIdentical(1, $result);
	}

	public function testNumericValueWithSchemaFormatter() {
		$result = $this->_db->value('1', ['type' => 'integer']);
		$this->assertIdentical(1, $result);

		$result = $this->_db->value('1.1', ['type' => 'float']);
		$this->assertIdentical(1.1, $result);
	}

	public function testObjectValueWithSchemaFormatter() {
		$result = $this->_db->value((object) 'REGEXP "^fo$"');
		$this->assertIdentical('REGEXP "^fo$"', $result);

		$result = $this->_db->value((object) 'CURRENT_TIMESTAMP', ['type' => 'timestamp']);
		$this->assertIdentical('CURRENT_TIMESTAMP', $result);
	}

	public function testDateTimeValueWithSchemaFormatter() {
		$result = $this->_db->value('2012-05-25 22:44:00', ['type' => 'timestamp']);
		$this->assertIdentical("'2012-05-25 22:44:00'", $result);

		$result = $this->_db->value('2012-05-25', ['type' => 'date']);
		$this->assertIdentical("'2012-05-25'", $result);

		$result = $this->_db->value('now', ['type' => 'timestamp']);
		$this->assertPattern("/^'\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}'/", $result);

		$result = $this->_db->value('now', ['type' => 'date']);
		$this->assertPattern("/^'\d{4}-\d{2}-\d{2}'/", $result);

		$result = $this->_db->value('now', ['type' => 'time']);
		$this->assertPattern("/^'\d{2}:\d{2}:\d{2}'/", $result);

		$result = $this->_db->value('', ['type' => 'date']);
		$this->assertIdentical('NULL', $result);

		$result = $this->_db->value('', ['type' => 'time']);
		$this->assertIdentical('NULL', $result);

		$result = $this->_db->value('', ['type' => 'timestamp']);
		$this->assertIdentical('NULL', $result);

		$result = $this->_db->value('', ['type' => 'datetime']);
		$this->assertIdentical('NULL', $result);

		$result = $this->_db->value('', [
			'type' => 'date', 'default' => '2012-05-25'
		]);
		$this->assertIdentical("'2012-05-25'", $result);

		$result = $this->_db->value('', [
			'type' => 'time', 'default' => '08:00:00'
		]);
		$this->assertIdentical("'08:00:00'", $result);

		$result = $this->_db->value('', [
			'type' => 'timestamp', 'default' => 'now'
		]);
		$this->assertPattern("/^'\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}'/", $result);
	}

	public function testStringValueByIntrospection() {
		$result = $this->_db->value("string");
		$this->assertIdentical("'string'", $result);
	}

	public function testBooleanValueByIntrospection() {
		$result = $this->_db->value(true);
		$this->assertIdentical(1, $result);
	}

	public function testNumericValueByIntrospection() {
		$result = $this->_db->value('1');
		$this->assertIdentical(1, $result);

		$result = $this->_db->value('1.1');
		$this->assertIdentical(1.1, $result);
	}

	public function testSchema() {
		$model = $this->_model;
		$model::config();
		$modelName = '';
		$expected = [$modelName => ['id', 'author_id', 'title', 'created']];
		$result = $this->_db->schema(new Query(compact('model')));
		$this->assertEqual($expected, $result);

		$query = new Query(compact('model') + ['fields' => '*']);
		$result = $this->_db->schema($query);
		$this->assertEqual($expected, $result);

		$query = new Query([
			'type' => 'read',
			'model' => $this->_model,
			'fields' => ['MockDatabaseComment'],
			'with' => ['MockDatabaseComment']
		]);
		$expected = [
			'' => ['id'],
			'MockDatabaseComment' => [
				'id', 'post_id', 'author_id', 'body', 'created'
			]
		];
		$result = $this->_db->schema($query);
		$this->assertEqual($expected, $result);

		$options = [
			'type' => 'read',
			'model' => $this->_model,
			'with' => 'MockDatabaseComment'
		];
		$options['fields'] = ['id', 'title'];
		$result = $this->_db->schema(new Query($options));

		$expected = [$modelName => $options['fields']];
		$this->assertEqual($expected, $result);

		$options['fields'] = [
			'MockDatabasePost.id',
			'MockDatabasePost.title',
			'MockDatabaseComment.body'
		];
		$result = $this->_db->schema(new Query($options));
		$expected = [
			$modelName => ['id', 'title'],
			'MockDatabaseComment' => ['body']
		];
		$this->assertEqual($expected, $result);

		$options['fields'] = [
			'MockDatabasePost' => ['id', 'title'],
			'MockDatabaseComment' => ['body', 'created']
		];
		$result = $this->_db->schema(new Query($options));
		$expected = [
			$modelName => ['id', 'title'],
			'MockDatabaseComment' => ['body', 'created']
		];
		$this->assertEqual($expected, $result);

		$options['fields'] = ['MockDatabasePost', 'MockDatabaseComment'];
		$result = $this->_db->schema(new Query($options));
		$expected = [
			$modelName => ['id', 'author_id', 'title', 'created'],
			'MockDatabaseComment' => ['id', 'post_id', 'author_id', 'body', 'created']
		];
		$this->assertEqual($expected, $result);
	}

	public function testSchemaFromManualFieldList() {
		$fields = ['id', 'name', 'created'];
		$result = $this->_db->schema(new Query(compact('fields')));
		$this->assertEqual(['' => $fields], $result);
	}

	public function testSimpleQueryRender() {
		$fieldList = '{MockDatabasePost}.{id}, {MockDatabasePost}.{title},';
		$fieldList .= ' {MockDatabasePost}.{created}';
		$table = '{mock_database_posts} AS {MockDatabasePost}';

		$result = $this->_db->renderCommand(new Query([
			'type' => 'read',
			'model' => $this->_model,
			'fields' => ['id', 'title', 'created']
		]));
		$this->assertEqual("SELECT {$fieldList} FROM {$table};", $result);

		$result = $this->_db->renderCommand(new Query([
			'type' => 'read',
			'model' => $this->_model,
			'fields' => ['id', 'title', 'created'],
			'limit' => 1
		]));
		$this->assertEqual("SELECT {$fieldList} FROM {$table} LIMIT 1;", $result);

		$result = $this->_db->renderCommand(new Query([
			'type' => 'read',
			'model' => $this->_model,
			'fields' => ['id', 'title', 'created'],
			'limit' => 1,
			'conditions' => 'Post.id = 2'
		]));
		$this->assertEqual("SELECT {$fieldList} FROM {$table} WHERE Post.id = 2 LIMIT 1;", $result);
	}

	public function testNestedQueryConditions() {
		$query = new Query([
			'type' => 'read',
			'model' => $this->_model,
			'fields' => ['MockDatabasePost.title', 'MockDatabasePost.body'],
			'conditions' => ['Post.id' => new Query([
				'type' => 'read',
				'fields' => ['post_id'],
				'model' => 'lithium\tests\mocks\data\model\MockDatabaseTagging',
				'conditions' => ['MockDatabaseTag.tag' => ['foo', 'bar', 'baz']]
			])]
		]);
		$result = $this->_db->renderCommand($query);

		$expected = "SELECT {MockDatabasePost}.{title}, {MockDatabasePost}.{body} FROM";
		$expected .= " {mock_database_posts} AS {MockDatabasePost} WHERE {Post}.{id} IN";
		$expected .= " (SELECT {MockDatabaseTagging}.{post_id} FROM {mock_database_taggings} AS ";
		$expected .= "{MockDatabaseTagging} WHERE {MockDatabaseTag}.{tag} IN";
		$expected .= " ('foo', 'bar', 'baz'));";
		$this->assertEqual($expected, $result);

		$query = new Query([
			'type' => 'read',
			'model' => $this->_model,
			'fields' => ['MockDatabasePost.title', 'MockDatabasePost.body'],
			'conditions' => ['Post.id' => ['!=' => new Query([
				'type' => 'read',
				'fields' => ['post_id'],
				'model' => 'lithium\tests\mocks\data\model\MockDatabaseTagging',
				'conditions' => ['MockDatabaseTag.tag' => ['foo', 'bar', 'baz']]
			])]]
		]);
		$result = $this->_db->renderCommand($query);

		$expected = "SELECT {MockDatabasePost}.{title}, {MockDatabasePost}.{body} FROM";
		$expected .= " {mock_database_posts} AS {MockDatabasePost} WHERE ({Post}.{id} NOT IN";
		$expected .= " (SELECT {MockDatabaseTagging}.{post_id} FROM {mock_database_taggings} AS ";
		$expected .= "{MockDatabaseTagging} WHERE {MockDatabaseTag}.{tag} IN ";
		$expected .= "('foo', 'bar', 'baz')));";
		$this->assertEqual($expected, $result);

		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'conditions' => [
				'or' => [
					'{MockDatabasePost}.{id}' => 'value1',
					'{MockDatabasePost}.{title}' => 'value2'
				]
			]
		]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE ";
		$sql .= "({MockDatabasePost}.{id} = 'value1' OR {MockDatabasePost}.{title} = 'value2');";
		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	public function testCastingQueryConditionsWithSchemaWithAlias() {
		$query = new Query([
			'type' => 'read',
			'model' => $this->_model,
			'conditions' => [
				'MockDatabasePost.title' => '007'
			]
		]);
		$result = $this->_db->renderCommand($query);

		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE ";
		$sql .= "{MockDatabasePost}.{title} = '007';";
		$this->assertEqual($sql, $result);
	}

	public function testQueryJoin() {
		$query = new Query([
			'type' => 'read',
			'model' => $this->_model,
			'fields' => ['MockDatabasePost.title', 'MockDatabasePost.body'],
			'conditions' => ['MockDatabaseTag.tag' => ['foo', 'bar', 'baz']],
			'joins' => [new Query([
				'model' => 'lithium\tests\mocks\data\model\MockDatabaseTag',
				'constraints' => '{MockDatabaseTagging}.{tag_id} = {MockDatabaseTag}.{id}'
			])]
		]);
		$result = $this->_db->renderCommand($query);

		$expected = "SELECT {MockDatabasePost}.{title}, {MockDatabasePost}.{body} FROM";
		$expected .= " {mock_database_posts} AS {MockDatabasePost} JOIN {mock_database_tags} AS";
		$expected .= " {MockDatabaseTag} ON ";
		$expected .= "{MockDatabaseTagging}.{tag_id} = {MockDatabaseTag}.{id}";
		$expected .= " WHERE {MockDatabaseTag}.{tag} IN ('foo', 'bar', 'baz');";
		$this->assertEqual($expected, $result);
	}

	public function testItem() {
		$model = $this->_model;
		$data = ['title' => 'new post', 'content' => 'This is a new post.'];
		$result = $model::create($data, ['defaults' => false]);
		$this->assertEqual($data, $result->data());
	}

	public function testCreate() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['title' => 'new post', 'body' => 'the body']
		]);
		$query = new Query(compact('entity') + [
			'type' => 'create',
			'model' => $this->_model
		]);
		$hash = $query->export($this->_db);
		ksort($hash);
		$expected = sha1(serialize($hash));

		$result = $this->_db->create($query);
		$this->assertTrue($result);
		$result = $query->entity()->id;
		$this->assertEqual($expected, $result);

		$expected = "INSERT INTO {mock_database_posts} ({title}, {body})";
		$expected .= " VALUES ('new post', 'the body');";
		$result = $this->_db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testCreateGenericSyntax() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['data' => ['title' => 'new post', 'body' => 'the body']]
		]);
		$query = new Query(compact('entity') + [
			'type' => 'create',
			'model' => $this->_model
		]);
		$hash = $query->export($this->_db);
		ksort($hash);
		$expected = sha1(serialize($hash));

		$result = $this->_db->create($query);
		$this->assertTrue($result);
		$result = $query->entity()->id;
		$this->assertEqual($expected, $result);

		$expected = "INSERT INTO {mock_database_posts} ({title}, {body})";
		$expected .= " VALUES ('new post', 'the body');";
		$result = $this->_db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testCreateWithValueBySchema() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['title' => '007', 'body' => 'the body']
		]);
		$query = new Query(compact('entity') + [
			'type' => 'create',
			'model' => $this->_model
		]);
		$hash = $query->export($this->_db);
		ksort($hash);
		$expected = sha1(serialize($hash));

		$result = $this->_db->create($query);
		$this->assertTrue($result);
		$result = $query->entity()->id;
		$this->assertEqual($expected, $result);

		$expected = "INSERT INTO {mock_database_posts} ({title}, {body})";
		$expected .= " VALUES ('007', 'the body');";
		$result = $this->_db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testCreateWithKey() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'new post', 'body' => 'the body']
		]);
		$query = new Query(compact('entity') + ['type' => 'create']);
		$expected = 1;

		$result = $this->_db->create($query);
		$this->assertTrue($result);
		$result = $query->entity()->id;
		$this->assertEqual($expected, $result);

		$expected = "INSERT INTO {mock_database_posts} ({id}, {title}, {body})";
		$expected .= " VALUES (1, 'new post', 'the body');";
		$this->assertEqual($expected, $this->_db->sql);
	}

	public function testReadWithQueryStringReturnResource() {
		$result = $this->_db->read('SELECT * from mock_database_posts AS MockDatabasePost;', [
			'return' => 'resource'
		]);
		$this->assertNotEmpty($result);

		$expected = "SELECT * from mock_database_posts AS MockDatabasePost;";
		$this->assertEqual($expected, $this->_db->sql);
	}

	/**
	 * @link https://github.com/UnionOfRAD/lithium/issues/1281
	 */
	public function testCalculation() {
		$options = [
			'type' => 'read',
			'model' => $this->_model
		];

		$this->_db->return['_execute'] = new MockResult([
			'records' => [
				[23]
			]
		]);
		$expected = 23;
		$result = $this->_db->calculation('count', new Query($options), $options);
		$this->assertEqual($expected, $result);

		$expected = 'SELECT COUNT(*) as count FROM {mock_database_posts} AS {MockDatabasePost};';
		$result = $this->_db->sql;
		$this->assertEqual($expected, $result);

		$this->_db->return['_execute'] = new MockResult([
			'records' => []
		]);
		$result = $this->_db->calculation('count', new Query($options), $options);
		$this->assertNull($result);
	}

	public function testReadWithQueryStringReturnArrayWithSchema() {
		$result = $this->_db->read('SELECT * FROM {:table} WHERE user_id = {:uid};', [
			'table' => 'mock_database_posts',
			'uid' => '3',
			'schema' => ['id', 'title', 'text']
		]);
		$expected = 'SELECT * FROM \'mock_database_posts\' WHERE user_id = 3;';
		$this->assertEqual($expected, $this->_db->sql);
	}

	public function testReadWithQueryObjectRecordSet() {
		$query = new Query(['type' => 'read', 'model' => $this->_model]);
		$result = $this->_db->read($query);
		$this->assertInstanceOf('lithium\data\collection\RecordSet', $result);

		$expected = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost};";
		$result = $this->_db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithQueryObjectArray() {
		$query = new Query(['type' => 'read', 'model' => $this->_model]);
		$result = $this->_db->read($query, ['return' => 'array']);
		$this->assertInternalType('array', $result);

		$expected = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost};";
		$result = $this->_db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testUpdate() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'the post', 'body' => 'the body'],
			'exists' => true
		]);
		$entity->title = 'new post';
		$entity->body = 'new body';
		$query = new Query(compact('entity') + ['type' => 'update']);
		$result = $this->_db->update($query);

		$this->assertTrue($result);
		$this->assertEqual(1, $query->entity()->id);

		$expected = "UPDATE {mock_database_posts} SET";
		$expected .= " {title} = 'new post', {body} = 'new body' WHERE {id} = 1;";
		$this->assertEqual($expected, $this->_db->sql);

		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 2, 'count' => 10],
			'exists' => true
		]);
		$entity->count = (object) '{count} + 1';
		$query = new Query(compact('entity') + ['type' => 'update']);
		$result = $this->_db->update($query);

		$this->assertTrue($result);
		$this->assertEqual(2, $query->entity()->id);

		$expected = "UPDATE {mock_database_posts} SET";
		$expected .= " {count} = {count} + 1 WHERE {id} = 2;";
		$this->assertEqual($expected, $this->_db->sql);

		$query = new Query([
			'type' => 'update',
			'data' => ['modified' => (object) 'NOW()'],
			'model' => $this->_model
		]);
		$sql = "UPDATE {mock_database_posts} SET {modified} = NOW();";
		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	public function testDelete() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'new post', 'body' => 'the body'],
			'exists' => true
		]);
		$query = new Query(compact('entity') + ['type' => 'delete']);
		$this->assertTrue($entity->exists());
		$this->assertTrue($this->_db->delete($query));
		$this->assertEqual(1, $query->entity()->id);

		$expected = "DELETE FROM {mock_database_posts} WHERE {id} = 1;";
		$this->assertEqual($expected, $this->_db->sql);
		$this->assertFalse($entity->exists());
	}

	public function testOrder() {
		$query = new Query(['model' => $this->_model]);

		$result = $this->_db->order("foo_bar", $query);
		$expected = 'ORDER BY {foo_bar} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order("title", $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order("title", $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order(["title"], $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order(["title" => "desc"], $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} DESC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order(["title" => "dasc"], $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order(["title" => []], $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order(['author_id', "title" => "DESC"], $query);
		$expected = 'ORDER BY {MockDatabasePost}.{author_id} ASC, {MockDatabasePost}.{title} DESC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order([], $query);
		$expected = '';
		$this->assertEqual($expected, $result);
	}

	public function testOrderOnRelated() {
		$query = new Query([
			'model' => $this->_model,
			'with' => ['MockDatabaseComment']
		]);

		$result = $this->_db->order('MockDatabaseComment.created DESC', $query);
		$expected = 'ORDER BY {MockDatabaseComment}.{created} DESC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order(['MockDatabaseComment.created' => 'DESC'], $query);
		$expected = 'ORDER BY {MockDatabaseComment}.{created} DESC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order(
			[
				'MockDatabasePost.title' => 'ASC',
				'MockDatabaseComment.created' => 'DESC'
			],
			$query
		);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC, {MockDatabaseComment}.{created} DESC';
		$this->assertEqual($expected, $result);

		$result = $this->_db->order(
			[
				'title' => 'ASC',
				'MockDatabaseComment.created' => 'DESC'
			],
			$query
		);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC, {MockDatabaseComment}.{created} DESC';
		$this->assertEqual($expected, $result);
	}

	public function testScopedDelete() {
		$query = new Query([
			'type' => 'delete',
			'conditions' => ['published' => false],
			'model' => $this->_model
		]);
		$sql = 'DELETE FROM {mock_database_posts} WHERE {published} = 0;';
		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	public function testScopedUpdate() {
		$query = new Query([
			'type' => 'update',
			'conditions' => ['expires' => ['>=' => '2010-05-13']],
			'data' => ['published' => false, 'comments' => null],
			'model' => $this->_model
		]);
		$sql = "UPDATE {mock_database_posts} SET {published} = 0, {comments} = NULL WHERE ";
		$sql .= "({expires} >= '2010-05-13');";
		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	public function testQueryOperators() {
		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'score' => ['between' => [90, 100]]
		]]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE ({score} ";
		$sql .= "BETWEEN 90 AND 100);";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'score' => ['not between' => [90, 100]]
		]]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE ({score} ";
		$sql .= "NOT BETWEEN 90 AND 100);";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'score' => ['>' => 90, '<' => 100]
		]]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE ";
		$sql .= "({score} > 90 AND {score} < 100);";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'score' => ['!=' => [98, 99, 100]]
		]]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} ";
		$sql .= "WHERE ({score} NOT IN (98, 99, 100));";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'scorer' => ['like' => '%howard%']
		]]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} ";
		$sql .= "WHERE ({scorer} LIKE '%howard%');";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$conditions = "custom conditions string";
		$query = new Query(compact('conditions') + [
			'type' => 'read', 'model' => $this->_model
		]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE {$conditions};";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'conditions' => [
				'field' => ['like' => '%value%', 'not like' => '%value2%']
			]
		]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE ";
		$sql .= "({field} LIKE '%value%' AND {field} NOT LIKE '%value2%');";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'scorer' => ['is' => null]
		]]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} ";
		$sql .= "WHERE ({scorer} IS NULL);";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'scorer' => ['is not' => null]
		]]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} ";
		$sql .= "WHERE ({scorer} IS NOT NULL);";
		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	public function testConditions() {
		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'conditions' => [
				'or' => [
					'id' => 'value1',
					'title' => 'value2',
					'and' => [
						'author_id' => '1',
						'created' => '2012-05-25 23:41:00'
					],
					['title' => 'value2'],
					['title' => null]
				],
				'id' => '3',
				'author_id' => false
			]
		]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE ";
		$sql .= "({MockDatabasePost}.{id} = 0 OR {MockDatabasePost}.{title} = 'value2' OR ";
		$sql .= "({MockDatabasePost}.{author_id} = 1 AND {MockDatabasePost}.{created} = ";
		$sql .= "'2012-05-25 23:41:00') OR ({MockDatabasePost}.{title} = 'value2') OR ";
		$sql .= "({MockDatabasePost}.{title} IS NULL)) AND {MockDatabasePost}.{id} = 3 AND ";
		$sql .= "{MockDatabasePost}.{author_id} = 0;";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'conditions' => ['title' => ['0900']]
		]);

		$sql = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost}';
		$sql .= ' WHERE {title} IN (\'0900\');';
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$sql = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE ';
		$sql .= 'lower(title) = \'test\';';

		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'conditions' => ['lower(title)' => 'test']
		]);

		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'conditions' => [(object) 'lower(title) = \'test\'']
		]);

		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$sql = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} WHERE ';
		$sql .= 'lower(title) = REGEXP \'^test$\';';

		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'conditions' => [(object) 'lower(title) = REGEXP \'^test$\'']
		]);

		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'conditions' => ['lower(title)' => (object) 'REGEXP \'^test$\'']
		]);

		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	public function testHaving() {
		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'having' => [
				'or' => [
					'id' => 'value1',
					'title' => 'value2',
					'and' => [
						'author_id' => '1',
						'created' => '2012-05-25 23:41:00'
					],
					['title' => 'value2'],
					['title' => null]
				],
				'id' => '3',
				'author_id' => false
			]
		]);
		$sql = "SELECT * FROM {mock_database_posts} AS {MockDatabasePost} HAVING ";
		$sql .= "({MockDatabasePost}.{id} = 0 OR {MockDatabasePost}.{title} = 'value2' OR ";
		$sql .= "({MockDatabasePost}.{author_id} = 1 AND {MockDatabasePost}.{created} = ";
		$sql .= "'2012-05-25 23:41:00') OR ({MockDatabasePost}.{title} = 'value2') OR ";
		$sql .= "({MockDatabasePost}.{title} IS NULL)) AND {MockDatabasePost}.{id} = 3 AND ";
		$sql .= "{MockDatabasePost}.{author_id} = 0;";
		$this->assertEqual($sql, $this->_db->renderCommand($query));

		$query = new Query([
			'type' => 'read', 'model' => $this->_model,
			'having' => ['title' => ['0900']]
		]);

		$sql = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost}';
		$sql .= ' HAVING {title} IN (\'0900\');';
		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	public function testConstraints() {
		$model = $this->_model;
		$query = new Query([
			'type' => 'read',
			'model' => $this->_model,
			'with' => [
				'MockDatabaseComment' => [
					'constraints' => [
						'or' => [
							['custom_id' => 'MockDatabasePost.value_id'],
							['custom_id' => 'id'],
							'and' => [
								'id' => 'MockDatabasePost.id',
								'title' => 'MockDatabasePost.title'
							],
							['title' => (object) $this->_db->value('value2')],
							['title' => null]
						],
						'id' => 5
					]
				]
			]
		]);

		$sql = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} LEFT JOIN ';
		$sql .= '{mock_database_comments} AS {MockDatabaseComment} ON ';
		$sql .= '(({MockDatabasePost}.{custom_id} = {MockDatabasePost}.{value_id}) OR ';
		$sql .= '({MockDatabasePost}.{custom_id} = {MockDatabaseComment}.{id}) OR ';
		$sql .= '({MockDatabasePost}.{id} = {MockDatabasePost}.{id} ';
		$sql .= 'AND {MockDatabasePost}.{title} = {MockDatabasePost}.{title}) ';
		$sql .= 'OR ({MockDatabasePost}.{title} = \'value2\') ';
		$sql .= 'OR ({MockDatabasePost}.{title} IS NULL)) AND {MockDatabasePost}.{id} = 5;';

		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	public function testReadConditionsWithModel() {
		$model = $this->_model;
		$options = [
			'type' => 'read',
			'model' => $this->_model,
			'conditions' => ['id' => 1, 'MockDatabaseComment.id' => 2],
			'with' => ['MockDatabaseComment']
		];
		$result = $this->_db->read(new Query($options), $options);
		$expected = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} LEFT JOIN ';
		$expected .= '{mock_database_comments} AS {MockDatabaseComment} ON ';
		$expected .= '{MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id} ';
		$expected .= 'WHERE {MockDatabasePost}.{id} = 1 AND {MockDatabaseComment}.{id} = 2;';
		$this->assertEqual($expected, $this->_db->sql);
	}

	public function testFields() {
		$query = new Query([
			'type' => 'read',
			'model' => $this->_model,
			'with' => ['MockDatabaseComment']
		]);

		$fields = ['id', 'title'];
		$result = $this->_db->fields($fields, $query);
		$expected = '{MockDatabasePost}.{id}, {MockDatabasePost}.{title}';
		$this->assertEqual($expected,$result);

		$fields = [
			'MockDatabasePost' => ['id', 'title', 'created'],
			'MockDatabaseComment' => ['body']
		];
		$result = $this->_db->fields($fields, $query);
		$expected = '{MockDatabasePost}.{id}, {MockDatabasePost}.{title},';
		$expected .= ' {MockDatabasePost}.{created}, {MockDatabaseComment}.{body}';
		$this->assertEqual($expected,$result);

		$fields = ['MockDatabasePost', 'MockDatabaseComment'];
		$result = $this->_db->fields($fields, $query);
		$expected = '{MockDatabasePost}.*, {MockDatabaseComment}.*';
		$this->assertEqual($expected, $result);

		$fields = ['MockDatabasePost.id as idPost', 'MockDatabaseComment.id AS idComment'];
		$result = $this->_db->fields($fields, $query);
		$expected = '{MockDatabasePost}.{id} as idPost, {MockDatabaseComment}.{id} as idComment';
		$this->assertEqual($expected, $result);

		$expected = ['' => ['idPost'], 'MockDatabaseComment' => ['idComment']];
		$this->assertEqual($expected, $query->map());

		$fields = [['count(MockDatabasePost.id)']];
		$expected = 'count(MockDatabasePost.id)';
		$result = $this->_db->fields($fields, $query);
		$this->assertEqual($expected, $result);

		$fields = [[(object) 'count(MockDatabasePost.id)']];
		$expected = 'count(MockDatabasePost.id)';
		$result = $this->_db->fields($fields, $query);
		$this->assertEqual($expected, $result);
	}

	public function testFieldsWithEmptyAlias() {
		$query = new Query();
		$result = $this->_db->fields(['id', 'name', 'created'], $query);
		$expected = '{id}, {name}, {created}';
		$this->assertEqual($expected, $result);
	}

	public function testRawConditions() {
		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => null]);
		$this->assertEmpty($this->_db->conditions(5, $query));
		$this->assertEmpty($this->_db->conditions(null, $query));
		$this->assertEqual("WHERE CUSTOM", $this->_db->conditions("CUSTOM", $query));
	}

	public function testRawHaving() {
		$query = new Query(['type' => 'read', 'model' => $this->_model, 'having' => null]);
		$this->assertEmpty($this->_db->having(5, $query));
		$this->assertEmpty($this->_db->having(null, $query));
		$this->assertEqual("HAVING CUSTOM", $this->_db->having("CUSTOM", $query));
	}

	/**
	 * Verifies that setting options using a raw SQL string works, when
	 * the operation returns no result.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/1210
	 */
	public function testRawOptionSettingWithNoResultResource() {
		$expected = [];
		$result = $this->_db->read('SET SESSION group_concat_max_len = 100000;');
		$this->assertEqual($expected, $result);
	}

	public function testRelationshipGeneration() {
		$comment = 'lithium\tests\mocks\data\model\MockDatabaseComment';

		$hasMany = $this->_db->relationship($this->_model, 'hasMany', 'Comments', [
			'to' => $comment
		]);
		$this->assertEqual(['id' => 'mock_database_post_id'], $hasMany->key());
		$this->assertEqual('comments', $hasMany->fieldName());

		$belongsTo = $this->_db->relationship($comment, 'belongsTo', 'Posts', [
			'to' => $this->_model
		]);
		$this->assertEqual(['post_id' => 'id'], $belongsTo->key());
		$this->assertEqual('post', $belongsTo->fieldName());
	}

	public function testRelationshipGenerationWithNullConstraint() {
		$postRevision = 'lithium\tests\mocks\data\model\MockDatabasePostRevision';

		$hasMany = $this->_db->relationship($this->_model, 'hasMany', 'PostRevisions', [
			'to' => $postRevision,
			'constraints' => ['MockDatabasePostRevision.deleted' => null]
		]);
		$this->assertEqual(['id' => 'mock_database_post_id'], $hasMany->key());
		$this->assertEqual('post_revisions', $hasMany->fieldName());

		$expected = [
			'MockDatabasePostRevision.deleted' => null,
			'MockDatabasePost.id' => 'PostRevisions.mock_database_post_id'
		];
		$result = $this->_db->on($hasMany);
		$this->assertEqual($expected, $result);

		$belongsTo = $this->_db->relationship($postRevision, 'belongsTo', 'Posts', [
			'to' => $this->_model
		]);
		$this->assertEqual(['post_id' => 'id'], $belongsTo->key());
		$this->assertEqual('post', $belongsTo->fieldName());
	}

	public function testInvalidQueryType() {
		$db = $this->_db;

		$this->assertException('Invalid query type `fakeType`.', function() use ($db) {
			$db->read(new Query(['type' => 'fakeType']));
		});
	}

	public function testReadWithRelationship() {
		$options = [
			'type' => 'read',
			'model' => $this->_model,
			'with' => ['MockDatabaseComment']
		];
		$result = $this->_db->read(new Query($options), $options);
		$expected = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} LEFT JOIN ';
		$expected .= '{mock_database_comments} AS {MockDatabaseComment} ON ';
		$expected .= '{MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id};';
		$this->assertEqual($expected, $this->_db->sql);
	}

	public function testReadWithRelationshipWithNullConstraint() {
		$options = [
			'type' => 'read',
			'model' => $this->_model,
			'with' => ['MockDatabasePostRevision']
		];
		$result = $this->_db->read(new Query($options), $options);
		$expected = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} LEFT JOIN ';
		$expected .= '{mock_database_post_revisions} AS {MockDatabasePostRevision} ON ';
		$expected .= '{MockDatabasePostRevision}.{deleted} IS NULL AND ';
		$expected .= '{MockDatabasePost}.{id} = {MockDatabasePostRevision}.';
		$expected .= '{mock_database_post_id};';
		$this->assertEqual($expected, $this->_db->sql);
	}

	public function testReadWithHasManyAndLimit() {
		$options = [
			'type' => 'read',
			'model' => $this->_model,
			'with' => ['MockDatabaseComment'],
			'limit' => 1
		];
		$result = $this->_db->read(new Query($options), $options);
		$this->assertNotInstanceOf('lithium\data\collection\RecordSet', $result);
	}

	public function testGroup() {
		$query = new Query([
			'type' => 'read', 'model' => $this->_model
		]);
		$result = $this->_db->group(['id'], $query);
		$expected = 'GROUP BY {MockDatabasePost}.{id}';
		$this->assertEqual($expected, $result);
	}

	public function testGroupWithAlias() {
		$query = new Query([
			'type' => 'read', 'model' => $this->_model, 'alias' => 'MyModel'
		]);
		$result = $this->_db->group('id', $query);
		$expected = 'GROUP BY {MyModel}.{id}';
		$this->assertEqual($expected, $result);
	}

	public function testGroupOnRelation() {
		$query = new Query([
			'type' => 'read',
			'model' => $this->_comment,
			'with' => 'MockDatabasePost',
			'group' => ['MockDatabaseComment.id']
		]);

		$sql = 'SELECT * FROM {mock_database_comments} AS {MockDatabaseComment} LEFT JOIN ';
		$sql .= '{mock_database_posts} AS {MockDatabasePost} ON {MockDatabaseComment}.';
		$sql .= '{mock_database_post_id} = {MockDatabasePost}.{id} GROUP BY ';
		$sql .= '{MockDatabaseComment}.{id};';
		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	public function testLimit() {
		MockDatabasePost::find('all', ['limit' => 15]);
		$result = $this->_db->sql;
		$expected = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} LIMIT 15;';
		$this->assertEqual($expected, $result);

		MockDatabasePost::find('all', ['limit' => 10, 'page' => 3]);
		$result = $this->_db->sql;
		$expected = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} LIMIT 10 OFFSET 20;';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that various syntaxes for the `'order'` key of the query object produce the correct
	 * SQL.
	 */
	public function testQueryOrderSyntaxes() {
		$query = new Query([
			'type' => 'read', 'model' => $this->_model, 'order' => ['created' => 'ASC']
		]);
		$sql = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} ';
		$sql .= 'ORDER BY {MockDatabasePost}.{created} ASC;';
		$this->assertEqual($sql, $this->_db->renderCommand($query));
	}

	/**
	 * Tests that complex model constraints with custom operators render correct constraint strings.
	 */
	public function testRenderArrayJoinConstraintComplex() {
		$model = 'lithium\tests\mocks\data\model\MockQueryComment';

		$query = new Query(compact('model') + [
			'type' => 'read',
			'source' => 'comments',
			'alias' => 'Comments',
			'conditions' => ['Comment.id' => 1],
			'joins' => [[
				'mode' => 'INNER',
				'source' => 'posts',
				'alias' => 'Post',
				'constraints' => ['Comment.post_id' => ['<=' => 'Post.id']]
			]]
		]);

		$expected = "SELECT * FROM {comments} AS {Comments} INNER JOIN {posts} AS {Post} ON ";
		$expected .= "({Comment}.{post_id} <= {Post}.{id}) WHERE {Comment}.{id} = 1;";
		$result = $this->_db->renderCommand($query);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that complex model constraints with custom operators render correct constraint strings.
	 */
	public function testRenderArrayJoinConstraintComplexArray() {
		$model = 'lithium\tests\mocks\data\model\MockQueryComment';

		$query = new Query(compact('model') + [
			'type' => 'read',
			'source' => 'comments',
			'alias' => 'Comments',
			'conditions' => ['Comment.id' => 1],
			'joins' => [[
				'mode' => 'LEFT',
				'source' => 'posts',
				'alias' => 'Post',
				'constraints' => [
					"Comment.post_id" => [
						'<=' => 'Post.id',
						'>=' => 'Post.id'
					]
				]
			]]
		]);

		$expected = "SELECT * FROM {comments} AS {Comments} LEFT JOIN {posts} AS {Post} ON ";
		$expected .= "({Comment}.{post_id} <= {Post}.{id} AND {Comment}.{post_id} >= {Post}.{id}) ";
		$expected .= "WHERE {Comment}.{id} = 1;";
		$result = $this->_db->renderCommand($query);
		$this->assertEqual($expected, $result);

		$query = new Query(compact('model') + [
			'type' => 'read',
			'source' => 'comments',
			'alias' => 'Comments',
			'joins' => [[
				'mode' => 'LEFT',
				'source' => 'posts',
				'alias' => 'Post',
				'constraints' => [
					'Comment.post_id' => ['=>' => 'Post.id']
				]
			]]
		]);
		$db = $this->_db;

		$this->assertException("Unsupported operator `=>`.", function() use ($db, $query) {
			$db->renderCommand($query);
		});
	}

	public function testRenderArrayJoin() {
		$model = 'lithium\tests\mocks\data\model\MockQueryComment';

		$query = new Query(compact('model') + [
			'type' => 'read',
			'source' => 'comments',
			'alias' => 'Comment',
			'conditions' => ['Comment.id' => 1],
			'joins' => [[
				'mode' => 'INNER',
				'source' => 'posts',
				'alias' => 'Post',
				'constraints' => ['Comment.post_id' => 'Post.id']
			]]
		]);

		$expected = "SELECT * FROM {comments} AS {Comment} INNER JOIN {posts} AS {Post} ON ";
		$expected .= "{Comment}.{post_id} = {Post}.{id} WHERE {Comment}.{id} = 1;";
		$result = $this->_db->renderCommand($query);
		$this->assertEqual($expected, $result);
	}

	public function testModelFindBy() {
		$this->_db->log = true;
		MockDatabasePost::findById(5, ['with' => 'MockDatabaseComment']);
		$this->_db->log = false;

		$result = $this->_db->logs[0];
		$expected = "SELECT DISTINCT({MockDatabasePost}.{id}) AS _ID_ FROM {mock_database_posts}";
		$expected .= " AS {MockDatabasePost} LEFT JOIN {mock_database_comments} AS ";
		$expected .= "{MockDatabaseComment} ON {MockDatabasePost}.{id} = ";
		$expected .= "{MockDatabaseComment}.{mock_database_post_id} WHERE ";
		$expected .= "{MockDatabasePost}.{id} = 5 LIMIT 1;";
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that when using LIMIT together with relation conditions and relationship,
	 * relation conditions are passed into the subsequent query issued.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/pull/1099
	 */
	public function testModelFindFirstPassesConditionsIntoSubsequent() {
		$this->_db->log = true;
		$this->_db->return['_execute'] = new MockResult([
			'records' => [
				[0 => 5]
			]
		]);

		MockDatabasePost::find('first', [
			'conditions' => [
				'id' => 5,
				'is_published' => true,
				'MockDatabaseComment.is_spam' => false
			],
			'with' => 'MockDatabaseComment'
		]);
		$this->_db->log = false;

		$result = $this->_db->logs;

		$expected[0] = <<<SQL
SELECT DISTINCT({MockDatabasePost}.{id}) AS _ID_
	FROM {mock_database_posts} AS {MockDatabasePost}
	LEFT JOIN {mock_database_comments} AS {MockDatabaseComment}
		ON {MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id}
	WHERE
		{MockDatabasePost}.{id} = 5
		AND {MockDatabasePost}.{is_published} = 1
		AND {MockDatabaseComment}.{is_spam} = 0
	LIMIT 1;
SQL;
		$expected[1] = <<<SQL
SELECT * FROM {mock_database_posts} AS {MockDatabasePost}
	LEFT JOIN {mock_database_comments} AS {MockDatabaseComment}
		ON {MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id}
	WHERE
		{MockDatabasePost}.{id} IN (5)
		AND {MockDatabaseComment}.{is_spam} = 0;
SQL;

		$expected = array_map(function($v) {
			return preg_replace('/[\t\n]+/', ' ', $v);
		}, $expected);
		$this->assertEqual($expected, $result);
	}

	public function testSplitFieldname() {
		$result = $this->_db->splitFieldname('Alias.fieldname');
		$this->assertEqual(['Alias', 'fieldname'], $result);

		$result = $this->_db->splitFieldname('fieldname');
		$this->assertEqual([null, 'fieldname'], $result);

		$result = $this->_db->splitFieldname('fieldname');
		$this->assertEqual([null, 'fieldname'], $result);

		$result = $this->_db->splitFieldname('lower(Alias.fieldname)');
		$this->assertEqual([null, 'lower(Alias.fieldname)'], $result);

		$result = $this->_db->splitFieldname('Alias.*');
		$this->assertEqual(['Alias', '*'], $result);
	}

	public function testOn() {
		$conn = MockDatabasePost::connection();
		$expected = [
			'MockDatabasePost.id' => 'MockDatabaseComment.mock_database_post_id'
		];
		$result = $conn->on(MockDatabasePost::relations('MockDatabaseComment'));
		$this->assertEqual($expected, $result);

		$expected = [
			'MockDatabaseComment.mock_database_post_id' => 'MockDatabasePost.id'
		];
		$result = $conn->on(MockDatabaseComment::relations('MockDatabasePost'));
		$this->assertEqual($expected, $result);

		$expected = [
			'MockDatabasePost.id' => 'MockDatabaseComment.mock_database_post_id',
			'MockDatabasePost.published' => (object) "'yes'"
		];

		$rel = MockDatabasePost::relations('MockDatabaseComment');
		$result = $conn->on($rel, null, null, ['published' => (object) "'yes'"]);

		$this->assertEqual($expected, $result);

		$expected = [
			'CustomPost.id' => 'CustomComment.mock_database_post_id',
			'CustomPost.published' => (object) "'no'"
		];

		$constraints = ['published' => (object) "'no'"];
		$result = $conn->on($rel, 'CustomPost', 'CustomComment', $constraints);
		$this->assertEqual($expected, $result);

		$expected = [
			'CustomPost.id' => 'CustomComment.post_id'
		];

		$constraints = ['CustomPost.id' => 'CustomComment.post_id'];
		$result = $conn->on($rel, 'CustomPost', 'CustomComment', $constraints);
		$this->assertEqual($expected, $result);
	}

	public function testWithGeneration() {
		$model = $this->_gallery;

		$options = [
			'type' => 'read',
			'model' => $model,
			'with' => ['Image.ImageTag.Tag']
		];

		$result = $this->_db->read(new Query($options));
		$expected = 'SELECT * FROM {mock_gallery} AS {Gallery} LEFT JOIN {mock_image} AS {Image} ';
		$expected .= 'ON {Gallery}.{id} = {Image}.{gallery_id} LEFT JOIN {mock_image_tag} AS ';
		$expected .= '{ImageTag} ON {Image}.{id} = {ImageTag}.{image_id} LEFT JOIN {mock_tag} ';
		$expected .= 'AS {Tag} ON {ImageTag}.{tag_id} = {Tag}.{id};';
		$this->assertEqual($expected, $this->_db->sql);

		$model = $this->_imageTag;
		$options = [
			'type' => 'read',
			'model' => $model,
			'with' => ['Image', 'Tag']
		];

		$result = $this->_db->read(new Query($options));
		$expected = 'SELECT * FROM {mock_image_tag} AS {ImageTag} LEFT JOIN {mock_image} AS ';
		$expected .= '{Image} ON {ImageTag}.{image_id} = {Image}.{id} LEFT JOIN {mock_tag} AS ';
		$expected .= '{Tag} ON {ImageTag}.{tag_id} = {Tag}.{id};';
		$this->assertEqual($expected, $this->_db->sql);
	}

	public function testWithOptionAndInlineConstraint() {
		$model = $this->_gallery;

		$options = [
			'type' => 'read',
			'model' => $model,
			'with' => [
				'Image' => [
					'constraints' => [
						'Image.title' => (object) "'MyImage'"
					]
				],
				'Image.ImageTag.Tag' => [
					'constraints' => [
						'Tag.name' => (object) "'MyTag'"
					]
				]
			]
		];
		$result = $this->_db->read(new Query($options));
		$expected = 'SELECT * FROM {mock_gallery} AS {Gallery} ';
		$expected .= 'LEFT JOIN {mock_image} AS {Image} ON {Image}.{title} = \'MyImage\' ';
		$expected .= 'AND {Gallery}.{id} = {Image}.{gallery_id} LEFT JOIN ';
		$expected .= '{mock_image_tag} AS {ImageTag} ON ';
		$expected .= '{Image}.{id} = {ImageTag}.{image_id} LEFT JOIN {mock_tag} AS {Tag} ON ';
		$expected .= '{Tag}.{name} = \'MyTag\' AND {ImageTag}.{tag_id} = {Tag}.{id};';
		$this->assertEqual($expected, $this->_db->sql);

		$to = 'lithium\tests\mocks\data\model\MockImage';
		$model::bind('hasMany', 'Image', ['to' => $to]);
		$to::bind('belongsTo', 'Gallery', ['to' => $model]);

		$result = $this->_db->read(new Query([
			'type' => 'read',
			'model' => $model,
			'with' => [
				'Image.Gallery' => [
					'alias' => 'Gallery2',
					'constraints' => [
						'Gallery.custom_id' => 'Gallery2.id'
					]
				]
			]
		]));
		$expected = 'SELECT * FROM {mock_gallery} AS {Gallery} LEFT JOIN {mock_image} AS {Image}';
		$expected .= ' ON {Gallery}.{id} = {Image}.{gallery_id} LEFT JOIN {mock_gallery} AS ';
		$expected .= '{Gallery2} ON {Gallery}.{custom_id} = {Gallery2}.{id} AND ';
		$expected .= '{Image}.{gallery_id} = {Gallery2}.{id};';
		$this->assertEqual($expected, $this->_db->sql);
		$model::reset();
	}

	public function testWithOptionAndConstraintInRelation() {
		$model = 'lithium\tests\mocks\data\model\MockGallery';
		$to = 'lithium\tests\mocks\data\model\MockImage';
		$model::bind('hasMany', 'Image', [
			'to' => $to,
			'constraints' => [
				'Image.title' => (object) "'MyImage'"
			]
		]);
		$result = $this->_db->read(new Query([
			'type' => 'read',
			'model' => $model,
			'with' => [
				'Image.ImageTag.Tag' => [
					'constraints' => [
						'Tag.name' => (object) "'MyTag'"
					]
				]
			]
		]));

		$expected = 'SELECT * FROM {mock_gallery} AS {Gallery} ';
		$expected .= 'LEFT JOIN {mock_image} AS {Image} ON {Image}.{title} = \'MyImage\' AND ';
		$expected .= '{Gallery}.{id} = {Image}.{gallery_id} LEFT JOIN {mock_image_tag} AS ';
		$expected .= '{ImageTag} ON {Image}.{id} = {ImageTag}.{image_id} LEFT JOIN {mock_tag} AS ';
		$expected .= '{Tag} ON {Tag}.{name} = \'MyTag\' AND {ImageTag}.{tag_id} = {Tag}.{id};';
		$this->assertEqual($expected, $this->_db->sql);

		$model::reset();
	}

	public function testWithOptionWithNullConstraint() {
		$options = [
			'type' => 'read',
			'model' => $this->_model,
			'with' => ['MockDatabasePostRevision']
		];
		$result = $this->_db->read(new Query($options));
		$expected = 'SELECT * FROM {mock_database_posts} AS {MockDatabasePost} LEFT JOIN ';
		$expected .= '{mock_database_post_revisions} AS {MockDatabasePostRevision} ON ';
		$expected .= '{MockDatabasePostRevision}.{deleted} IS NULL AND ';
		$expected .= '{MockDatabasePost}.{id} = {MockDatabasePostRevision}.';
		$expected .= '{mock_database_post_id};';
		$this->assertEqual($expected, $this->_db->sql);
	}

	public function testWithOptionAndCustomAlias() {
		$model = 'lithium\tests\mocks\data\model\MockGallery';

		$model::bind('hasMany', 'Image', [
			'to' => 'lithium\tests\mocks\data\model\MockImage',
			'constraints' => [
				'Image.title' => (object) "'MyImage'"
			]
		]);

		$options = [
			'type' => 'read',
			'model' => $model,
			'alias' => 'MyGallery',
			'with' => [
				'Image' => ['alias' => 'MyImage']
			]
		];

		$result = $this->_db->read(new Query($options));
		$query = new Query($options);
		$expected = 'SELECT * FROM {mock_gallery} AS {MyGallery} LEFT JOIN ';
		$expected .= '{mock_image} AS {MyImage} ON {MyImage}.{title} = \'MyImage\' ';
		$expected .= 'AND {MyGallery}.{id} = {MyImage}.{gallery_id};';
		$this->assertEqual($expected, $this->_db->sql);
		$model::reset();
	}

	public function testJoin() {
		$model =  $this->_model;
		$conn = $model::connection();
		$query = new Query(['type' => 'read', 'model' => $model]);

		$rel = $model::relations('MockDatabaseComment');

		$conn->join($query, $rel, null, null, ['published' => (object) "'yes'"]);

		$joins = $query->joins();
		$expected = [
			'MockDatabaseComment' => [
				'constraints' => [
					'MockDatabasePost.id' => 'MockDatabaseComment.mock_database_post_id',
					'MockDatabasePost.published' => (object) "'yes'"
				],
				'model' => 'lithium\tests\mocks\data\model\MockDatabaseComment',
				'mode' => 'LEFT',
				'alias' => 'MockDatabaseComment'
			]
		];

		$this->assertEqual($expected, $joins);

		$query = new Query(['type' => 'read', 'model' => $model]);

		$rel = $model::relations('MockDatabaseComment');

		$conn->join($query, $rel, null, null, (object) ['published' => (object) "'yes'"]);

		$joins = $query->joins();
		$expected = [
			'MockDatabaseComment' => [
				'constraints' => [
					'published' => (object) "'yes'"
				],
				'model' => 'lithium\tests\mocks\data\model\MockDatabaseComment',
				'mode' => 'LEFT',
				'alias' => 'MockDatabaseComment'
			]
		];

		$this->assertEqual($expected, $joins);
	}

	public function testExportedFieldsWithJoinedStrategy() {
		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'with' => ['Image.ImageTag.Tag']
		]);
		$result = $query->export($this->_db);
		$this->assertEqual('*', $result['fields']);

		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => 'id',
			'with' => ['Image.ImageTag.Tag']
		]);
		$result = $query->export($this->_db);
		$expected = '{Gallery}.{id}';
		$this->assertEqual($expected, $result['fields']);

		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => 'Tag.id',
			'with' => ['Image.ImageTag.Tag']
		]);
		$result = $query->export($this->_db);
		$expected = '{Gallery}.{id}, {Tag}.{id}, {Image}.{id}, {ImageTag}.{id}';
		$this->assertEqual($expected, $result['fields']);

		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => 'Tag',
			'with' => ['Image.ImageTag.Tag']
		]);
		$result = $query->export($this->_db);
		$expected = '{Gallery}.{id}, {Tag}.*, {Image}.{id}, {ImageTag}.{id}';
		$this->assertEqual($expected, $result['fields']);

		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => 'Tag.*',
			'with' => ['Image.ImageTag.Tag']
		]);
		$result = $query->export($this->_db);
		$expected = '{Gallery}.{id}, {Tag}.*, {Image}.{id}, {ImageTag}.{id}';
		$this->assertEqual($expected, $result['fields']);
	}

	public function testExportedFieldsWithJoinedStrategyAndRecursiveRelation() {
		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'with' => ['Parent.Parent']
		]);
		$result = $query->export($this->_db);
		$expected = '*';
		$this->assertEqual($expected, $result['fields']);

		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => 'Parent.name',
			'with' => ['Parent.Parent']
		]);
		$result = $query->export($this->_db);
		$expected = '{Gallery}.{id}, {Parent}.{name}';
		$this->assertEqual($expected, $result['fields']);

		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => 'ParentOfParent.name',
			'with' => ['Parent.Parent' => ['alias' => 'ParentOfParent']]
		]);
		$result = $query->export($this->_db);
		$expected = '{Gallery}.{id}, {ParentOfParent}.{name}, {Parent}.{id}';
		$this->assertEqual($expected, $result['fields']);
	}

	public function testCustomField() {
		$field = "(CASE `title` WHEN 'Lotus Flower' THEN 'Found' ELSE 'Not Found' END) as extra";
		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => ['*', $field]
		]);
		$result = $this->_db->read($query);
		$expected = 'SELECT (CASE `title` WHEN \'Lotus Flower\' THEN \'Found\' ELSE \'Not Found\' ';
		$expected .= 'END) as extra, {Gallery}.* FROM {mock_gallery} AS {Gallery};';
		$this->assertEqual($expected, $this->_db->sql);
		$map = ['' => ['extra', 'id', 'title']];
		$this->assertEqual($map, $query->map());

		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => ['*', (object) $field]
		]);
		$result = $this->_db->read($query);
		$this->assertEqual($expected, $this->_db->sql);
		$this->assertEqual($map, $query->map());

		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => ['*', [$field]]
		]);
		$result = $this->_db->read($query);
		$this->assertEqual($expected, $this->_db->sql);
		$this->assertEqual($map, $query->map());

		$query = new Query([
			'type' => 'read',
			'model' => $this->_gallery,
			'fields' => [(object) 'count(Image.id) as count', 'Image'],
			'group' => 'Gallery.id',
			'with' => ['Image']
		]);
		$result = $this->_db->read($query);
		$expected = 'SELECT count(Image.id) as count, {Gallery}.{id}, {Image}.* FROM ';
		$expected .= '{mock_gallery} AS {Gallery} LEFT JOIN {mock_image} AS {Image} ON ';
		$expected .= '{Gallery}.{id} = {Image}.{gallery_id} GROUP BY {Gallery}.{id};';

		$this->assertEqual($expected, $this->_db->sql);
		$map = [
			'' => ['count', 'id'],
			'Image' => ['id', 'title', 'image', 'gallery_id']
		];
		$this->assertEqual($map, $query->map());
	}

	public function testReturnArrayOnReadWithString() {
		$data = new MockResult(['records' => [
			['id', 'int(11)', 'NO', 'PRI', null, 'auto_increment'],
			['name', 'varchar(256)', 'YES', '', null, '']
		]]);
		$this->_db->return = [
			'schema' => ['field', 'type', 'null', 'key', 'default', 'extra'],
			'_execute' => $data
		];
		$result = $this->_db->read('DESCRIBE {table};', ['return' => 'array']);
		$expected = [
			[
				'field' => 'id',
				'type' => 'int(11)',
				'null' => 'NO',
				'key' => 'PRI',
				'default' => null,
				'extra' => 'auto_increment',
			],
			[
				'field' => 'name',
				'type' => 'varchar(256)',
				'null' => 'YES',
				'key' => '',
				'default' => null,
				'extra' => '',
			]
		];
		$this->assertEqual($expected, $result);
	}

	public function testReturnArrayOnReadWithQuery() {
		$data = new MockResult(['records' => [[
			'1',
			'2',
			'Post title',
			'2012-12-17 17:04:00',
			'3',
			'1',
			'2',
			'Very good post',
			'2012-12-17 17:05:00',
			'1',
			'2',
			'Post title',
			'2012-12-17 17:04:00',
		]]]);
		$this->_db->return = [
			'_execute' => $data
		];
		$query = new Query([
			'type' => 'read',
			'model' => $this->_model,
			'with' => ['MockDatabaseComment.MockDatabasePost']
		]);
		$result = $this->_db->read($query, ['return' => 'array']);
		$expected = [[
			'id' => '1',
			'author_id' => '2',
			'title' => 'Post title',
			'created' => '2012-12-17 17:04:00',
			'MockDatabaseComment' => [
				'id' => '3',
				'post_id' => '1',
				'author_id' => '2',
				'body' => 'Very good post',
				'created' => '2012-12-17 17:05:00',
				'MockDatabasePost' => [
					'id' => '1',
					'author_id' => '2',
					'title' => 'Post title',
					'created' => '2012-12-17 17:04:00',
				]
			]
		]];
		$this->assertEqual($expected, $result);
	}

	public function testCleanRenderCommand() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['title' => '{:foobar}'],
			'exists' => false
		]);

		$query = new Query(compact('entity') + ['type' => 'create']);
		$result = $this->_db->create($query);

		$expected = "INSERT INTO {mock_database_posts} ({title}) VALUES ('{:foobar}');";
		$this->assertEqual($expected, $this->_db->sql);
	}

	public function testIncrement() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'balance' => 10],
			'exists' => true
		]);

		$entity->increment('balance', 10);
		$query = new Query(compact('entity') + ['type' => 'update']);
		$result = $this->_db->update($query);
		$expected = "UPDATE {mock_database_posts} SET {balance} = {balance} + 10 WHERE {id} = 1;";
		$this->assertEqual($expected, $this->_db->sql);

		$entity->increment('balance', 10);
		$entity->decrement('balance', 20);
		$query = new Query(compact('entity') + ['type' => 'update']);
		$result = $this->_db->update($query);
		$expected = "UPDATE {mock_database_posts} SET {balance} = {balance} + -10 WHERE {id} = 1;";
		$this->assertEqual($expected, $this->_db->sql);

		$entity->increment('balance', 10);
		$entity->balance = 20;
		$query = new Query(compact('entity') + ['type' => 'update']);
		$result = $this->_db->update($query);
		$expected = "UPDATE {mock_database_posts} SET {balance} = 20 WHERE {id} = 1;";
		$this->assertEqual($expected, $this->_db->sql);

		$this->assertException("Field `'name'` cannot be incremented.", function() use ($entity) {
			$entity->name = 'Ali';
			$entity->increment('name', 10);
		});
	}

	public function testHasManyRelationsWithLimitAndWithoutConditions() {
		$this->_db->return['_execute'] = function($sql) {
			if (strpos($sql, 'SELECT DISTINCT') === 0) {
				return new MockResult([
					'records' => [
						[1],
						[2]
					]
				]);
			} else {
				return new MockResult([
					'records' => [
						[
							'1',
							'2',
							'Post title',
							'2014-10-12 01:39:00',
							'3',
							'1',
							'2',
							'Very good post',
							'2014-10-12 01:39:00',
						]
					]
				]);

			}
		};

		$query = new Query([
			'type' => 'read',
			'model' => $this->_model,
			'with' => ['MockDatabaseComment'],
			'limit' => 3,
		]);
		$result = $this->_db->read($query, ['return' => 'array']);

		$expected = [[
			'id' => '1',
			'author_id' => '2',
			'title' => 'Post title',
			'created' => '2014-10-12 01:39:00',
			'MockDatabaseComment' => [
				'id' => '3',
				'post_id' => '1',
				'author_id' => '2',
				'body' => 'Very good post',
				'created' => '2014-10-12 01:39:00',
			]
		]];
		$this->assertEqual($expected, $result);
	}

	public function testMultiHasManyRelationsWithLimit() {
		$this->_db->log = true;
		$this->_db->return['_execute'] = new MockResult([
			'records' => [
				[0 => 5]
			]
		]);

		MockDatabasePost::find('first', [
			'conditions' => [
				'id' => 5,
				'is_published' => true,
				'MockDatabaseComment.is_spam' => false,
				'MockDatabasePostRevision.title' => 'foo',
			],
			'with' => [
				'MockDatabaseComment',
				'MockDatabasePostRevision'
			],
		]);
		$this->_db->log = false;

		$result = $this->_db->logs;

		$expected[0] = <<<SQL
SELECT DISTINCT({MockDatabasePost}.{id}) AS _ID_
	FROM {mock_database_posts} AS {MockDatabasePost}
	LEFT JOIN {mock_database_comments} AS {MockDatabaseComment}
		ON {MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id}
	LEFT JOIN {mock_database_post_revisions} AS {MockDatabasePostRevision}
		ON {MockDatabasePostRevision}.{deleted} IS NULL
			AND {MockDatabasePost}.{id} = {MockDatabasePostRevision}.{mock_database_post_id}
	WHERE
		{MockDatabasePost}.{id} = 5
		AND {MockDatabasePost}.{is_published} = 1
		AND {MockDatabaseComment}.{is_spam} = 0
		AND {MockDatabasePostRevision}.{title} = 'foo'
	LIMIT 1;
SQL;
		$expected[1] = <<<SQL
SELECT * FROM {mock_database_posts} AS {MockDatabasePost}
	LEFT JOIN {mock_database_comments} AS {MockDatabaseComment}
		ON {MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id}
	LEFT JOIN {mock_database_post_revisions} AS {MockDatabasePostRevision}
		ON {MockDatabasePostRevision}.{deleted} IS NULL
			AND {MockDatabasePost}.{id} = {MockDatabasePostRevision}.{mock_database_post_id}
	WHERE
		{MockDatabasePost}.{id} IN (5)
		AND {MockDatabaseComment}.{is_spam} = 0
		AND {MockDatabasePostRevision}.{title} = 'foo';
SQL;

		$expected = array_map(function($v) {
			return preg_replace('/[\t\n]+/', ' ', $v);
		}, $expected);
		$this->assertEqual($expected, $result);
	}

	public function testUpdateWithNoFieldsChanged() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'the post', 'body' => 'the body'],
			'exists' => true
		]);
		$query = new Query(compact('entity') + ['type' => 'update']);
		$result = $this->_db->update($query);

		$this->assertTrue($result);
		$this->assertEqual(1, $query->entity()->id);
		$this->assertNull($this->_db->sql);
	}

	public function testUpdateWithAllFieldsChanged() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'the post', 'body' => 'the body'],
			'exists' => true
		]);
		$entity->title = 'foo';
		$entity->body = 'bar';

		$query = new Query(compact('entity') + [
			'type' => 'update'
		]);
		$result = $this->_db->update($query);

		$this->assertTrue($result);
		$this->assertEqual(1, $query->entity()->id);
		$sql = "UPDATE {mock_database_posts} SET {title} = 'foo', {body} = 'bar' WHERE {id} = 1;";
		$this->assertEqual($sql, $this->_db->sql);
	}

	public function testUpdateWithSomeFieldsChanged() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'the post', 'body' => 'the body'],
			'exists' => true
		]);
		$entity->title = 'foo';

		$query = new Query(compact('entity') + [
			'type' => 'update'
		]);
		$result = $this->_db->update($query);

		$this->assertTrue($result);
		$this->assertEqual(1, $query->entity()->id);
		$sql = "UPDATE {mock_database_posts} SET {title} = 'foo' WHERE {id} = 1;";
		$this->assertEqual($sql, $this->_db->sql);
	}

	public function testUpdateWithNoFieldChanged() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'the post', 'body' => 'the body'],
			'exists' => true
		]);
		$entity->title = 'the post';

		$query = new Query(compact('entity') + [
			'type' => 'update'
		]);
		$result = $this->_db->update($query);

		$this->assertTrue($result);
		$this->assertEqual(1, $query->entity()->id);
		$this->assertNull($this->_db->sql);
	}


	public function testUpdateWithAllFieldsChangedAndWhitelist() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'the post', 'body' => 'the body'],
			'exists' => true
		]);
		$entity->title = 'foo';
		$entity->body = 'bar';

		$query = new Query(compact('entity') + [
			'type' => 'update',
			'whitelist' => ['title', 'body']
		]);
		$result = $this->_db->update($query);

		$this->assertTrue($result);
		$this->assertEqual(1, $query->entity()->id);
		$sql = "UPDATE {mock_database_posts} SET {title} = 'foo', {body} = 'bar' WHERE {id} = 1;";
		$this->assertEqual($sql, $this->_db->sql);
	}

	public function testUpdateSomeFieldsViaWhitelist() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'the post', 'body' => 'the body'],
			'exists' => true
		]);
		$entity->title = 'foo';
		$entity->body = 'bar';

		$query = new Query(compact('entity') + [
			'type' => 'update',
			'whitelist' => ['title']
		]);
		$result = $this->_db->update($query);

		$this->assertTrue($result);
		$this->assertEqual(1, $query->entity()->id);
		$sql = "UPDATE {mock_database_posts} SET {title} = 'foo' WHERE {id} = 1;";
		$this->assertEqual($sql, $this->_db->sql);
	}

	public function testUpdateWithAllChangedFieldsRemovedViaWhitelist() {
		$entity = new Record([
			'model' => $this->_model,
			'data' => ['id' => 1, 'title' => 'the post', 'body' => 'the body'],
			'exists' => true
		]);
		$entity->title = 'foo';

		$query = new Query(compact('entity') + [
			'type' => 'update',
			'whitelist' => ['body']
		]);
		$result = $this->_db->update($query);

		$this->assertTrue($result);
		$this->assertEqual(1, $query->entity()->id);
		$this->assertNull($this->_db->sql);
	}
}

?>