<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use \lithium\data\Model;

class Post extends Model {

	public static function resetSchema() {
		static::_instance()->_schema = array();
	}

	public static function instances() {
		return array_keys(static::$_instances);
	}
}

class Comment extends Model {

	protected $_meta = array('key' => 'comment_id');
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

		Post::init(array('source' => 'post'));
		$this->assertEqual('post', Post::meta('source'));

		Post::init(array('source' => false));
		$this->assertIdentical(false, Post::meta('source'));

		Post::init(array('source' => null));
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

	public function testSimpleFind() {
		$result = Post::find('all', array('limit' => 5));
		$this->assertTrue($result instanceof \lithium\data\model\RecordSet);
		//$this->assertEqual(5, count($result));
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