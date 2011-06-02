<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use lithium\data\Model;
use lithium\data\Entity;
use lithium\data\model\Query;
use lithium\data\Connections;
use lithium\data\entity\Record;
use lithium\tests\mocks\data\MockTag;
use lithium\tests\mocks\data\MockPost;
use lithium\tests\mocks\data\MockComment;
use lithium\tests\mocks\data\MockTagging;
use lithium\tests\mocks\data\MockCreator;
use lithium\tests\mocks\data\MockPostForValidates;
use lithium\tests\mocks\data\source\MockMongoConnection;

class ModelTest extends \lithium\test\Unit {

	protected $_configs = array();

	protected $_altSchema = array(
		'id' => array('type' => 'integer'),
		'author_id' => array('type' => 'integer'),
		'title' => array('type' => 'string'),
		'body' => array('type' => 'text')
	);

	public function setUp() {
		$this->_configs = Connections::config();
		Connections::config(array('mock-source' => array(
			'type' => 'lithium\tests\mocks\data\MockSource'
		)));
		MockPost::config(array('connection' => 'mock-source'));
		MockTag::config();
		MockComment::config();
	}

	public function tearDown() {
		Connections::config(array('mock-source' => false));
		Connections::config($this->_configs);
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
		MockPost::config();
		$this->assertEqual($expected, MockPost::instances());

		Model::config();
		$this->assertEqual($expected, MockPost::instances());

		$this->assertEqual('mock_posts', MockPost::meta('source'));

		MockPost::config(array('source' => 'post'));
		$this->assertEqual('post', MockPost::meta('source'));

		MockPost::config(array('source' => false));
		$this->assertIdentical(false, MockPost::meta('source'));

		MockPost::config(array('source' => null));
		$this->assertIdentical('mock_posts', MockPost::meta('source'));

		MockPost::config();
		$this->assertEqual('mock_posts', MockPost::meta('source'));

		$this->assertEqual('mock-source', MockPost::meta('connection'));
	}

	public function testMetaInformation() {
		$expected = array(
			'class'       => 'lithium\tests\mocks\data\MockPost',
			'name'        => 'MockPost',
			'key'         => 'id',
			'title'       => 'title',
			'source'      => 'mock_posts',
			'connection'  => 'mock-source',
			'initialized' => true,
			'locked'      => true
		);
		MockPost::config();
		$this->assertEqual($expected, MockPost::meta());

		$expected = array(
			'class'       => 'lithium\tests\mocks\data\MockComment',
			'name'       => 'MockComment',
			'key'        => 'comment_id',
			'title'      => 'comment_id',
			'source'     => 'mock_comments',
			'connection' => 'mock-source',
			'initialized' => true,
			'locked'      => true
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
		$result = array_keys(MockPost::relations());
		$expected = array('MockComment');
		$this->assertEqual($expected, $result);

		$result = MockPost::relations('hasMany');
		$this->assertEqual($expected, $result);

		$result = array_keys(MockComment::relations());
		$expected = array('MockPost');
		$this->assertEqual($expected, $result);

		$result = MockComment::relations('belongsTo');
		$this->assertEqual($expected, $result);

		$this->assertFalse(MockComment::relations('hasMany'));
		$this->assertFalse(MockPost::relations('belongsTo'));

		$expected = array(
			'name' => 'MockPost',
			'type' => 'belongsTo',
			'keys' => array('mock_post_id' => 'id'),
			'from' => 'lithium\tests\mocks\data\MockComment',
			'to' => 'lithium\tests\mocks\data\MockPost',
			'link' => 'key',
			'fields' => true,
			'fieldName' => 'mock_post',
			'constraint' => array(),
			'init' => true
		);
		$this->assertEqual($expected, MockComment::relations('MockPost')->data());

		$expected = array('MockComment.mock_post_id' => 'MockPost.id');
		$this->assertEqual($expected, MockComment::relations('MockPost')->constraints());

		$expected = array(
			'name' => 'MockComment',
			'type' => 'hasMany',
			'from' => 'lithium\tests\mocks\data\MockPost',
			'to' => 'lithium\tests\mocks\data\MockComment',
			'fields' => true,
			'keys' => array('id' => 'mock_post_id'),
			'link' => 'key',
			'fieldName' => 'mock_comments',
			'constraint' => array(),
			'init' => true
		);
		$this->assertEqual($expected, MockPost::relations('MockComment')->data());

		$expected = array('MockPost.id' => 'MockComment.mock_post_id');
		$this->assertEqual($expected, MockPost::relations('MockComment')->constraints());
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

		$comment = MockComment::create(
			array('author_id' => 111, 'text' => 'This comment should already exist'),
			array('exists' => true)
		);
		$this->assertTrue($comment->exists());
	}

	public function testSimpleFind() {
		$result = MockPost::find('all');
		$this->assertTrue($result['query'] instanceof Query);
	}

	public function testMagicFinders() {
		$result = MockPost::findById(5);
		$result2 = MockPost::findFirstById(5);
		$this->assertEqual($result2, $result);

		$expected = array('id' => 5);
		$this->assertEqual($expected, $result['query']->conditions());
		$this->assertEqual('read', $result['query']->type());

		$result = MockPost::findAllByFoo(13, array('order' => array('created_at' => 'desc')));
		$this->assertFalse($result['query']->data());
		$this->assertEqual(array('foo' => 13), $result['query']->conditions());
		$this->assertEqual(array('created_at' => 'desc'), $result['query']->order());

		$this->expectException('/Method `findFoo` not defined or handled in class/');
		MockPost::findFoo();
	}

	/**
	 * Tests the find 'first' filter on a simple record set.
	 *
	 * @return void
	 */
	public function testSimpleFindFirst() {
		$result = MockComment::first();
		$this->assertTrue($result instanceof Record);

		$expected = 'First comment';
		$this->assertEqual($expected, $result->text);
	}

    public function testSimpleFindList() {
		$result = MockComment::find('list');
		$this->assertTrue(!empty($result));
		$this->assertTrue(is_array($result));
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

		$finder = array(
			'fields' => array('id', 'email'),
			'conditions' => array('id' => 2)
		);
		MockPost::finder('arrayTest', $finder);
		$result = MockPost::find('arrayTest');
		$expected = $finder + array(
			'order' => null,
			'limit' => null,
			'page' => null,
			'page' => null,
			'with' => array(),
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\MockPost'
		);
		$this->assertEqual($expected, $result['options']);
	}

	public function testCustomFindMethods() {
		$result = MockPost::findFirstById(5);
		$query = $result['query'];
		$this->assertEqual(array('id' => 5), $query->conditions());
		$this->assertEqual(1, $query->limit());
	}

	public function testKeyGeneration() {
		$this->assertEqual('comment_id', MockComment::key());
		$this->assertEqual(array('post_id', 'tag_id'), MockTagging::key());

		$result = MockComment::key(array('comment_id' => 5, 'body' => 'This is a comment'));
		$this->assertEqual(array('comment_id' => 5), $result);

		$result = MockTagging::key(array(
			'post_id' => 2,
			'tag_id' => 5,
			'created' => '2009-06-16 10:00:00'
		));
		$this->assertEqual('id', MockPost::key());
		$this->assertEqual(array('id' => 5), MockPost::key(5));
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

	public function testCustomValidationCriteria() {
		$validates = array(
			'title' => 'A custom message here for empty titles.',
			'email' => array(
				array('notEmpty', 'message' => 'email is empty.')
			)
		);
		$post = MockPostForValidates::create(array(
			'title' => 'custom validation', 'email' => 'asdf'
		));

		$result = $post->validates(array('rules' => $validates));
		$this->assertTrue($result === true);
		$this->assertIdentical(array(), $post->errors());
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

	public function testModelWithNoBackend() {
		$this->assertEqual('mock-source', MockPost::meta('connection'));
		MockPost::config(array('connection' => false));
		$this->assertFalse(MockPost::meta('connection'));
		$schema = MockPost::schema();

		MockPost::overrideSchema($this->_altSchema);
		$this->assertEqual($this->_altSchema, MockPost::schema());

		$post = MockPost::create(array('title' => 'New post'));
		$this->assertTrue($post instanceof Entity);
		$this->assertEqual('New post', $post->title);
		MockPost::overrideSchema($schema);

		$this->expectException('/Connection name not defined/');
		$post->save();
	}

	public function testSave() {
		$schema = MockPost::schema();
		MockPost::overrideSchema($this->_altSchema);
		$data = array('title' => 'New post', 'author_id' => 13);
		$record = MockPost::create($data);
		$result = $record->save();

		$this->assertEqual('create', $result['query']->type());
		$this->assertEqual($data, $result['query']->data());
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $result['query']->model());
		MockPost::overrideSchema($schema);
	}

	public function testSaveWithNoCallbacks() {
		$schema = MockPost::schema();
		MockPost::overrideSchema($this->_altSchema);
		$data = array('title' => 'New post', 'author_id' => 13);
		$record = MockPost::create($data);
		$result = $record->save(null, array('callbacks' => false));

		$this->assertEqual('create', $result['query']->type());
		$this->assertEqual($data, $result['query']->data());
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $result['query']->model());
		MockPost::overrideSchema($schema);
	}

	public function testSaveWithFailedValidation() {
		$data = array('title' => '', 'author_id' => 13);
		$record = MockPost::create($data);
		$result = $record->save(null, array('validate' => array(
			'title' => 'A title must be present'
		)));

		$this->assertIdentical(false, $result);
	}

	public function testImplicitKeyFind() {
		$result = MockPost::find(10);
		$this->assertEqual('read', $result['query']->type());
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $result['query']->model());
		$this->assertEqual(array('id' => 10), $result['query']->conditions());
	}

	public function testDelete() {
		$record = MockPost::create(array('id' => 5), array('exists' => true));
		$result = $record->delete();
		$this->assertEqual('delete', $result['query']->type());
		$this->assertEqual('mock_posts', $result['query']->source());
		$this->assertEqual(array('id' => 5), $result['query']->conditions());
	}

	public function testMultiRecordUpdate() {
		$result = MockPost::update(
			array('published' => false),
			array('expires' => array('>=' => '2010-05-13'))
		);
		$query = $result['query'];
		$this->assertEqual('update', $query->type());
		$this->assertEqual(array('published' => false), $query->data());
		$this->assertEqual(array('expires' => array('>=' => '2010-05-13')), $query->conditions());
	}

	public function testMultiRecordDelete() {
		$result = MockPost::remove(array('published' => false));
		$query = $result['query'];
		$this->assertEqual('delete', $query->type());
		$this->assertEqual(array('published' => false), $query->conditions());

		$keys = array_keys(array_filter($query->export(Connections::get('mock-source'))));
		$this->assertEqual(array('type', 'name', 'conditions', 'model', 'source'), $keys);
	}

	public function testFindFirst() {
		$tag = MockTag::find('first', array('conditions' => array('id' => 2)));
		$tag2 = MockTag::find(2);
		$tag3 = MockTag::first(2);

		$this->assertEqual($tag, $tag2);
		$this->assertEqual($tag, $tag3);
	}

	/**
	 * Tests that varying `count` syntaxes all produce the same query operation (i.e.
	 * `Model::count(...)`, `Model::find('count', ...)` etc).
	 *
	 * @return void
	 */
	public function testCountSyntax() {
		$base = MockPost::count(array('email' => 'foo@example.com'));
		$query = $base['query'];

		$this->assertEqual('read', $query->type());
		$this->assertEqual('count', $query->calculate());
		$this->assertEqual(array('email' => 'foo@example.com'), $query->conditions());

		$result = MockPost::find('count', array('conditions' => array(
			'email' => 'foo@example.com'
		)));
		$this->assertEqual($query, $result['query']);

		$result = MockPost::count(array('conditions' => array('email' => 'foo@example.com')));
		$this->assertEqual($query, $result['query']);
	}

	public function testSettingNestedObjectDefaults() {
		$this->skipIf(!MockMongoConnection::enabled(), 'MongoDb not enabled.');

		MockPost::$connection = new MockMongoConnection();
		$schema = MockPost::schema();

		MockPost::overrideSchema($schema + array('nested.value' => array(
			'type' => 'string',
			'default' => 'foo'
		)));
		$this->assertEqual('foo', MockPost::create()->nested->value);

		$data = array('nested' => array('value' => 'bar'));
		$this->assertEqual('bar', MockPost::create($data)->nested->value);

		MockPost::overrideSchema($schema);
		MockPost::$connection = null;
	}
}

?>