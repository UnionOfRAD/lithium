<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use \lithium\data\model\Query;
use \lithium\data\model\Record;
use \lithium\data\source\Database;
use \lithium\tests\mocks\data\model\MockDatabase;
use \lithium\tests\mocks\data\model\MockDatabasePost;
use \lithium\tests\mocks\data\model\MockDatabaseComment;

class DatabaseTest extends \lithium\test\Unit {

	public $db = null;

	public function setUp() {
		$this->db = new MockDatabase();
		MockDatabasePost::__init();
		MockDatabaseComment::__init();
	}

	public function testDefaultConfig() {
		$expected = array(
			'persistent'    => true,
			'host'          => 'localhost',
			'login'         => 'root',
			'password'      => '',
			'database'      => 'lithium',
			'autoConnect'   => true,
			'init'          => true
		);
		$result = $this->db->testConfig();
		$this->assertEqual($expected, $result);
	}

	public function testModifyConfig() {
		$db = new MockDatabase(array('host' => '127.0.0.1', 'login' => 'bob'));
		$expected = array(
			'persistent'    => true,
			'host'          => '127.0.0.1',
			'login'         => 'bob',
			'password'      => '',
			'database'      => 'lithium',
			'autoConnect'   => true,
			'init'          => true
		);
		$result = $db->testConfig();
		$this->assertEqual($expected, $result);
	}

	public function testName() {
		$expected = "name";
		$result = $this->db->name($expected);
		$this->assertEqual($expected, $result);
	}

	public function testValueWithSchema() {
		$expected = 'NULL';
		$result = $this->db->value(null);
		$this->assertTrue(is_string($result));
		$this->assertEqual($expected, $result);

		$expected = "'string'";
		$result = $this->db->value('string', array('type' => 'string'));
		$this->assertTrue(is_string($result));
		$this->assertEqual($expected, $result);

		$expected = true;
		$result = $this->db->value('true', array('type' => 'boolean'));
		$this->assertTrue(is_bool($result));
		$this->assertEqual($expected, $result);

		$expected = 1;
		$result = $this->db->value('1', array('type' => 'integer'));
		$this->assertTrue(is_int($result));
		$this->assertEqual($expected, $result);

		$expected = 1.1;
		$result = $this->db->value('1.1', array('type' => 'float'));
		$this->assertTrue(is_float($result));
		$this->assertEqual($expected, $result);
	}

	public function testValueByIntrospect() {
		$expected = "'string'";
		$result = $this->db->value("string");
		$this->assertTrue(is_string($result));
		$this->assertEqual($expected, $result);

		$expected = true;
		$result = $this->db->value(true);
		$this->assertTrue(is_bool($result));
		$this->assertEqual($expected, $result);

		$expected = 1;
		$result = $this->db->value('1');
		$this->assertTrue(is_int($result));
		$this->assertEqual($expected, $result);

		$expected = 1.1;
		$result = $this->db->value('1.1');
		$this->assertTrue(is_float($result));
		$this->assertEqual($expected, $result);
	}

	public function testSchema() {
		$expected = array(
			'lithium\tests\mocks\data\model\MockDatabasePost' => array('id', 'title', 'created')
		);
		$result = $this->db->schema(new Query(array(
			'model' =>  'lithium\tests\mocks\data\model\MockDatabasePost'
		)));
		$this->assertEqual($expected, $result);

		$query = new Query(array(
			'model' =>  'lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => '*'
		));
		$expected = array(
			'lithium\tests\mocks\data\model\MockDatabasePost' => array('id', 'title', 'created')
		);
		$result = $this->db->schema($query);
		$this->assertEqual($expected, $result);

		$query = new Query(array(
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => array('MockDatabaseComment')
		));
		$expected = array(
			'lithium\tests\mocks\data\model\MockDatabaseComment' => array(
				'id', 'post_id', 'author_id', 'body', 'created'
			)
		);
		$result = $this->db->schema($query);
		$this->assertEqual($expected, $result);
	}

	public function testSimpleQueryRender() {
		$result = $this->db->renderCommand(new Query(array(
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => array('id', 'title', 'created')
		)));
		$expected = 'SELECT id, title, created From mock_database_posts;';
		$this->assertEqual($expected, $result);

		$result = $this->db->renderCommand(new Query(array(
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => array('id', 'title', 'created'),
			'limit' => 1
		)));
		$expected = 'SELECT id, title, created From mock_database_posts LIMIT 1;';
		$this->assertEqual($expected, $result);

		$result = $this->db->renderCommand(new Query(array(
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => array('id', 'title', 'created'),
			'limit' => 1,
			'conditions' => 'Post.id = 2'
		)));
		$expected = 'SELECT id, title, created From mock_database_posts WHERE Post.id = 2';
		$expected .= ' LIMIT 1;';
		$this->assertEqual($expected, $result);
	}

	public function testNestedQueryConditions() {
		$query = new Query(array(
			'type' => 'read',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => array('MockDatabasePost.title', 'MockDatabasePost.body'),
			'conditions' => array('Post.id' => new Query(array(
				'type' => 'read',
				'fields' => array('post_id'),
				'model' => '\lithium\tests\mocks\data\model\MockDatabaseTagging',
				'conditions' => array('MockDatabaseTag.tag' => array('foo', 'bar', 'baz')),
			)))
		));
		$result = $this->db->renderCommand($query);

		$expected = "SELECT MockDatabasePost.title, MockDatabasePost.body From mock_database_posts";
		$expected .= " WHERE Post.id IN (SELECT post_id From mock_database_taggings WHERE ";
		$expected .= "MockDatabaseTag.tag IN ('foo', 'bar', 'baz'));";
		$this->assertEqual($expected, $result);
	}

	public function testJoin() {
		$query = new Query(array(
			'type' => 'read',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => array('MockDatabasePost.title', 'MockDatabasePost.body'),
			'conditions' => array('MockDatabaseTag.tag' => array('foo', 'bar', 'baz')),
			'joins' => array(new Query(array(
				'type' => 'read',
				'model' => '\lithium\tests\mocks\data\model\MockDatabaseTag',
				'constraint' => 'MockDatabaseTagging.tag_id = MockDatabaseTag.id'
			)))
		));
		$result = $this->db->renderCommand($query);

		$expected = "SELECT MockDatabasePost.title, MockDatabasePost.body From mock_database_posts";
		$expected .= " JOIN mock_database_tags ON MockDatabaseTagging.tag_id = MockDatabaseTag.id";
		$expected .= " WHERE MockDatabaseTag.tag IN ('foo', 'bar', 'baz');";
		$this->assertEqual($expected, $result);
	}

	public function testItem() {
		$model = '\lithium\tests\mocks\data\model\MockDatabasePost';
		$data = array('title' => 'new post', 'content' => 'This is a new post.');
		$item = $this->db->item($model, $data);
		$result = $item->data();
		$this->assertEqual($data, $result);
	}

	public function testCreate() {
		$record = new Record(array(
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'data' => array('title' => 'new post', 'body' => 'the body')
		));
		$query = new Query(array(
			'type' => 'create',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'record' => $record
		));
		$expected = sha1(serialize($query));
		$result = $this->db->create($query);
		$this->assertTrue($result);
		$result = $query->record()->id;
		$this->assertEqual($expected, $result);

		$expected = "INSERT INTO mock_database_posts"
			. " (title, body)"
			. " VALUES ('new post', 'the body');";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testCreateWithKey() {
		$record = new Record(array(
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'data' => array('id' => 1, 'title' => 'new post', 'body' => 'the body')
		));
		$query = new Query(array(
			'type' => 'create',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'record' => $record
		));
		$expected = 1;
		$result = $this->db->create($query);
		$this->assertTrue($result);
		$result = $query->record()->id;
		$this->assertEqual($expected, $result);

		$expected = "INSERT INTO mock_database_posts"
			. " (id, title, body)"
			. " VALUES (1, 'new post', 'the body');";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithQueryStringReturnResource() {
		$result = $this->db->read('SELECT * from mock_database_posts;', array(
			'return' => 'resource'
		));
		$this->assertTrue($result);

		$expected = "SELECT * from mock_database_posts;";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithQueryObjectRecordSet() {
		$query = new Query(array(
			'type' => 'read',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
		));
		$result = $this->db->read($query);
		$this->assertTrue($result instanceof \lithium\data\collection\RecordSet);

		$expected = "SELECT * From mock_database_posts;";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithQueryObjectArray() {
		$query = new Query(array(
			'type' => 'read',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
		));
		$result = $this->db->read($query, array('return' => 'array'));
		$this->assertTrue(is_array($result));

		$expected = "SELECT * From mock_database_posts;";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testUpdate() {
		$record = new Record(array(
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'data' => array('id' => 1, 'title' => 'new post', 'body' => 'the body'),
			'exists' => true
		));
		$query = new Query(array(
			'type' => 'update',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'record' => $record
		));
		$result = $this->db->update($query);
		$this->assertTrue($result);
		$expected = 1;
		$result = $query->record()->id;
		$this->assertEqual($expected, $result);

		$expected = "UPDATE mock_database_posts SET"
			. " id = 1, title = 'new post', body = 'the body'"
			. " WHERE id = 1;";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$record = new Record(array(
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'data' => array('id' => 1, 'title' => 'new post', 'body' => 'the body'),
			'exists' => true
		));
		$query = new Query(array(
			'type' => 'delete',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'record' => $record
		));
		$result = $this->db->delete($query);
		$this->assertTrue($result);
		$expected = 1;
		$result = $query->record()->id;
		$this->assertEqual($expected, $result);

		$expected = "DELETE From mock_database_posts"
			. " WHERE id = 1;";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}
}

?>