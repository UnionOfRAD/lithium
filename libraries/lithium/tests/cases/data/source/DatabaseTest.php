<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use \lithium\data\model\Query;
use \lithium\data\source\Database;
use \lithium\tests\mocks\data\model\MockDatabase;
use \lithium\tests\mocks\data\model\MockDatabaseTag;
use \lithium\tests\mocks\data\model\MockDatabasePost;
use \lithium\tests\mocks\data\model\MockDatabaseComment;
use \lithium\tests\mocks\data\model\MockDatabaseTagging;

class DatabaseTest extends \lithium\test\Unit {

	public $db = null;

	public function setUp() {
		$this->db = new MockDatabase();
		MockDatabasePost::__init();
		MockDatabaseComment::__init();
	}

	public function testColumnMapping() {
		$result = $this->db->schema(new Query(array(
			'model' =>  'lithium\tests\mocks\data\model\MockDatabasePost'
		)));
		$expected = array(
			'lithium\tests\mocks\data\model\MockDatabasePost' => array('id', 'title', 'created')
		);
		$this->assertEqual($expected, $result);

		$query = new Query(array(
			'model' =>  'lithium\tests\mocks\data\model\MockDatabasePost', 'fields' => '*'
		));
		$result = $this->db->schema($query);
		$this->assertEqual($expected, $result);

		$fields = array('MockDatabaseComment');
		$query = new Query(array(
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost', 'fields' => $fields
		));
		$result = $this->db->schema($query);
		$expected = array(
			'lithium\tests\mocks\data\model\MockDatabaseComment' => array_keys(
				MockDatabaseComment::schema()
			)
		);
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
				'join' => new Query(array(
					'type' => 'read',
					'model' => '\lithium\tests\mocks\data\model\MockDatabaseTag',
					'conditions' => 'MockDatabaseTagging.tag_id = MockDatabaseTag.id'
				))
			)))
		));
		$result = $this->db->renderCommand($query);

		$expected = "SELECT MockDatabasePost.title, MockDatabasePost.body From mock_database_posts";
		$expected .= " WHERE Post.id IN (SELECT post_id From mock_database_taggings WHERE ";
		$expected .= "MockDatabaseTag.tag IN ('foo', 'bar', 'baz'));";
		$this->assertEqual($expected, $result);
	}
}

?>