<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use \lithium\data\Model;
use \lithium\data\Connections;
use \lithium\analysis\Inspector;
use \lithium\tests\mocks\data\MockPost;
use \lithium\tests\mocks\data\MockComment;
use \lithium\tests\mocks\data\MockTag;
use \lithium\tests\mocks\data\MockTagging;
use \lithium\tests\mocks\data\MockCreator;
use \lithium\tests\mocks\data\MockPostForValidates;

class ModelTest extends \lithium\test\Unit {

	public function _init() {
		Connections::add('mock-source', '\lithium\tests\mocks\data\MockSource');
	}

	public function testOverrideMeta() {
		$meta = MockTag::meta(array('id' => 'key'));

		$expected = 'mock-source';
		$result = $meta['connection'];
		$this->assertEqual($expected, $result);

		$expected = 'mock_tags';
		$result = $meta['source'];
		$this->assertEqual($expected, $result);

		$expected = 'key';
		$result = $meta['id'];
		$this->assertEqual($expected, $result);
	}

	public function testClassInitialization() {
		$expected = MockPost::instances();
		MockPost::__init();
		$this->assertEqual($expected, MockPost::instances());

		Model::__init();
		$this->assertEqual($expected, MockPost::instances());

		$this->assertEqual('mock_posts', \lithium\tests\mocks\data\MockPost::meta('source'));

		MockPost::__init(array('source' => 'post'));
		$this->assertEqual('post', MockPost::meta('source'));

		MockPost::__init(array('source' => false));
		$this->assertIdentical(false, MockPost::meta('source'));

		MockPost::__init(array('source' => null));
		$this->assertIdentical('mock_posts', MockPost::meta('source'));
	}

	public function testMetaInformation() {
		$expected = array(
			'class'       => 'lithium\tests\mocks\data\MockPost',
			'name'        => 'MockPost',
			'key'         => 'id',
			'title'       => 'title',
			'source'      => 'mock_posts',
			'connection'  => 'mock-source',
			'initialized' => true
		);
		MockPost::__init();
		$this->assertEqual($expected, MockPost::meta());

		$expected = array(
			'class'       => 'lithium\tests\mocks\data\MockComment',
			'name'       => 'MockComment',
			'key'        => 'comment_id',
			'title'      => 'comment_id',
			'source'     => 'mock_comments',
			'connection' => 'mock-source',
			'initialized' => true
		);
		$this->assertEqual($expected, MockComment::meta());

		$expected += array('foo' => 'bar');
		$this->assertEqual($expected, MockComment::meta('foo', 'bar'));

		$expected += array('bar' => true, 'baz' => false);
		$this->assertEqual($expected, MockComment::meta(array('bar' => true, 'baz' => false)));
	}

	public function testSchemaLoading() {
		$result = MockPost::schema();
		$this->assertTrue($result);

		MockPost::resetSchema();
		$this->assertEqual($result, MockPost::schema());
	}

	public function testFieldIntrospection() {
		$this->assertTrue(MockComment::hasField('comment_id'));
		$this->assertFalse(MockComment::hasField('foo'));
		$this->assertEqual('comment_id', MockComment::hasField(array('comment_id')));
	}

	/**
	 * Tests introspecting the relationship settings for the model as a whole, various relationship
	 * types, and individual relationships.
	 *
	 * @todo Some tests will need to change when full relationship support is built out.
	 * @return void
	 */
	public function testRelationshipIntrospection() {
		$result = MockPost::relations();
		$expected = array('MockComment');
		$this->assertEqual($expected, $result);

		$result = MockPost::relations('hasMany');
		$this->assertEqual($expected, $result);

		$result = MockComment::relations();
		$expected = array('MockPost');
		$this->assertEqual($expected, $result);

		$result = MockComment::relations('belongsTo');
		$this->assertEqual($expected, $result);

		$this->assertFalse(MockComment::relations('hasMany'));
		$this->assertFalse(MockPost::relations('belongsTo'));

		$this->assertNull(MockComment::relations('MockPost'));
		$this->assertNull(MockPost::relations('MockComment'));
	}

	public function testSimpleRecordCreation() {
		$comment = MockComment::create(array(
			'author_id' => 451,
			'text' => 'Do you ever read any of the books you burn?'
		));

		$this->assertFalse($comment->exists());
		$this->assertNull($comment->comment_id);

		$expected = 'Do you ever read any of the books you burn?';
		$this->assertEqual($expected, $comment->text);
	}

	public function testSimpleFind() {
		$result = MockPost::find('all');
		$this->assertTrue($result instanceof \lithium\data\model\RecordSet);
	}

	/**
	 * Tests the find 'first' filter on a simple record set.
	 *
	 * @return void
	 */
	public function testSimpleFindFirst() {
		$result = MockComment::first();
		$this->assertTrue($result instanceof \lithium\data\model\Record);

		$expected = 'First comment';
		$this->assertEqual($expected, $result->text);
	}

	public function testFilteredFind() {
		MockComment::applyFilter('find', function($self, $params, $chain) {
			$result = $chain->next($self, $params, $chain);
			if ($result != null) {
				$result->filtered = true;
			}
			return $result;
		});
		$result = MockComment::first();
		$this->assertTrue($result->filtered);
	}

	public function testCustomFinder() {
		$finder = function() {};
		MockPost::finder('custom', $finder);
		$this->assertIdentical($finder, MockPost::finder('custom'));
	}

	public function testCustomFindMethods() {
		print_r(MockPost::findFirstById());
	}

	public function testKeyGeneration() {
		$this->assertEqual('comment_id', MockComment::key());
		$this->assertEqual(array('post_id', 'tag_id'), MockTagging::key());

		$result = MockComment::key(array('comment_id' => 5, 'body' => 'This is a comment'));
		$this->assertEqual(5, $result);

		$result = MockTagging::key(array(
			'post_id' => 2,
			'tag_id' => 5,
			'created' => '2009-06-16 10:00:00'
		));
		$this->assertEqual(array('post_id' => 2, 'tag_id' => 5), $result);
	}

	public function testValidatesFalse() {
		$post = MockPostForValidates::create();

		$result = $post->validates();
		$this->assertTrue($result === false);
		$result = $post->errors();
		$this->assertTrue(!empty($result));

		$expected = array(
			'title' => array('please enter a title'),
			'email' => array('email is empty', 'email is not valid')
		);
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesTitle() {
		$post = MockPostForValidates::create(array('title' => 'new post'));

		$result = $post->validates();
		$this->assertTrue($result === false);
		$result = $post->errors();
		$this->assertTrue(!empty($result));

		$expected = array(
			'email' => array('email is empty', 'email is not valid')
		);
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesEmailIsNotEmpty() {
		$post = MockPostForValidates::create(array('title' => 'new post', 'email' => 'something'));

		$result = $post->validates();
		$this->assertIdentical(false, $result);

		$result = $post->errors();
		$this->assertTrue($result);

		$expected = array('email' => array('email is not valid'));
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesEmailIsValid() {
		$post = MockPostForValidates::create(array(
			'title' => 'new post', 'email' => 'something@test.com'
		));

		$result = $post->validates();
		$this->assertTrue($result === true);
		$result = $post->errors();
		$this->assertTrue(empty($result));
	}

	public function testDefaultValuesFromSchema() {
		$creator = MockCreator::create();
		$expected = array(
			'name' => 'Moe',
			'sign' => 'bar',
			'age' =>  0
		);
		$result = $creator->data();
		$this->assertEqual($expected, $result);

		$creator = MockCreator::create(array('name' => 'Homer'));
		$expected = array(
			'name' => 'Homer',
			'sign' => 'bar',
			'age' =>  0
		);
		$result = $creator->data();
		$this->assertEqual($expected, $result);

		$creator = MockCreator::create(array(
			'sign' => 'Beer', 'skin' => 'yellow', 'age' => 12, 'hair' => false
		));
		$expected = array(
			'name' => 'Moe',
			'sign' => 'Beer',
			'skin' => 'yellow',
			'age' =>  12,
			'hair' => false
		);
		$result = $creator->data();
		$this->assertEqual($expected, $result);

		$expected = 'mock_creators';
		$result = MockCreator::meta('source');
		$this->assertEqual($expected, $result);
	}

	/*
	* @todo create proper mock objects for the following test
	*
	public function testFindAll() {
	    $tags = MockTag::find('all', array('conditions' => array('id' => 2)));

		$this->assertTrue($tags instanceof \lithium\data\model\RecordSet);
		$this->assertEqual(1, $tags->count());
		$tag = $tags->rewind();
		$this->assertTrue($tag instanceof \lithium\data\model\Record);

		$tags2 = MockTag::find('all', array('conditions' => array('id' => 3)));

		$this->assertEqual(0, $tags2->count());
	}

	public function testFindFirst() {
	    $tag = MockTag::find('first', array('conditions' => array('id' => 2)));

		$this->assertTrue($tag instanceof \lithium\data\model\Record);
		$this->assertEqual('2', $tag->id);

		$tag2 = MockTag::find('first', array('conditions' => array('id' => 3)));

		$this->assertNull($tag2);

		$tag = MockTag::find(2);

		$this->assertTrue($tag instanceof \lithium\data\model\Record);
		$this->assertEqual('2', $tag->id);

		$tag2 = MockTag::find(3);

		$this->assertNull($tag2);
	}
	*/
}

?>