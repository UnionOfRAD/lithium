<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use \lithium\data\Connections;
use \lithium\data\model\Query;
use \lithium\data\entity\Record;
use \lithium\data\source\Database;
use \lithium\tests\mocks\data\model\MockDatabase;
use \lithium\tests\mocks\data\model\MockDatabasePost;
use \lithium\tests\mocks\data\model\MockDatabaseComment;

class DatabaseTest extends \lithium\test\Unit {

	public $db = null;

	protected $_configs = array();

	public function setUp() {
		$this->db = new MockDatabase();
		$this->_configs = Connections::config();

		Connections::reset();
		Connections::config(array('mock-database-connection' => array(
			'object' => &$this->db,
			'adapter' => 'MockDatabase'
		)));

		MockDatabasePost::config();
		MockDatabaseComment::config();
	}

	public function tearDown() {
		Connections::reset();
		Connections::config($this->_configs);
	}

	public function testDefaultConfig() {
		$expected = array(
			'persistent'    => true,
			'host'          => 'localhost',
			'login'         => 'root',
			'password'      => '',
			'database'      => null,
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
			'database'      => null,
			'autoConnect'   => true,
			'init'          => true
		);
		$result = $db->testConfig();
		$this->assertEqual($expected, $result);
	}

	public function testName() {
		$expected = "{name}";
		$result = $this->db->name("name");
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

		$result = $this->db->value('true', array('type' => 'boolean'));
		$this->assertIdentical(1, $result);

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

		$result = $this->db->value(true);
		$this->assertIdentical(1, $result);

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
		$expected = array('lithium\tests\mocks\data\model\MockDatabasePost' => array(
			'id', 'author_id', 'title', 'created'
		));
		$result = $this->db->schema(new Query(array(
			'model' =>  'lithium\tests\mocks\data\model\MockDatabasePost'
		)));
		$this->assertEqual($expected, $result);

		$query = new Query(array(
			'model' =>  'lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => '*'
		));
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
		$expected = 'SELECT id, title, created From {mock_database_posts} AS {MockDatabasePost};';
		$this->assertEqual($expected, $result);

		$result = $this->db->renderCommand(new Query(array(
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => array('id', 'title', 'created'),
			'limit' => 1
		)));
		$expected = 'SELECT id, title, created From {mock_database_posts} AS {MockDatabasePost} ';
		$expected .= 'LIMIT 1;';
		$this->assertEqual($expected, $result);

		$result = $this->db->renderCommand(new Query(array(
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost',
			'fields' => array('id', 'title', 'created'),
			'limit' => 1,
			'conditions' => 'Post.id = 2'
		)));
		$expected = 'SELECT id, title, created From {mock_database_posts} AS {MockDatabasePost} ';
		$expected .= 'WHERE Post.id = 2 LIMIT 1;';
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

		$expected = "SELECT MockDatabasePost.title, MockDatabasePost.body From";
		$expected .= " {mock_database_posts} AS {MockDatabasePost} WHERE Post.id IN";
		$expected .= " (SELECT post_id From {mock_database_taggings} AS {MockDatabaseTagging} ";
		$expected .= "WHERE MockDatabaseTag.tag IN ('foo', 'bar', 'baz'));";
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

		$expected = "SELECT MockDatabasePost.title, MockDatabasePost.body From";
		$expected .= " {mock_database_posts} AS {MockDatabasePost} JOIN {mock_database_tags} ON ";
		$expected .= "MockDatabaseTagging.tag_id = MockDatabaseTag.id";
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
		$entity = new Record(array(
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'data' => array('title' => 'new post', 'body' => 'the body')
		));
		$query = new Query(compact('entity') + array(
			'type' => 'create',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
		));
		$expected = sha1(serialize($query));
		$result = $this->db->create($query);
		$this->assertTrue($result);
		$result = $query->entity()->id;
		$this->assertEqual($expected, $result);

		$expected = "INSERT INTO {mock_database_posts} ({title}, {body})";
		$expected .= " VALUES ('new post', 'the body');";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testCreateWithKey() {
		$entity = new Record(array(
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'data' => array('id' => 1, 'title' => 'new post', 'body' => 'the body')
		));
		$query = new Query(compact('entity') + array(
			'type' => 'create',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
		));
		$expected = 1;
		$result = $this->db->create($query);
		$this->assertTrue($result);
		$result = $query->entity()->id;
		$this->assertEqual($expected, $result);

		$expected = "INSERT INTO {mock_database_posts}"
			. " ({id}, {title}, {body})"
			. " VALUES (1, 'new post', 'the body');";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testReadWithQueryStringReturnResource() {
		$result = $this->db->read('SELECT * from mock_database_posts AS MockDatabasePost;', array(
			'return' => 'resource'
		));
		$this->assertTrue($result);

		$expected = "SELECT * from mock_database_posts AS MockDatabasePost;";
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

		$expected = "SELECT * From {mock_database_posts} AS {MockDatabasePost};";
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

		$expected = "SELECT * From {mock_database_posts} AS {MockDatabasePost};";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testUpdate() {
		$entity = new Record(array(
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'data' => array('id' => 1, 'title' => 'new post', 'body' => 'the body'),
			'exists' => true
		));
		$query = new Query(compact('entity') + array(
			'type' => 'update',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
		));
		$result = $this->db->update($query);
		$this->assertTrue($result);
		$expected = 1;
		$result = $query->entity()->id;
		$this->assertEqual($expected, $result);

		$expected = "UPDATE {mock_database_posts} SET"
			. " {id} = 1, {title} = 'new post', {body} = 'the body'"
			. " WHERE id = 1;";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$entity = new Record(array(
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
			'data' => array('id' => 1, 'title' => 'new post', 'body' => 'the body'),
			'exists' => true
		));
		$query = new Query(compact('entity') + array(
			'type' => 'delete',
			'model' => '\lithium\tests\mocks\data\model\MockDatabasePost',
		));
		$result = $this->db->delete($query);
		$this->assertTrue($result);
		$expected = 1;
		$result = $query->entity()->id;
		$this->assertEqual($expected, $result);

		$expected = "DELETE From {mock_database_posts}"
			. " WHERE id = 1;";
		$result = $this->db->sql;
		$this->assertEqual($expected, $result);
	}

	public function testOrder() {
		$query = new Query(array('model' => '\lithium\tests\mocks\data\model\MockDatabasePost'));

		$result = $this->db->order("foo_bar", $query);
		$expected = 'ORDER BY foo_bar ASC';
		$this->assertEqual($expected, $result);

		$result = $this->db->order("title", $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->db->order("title", $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->db->order(array("title"), $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->db->order(array("title" => "desc"), $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} desc';
		$this->assertEqual($expected, $result);

		$result = $this->db->order(array("title" => "dasc"), $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->db->order(array("title" => array()), $query);
		$expected = 'ORDER BY {MockDatabasePost}.{title} ASC';
		$this->assertEqual($expected, $result);

		$result = $this->db->order(array('author_id', "title" => "DESC"), $query);
		$expected = 'ORDER BY {MockDatabasePost}.{author_id} ASC, {MockDatabasePost}.{title} DESC';
		$this->assertEqual($expected, $result);
	}

	public function testScopedDelete() {
		$query = new Query(array(
			'type' => 'delete',
			'conditions' => array('published' => false),
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost'
		));
		$sql = 'DELETE From {mock_database_posts} WHERE published = 0;';
		$this->assertEqual($sql, $this->db->renderCommand($query));
	}

	public function testScopedUpdate() {
		$query = new Query(array(
			'type' => 'update',
			'conditions' => array('expires' => array('>=' => '2010-05-13')),
			'data' => array('published' => false),
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost'
		));
		$sql = "UPDATE {mock_database_posts} SET {published} = 0 WHERE {expires} >= '2010-05-13';";
		$this->assertEqual($sql, $this->db->renderCommand($query));
	}

	public function testQueryOperators() {
		$query = new Query(array(
			'type' => 'read',
			'conditions' => array('score' => array('between' => array(90, 100))),
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost'
		));
		$sql = "SELECT * From {mock_database_posts} AS {MockDatabasePost} WHERE {score} ";
		$sql .= "BETWEEN 90 AND 100;";
		$this->assertEqual($sql, $this->db->renderCommand($query));

		$query = new Query(array(
			'type' => 'read',
			'conditions' => array('score' => array('>' => 90, '<' => 100)),
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost'
		));
		$sql = "SELECT * From {mock_database_posts} AS {MockDatabasePost} WHERE ";
		$sql .= "{score} > 90 AND {score} < 100;";
		$this->assertEqual($sql, $this->db->renderCommand($query));

		$query = new Query(array(
			'type' => 'read',
			'conditions' => array('score' => array('!=' => array(98, 99, 100))),
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost'
		));
		$sql = "SELECT * From {mock_database_posts} AS {MockDatabasePost} ";
		$sql .= "WHERE {score} NOT IN (98, 99, 100);";
		$this->assertEqual($sql, $this->db->renderCommand($query));

		$query = new Query(array(
			'type' => 'read',
			'conditions' => "custom conditions string",
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost'
		));
		$sql = "SELECT * From {mock_database_posts} AS {MockDatabasePost} WHERE ";
		$sql .= "custom conditions string;";
		$this->assertEqual($sql, $this->db->renderCommand($query));
	}

	public function testRawConditions() {
		$query = new Query(array(
			'type' => 'read',
			'conditions' => null,
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost'
		));
		$this->assertFalse($this->db->conditions(5, $query));
		$this->assertFalse($this->db->conditions(null, $query));
		$this->assertEqual("WHERE CUSTOM", $this->db->conditions("CUSTOM", $query));
	}
}

?>