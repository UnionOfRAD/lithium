<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use \lithium\data\Model;
use \lithium\data\model\Query;
use \lithium\data\model\Record;
use \lithium\data\model\RecordSet;

class Post extends Model {

	public $hasMany = array('Comment');

	public static function resetSchema() {
		static::_instance()->_schema = array();
	}

	public static function instances() {
		return array_keys(static::$_instances);
	}
}

class Comment extends Model {

	public $belongsTo = array('Post');

	protected $_meta = array('key' => 'comment_id');

	public static function find($type, $options = array()) {
		$defaults = array(
			'conditions' => null, 'fields' => null, 'order' => null, 'limit' => null, 'page' => 1
		);
		$options += $defaults;
		$params = compact('type', 'options');
		$self = static::_instance();

		$filter = function($self, $params) {
			extract($params);
			$query = new Query(array('type' => 'read') + $options);

			return new RecordSet(array(
				'query'    => $query,
				'items'    => array_map(
					function($data) { return new Record(compact('data')); },
					array(
						array('comment_id' => 1, 'author_id' => 123, 'text' => 'First comment'),
						array('comment_id' => 2, 'author_id' => 241, 'text' => 'Second comment'),
						array('comment_id' => 3, 'author_id' => 451, 'text' => 'Third comment')
					)
				)
			));
		};
		$finder = isset($self->_finders[$type]) ? array($self->_finders[$type]) : array();
		return static::_filter(__METHOD__, $params, $filter, $finder);
	}
}

class Tag extends Model {
}

class Tagging extends Model {

	protected $_meta = array('source' => 'posts_tags', 'key' => array('post_id', 'tag_id'));
}

class ModelTest extends \lithium\test\Unit {

	public function setUp() {
		Post::__init();
		Comment::__init();
	}

	public function testClassInitialization() {
		$expected = Post::instances();
		Post::__init();
		$this->assertEqual($expected, Post::instances());

		Model::__init();
		$this->assertEqual($expected, Post::instances());

		$this->assertEqual('posts', Post::meta('source'));

		Post::__init(array('source' => 'post'));
		$this->assertEqual('post', Post::meta('source'));

		Post::__init(array('source' => false));
		$this->assertIdentical(false, Post::meta('source'));

		Post::__init(array('source' => null));
		$this->assertIdentical('posts', Post::meta('source'));
	}

	public function testMetaInformation() {
		$expected = array(
			'class'       => __NAMESPACE__ . '\Post',
			'name'       => 'Post',
			'key'        => 'id',
			'title'      => 'title',
			'source'     => 'posts',
			'prefix'     => '',
			'connection' => 'default'
		);
		$this->assertEqual($expected, Post::meta());

		$expected = array(
			'class'       => __NAMESPACE__ . '\Comment',
			'name'       => 'Comment',
			'key'        => 'comment_id',
			'title'      => 'comment_id',
			'source'     => 'comments',
			'prefix'     => '',
			'connection' => 'default'
		);
		$this->assertEqual($expected, Comment::meta());

		$expected += array('foo' => 'bar');
		$this->assertEqual($expected, Comment::meta('foo', 'bar'));

		$expected += array('bar' => true, 'baz' => false);
		$this->assertEqual($expected, Comment::meta(array('bar' => true, 'baz' => false)));
	}

	public function testSchemaLoading() {
		$result = Post::schema();
		$this->assertTrue($result);

		Post::resetSchema();
		$this->assertEqual($result, Post::schema());
	}

	public function testFieldIntrospection() {
		$this->assertTrue(Comment::hasField('comment_id'));
		$this->assertFalse(Comment::hasField('foo'));
		$this->assertEqual('comment_id', Comment::hasField(array('comment_id')));
	}

	/**
	 * Tests introspecting the relationship settings for the model as a whole, various relationship
	 * types, and individual relationships.
	 *
	 * @todo Some tests will need to change when full relationship support is built out.
	 * @return void
	 */
	public function testRelationshipIntrospection() {
		$result = Post::relations();
		$expected = array('Comment');
		$this->assertEqual($expected, $result);

		$result = Post::relations('hasMany');
		$this->assertEqual($expected, $result);

		$result = Comment::relations();
		$expected = array('Post');
		$this->assertEqual($expected, $result);

		$result = Comment::relations('belongsTo');
		$this->assertEqual($expected, $result);

		$this->assertFalse(Comment::relations('hasMany'));
		$this->assertFalse(Post::relations('belongsTo'));

		$this->assertNull(Comment::relations('Post'));
		$this->assertNull(Post::relations('Comment'));
	}

	public function testSimpleRecordCreation() {
		$comment = Comment::create(array(
			'author_id' => 451,
			'text' => 'Do you ever read any of the books you burn?'
		));

		$this->assertFalse($comment->exists());
		$this->assertNull($comment->comment_id);

		$expected = 'Do you ever read any of the books you burn?';
		$this->assertEqual($expected, $comment->text);
	}

	public function testSimpleFind() {
		$result = Post::find('all');
		$this->assertTrue($result instanceof \lithium\data\model\RecordSet);
	}

	/**
	 * Tests the find 'first' filter on a simple record set.
	 *
	 * @return void
	 */
	public function testSimpleFindFirst() {
		$result = Comment::first();
		$this->assertTrue($result instanceof Record);

		$expected = 'First comment';
		$this->assertEqual($expected, $result->text);
	}

	public function testFilteredFind() {
		Post::applyFilter('find', function($self, $params, $chain) {
			$result = $chain->next($self, $params, $chain);
			return $result;
		});
	}

	public function testCustomFinder() {
		$finder = function() {};
		Post::finder('custom', $finder);
		$this->assertIdentical($finder, Post::finder('custom'));
	}

	public function testCustomFindMethods() {
		print_r(Post::findFirstById());
	}

	public function testKeyGeneration() {
		$this->assertEqual('comment_id', Comment::key());
		$this->assertEqual(array('post_id', 'tag_id'), Tagging::key());

		$result = Comment::key(array('comment_id' => 5, 'body' => 'This is a comment'));
		$this->assertEqual(5, $result);

		$result = Tagging::key(array(
			'post_id' => 2,
			'tag_id' => 5,
			'created' => '2009-06-16 10:00:00'
		));
		$this->assertEqual(array('post_id' => 2, 'tag_id' => 5), $result);
	}

	public function testRelations() {
		
	}
}

?>