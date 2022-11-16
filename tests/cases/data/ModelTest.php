<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data;

use lithium\aop\Filters;
use lithium\data\model\Query;
use stdClass;
use lithium\util\Inflector;
use lithium\data\Model;
use lithium\data\Schema;
use lithium\data\Connections;
use lithium\tests\mocks\data\MockTag;
use lithium\tests\mocks\data\MockPost;
use lithium\tests\mocks\data\MockComment;
use lithium\tests\mocks\data\MockTagging;
use lithium\tests\mocks\data\MockCreator;
use lithium\tests\mocks\data\MockPostForValidates;
use lithium\tests\mocks\data\MockProduct;
use lithium\tests\mocks\data\MockSubProduct;
use lithium\tests\mocks\data\MockBadConnection;
use lithium\tests\mocks\core\MockCallable;
use lithium\tests\mocks\data\MockSource;
use lithium\tests\mocks\data\model\MockDatabase;

class ModelTest extends \lithium\test\Unit {

	protected $_altSchema = null;

	public function setUp() {
		Connections::add('mocksource', ['object' => new MockSource()]);
		Connections::add('mockconn', ['object' => new MockDatabase()]);

		MockPost::config(['meta' => ['connection' => 'mocksource']]);
		MockTag::config(['meta' => ['connection' => 'mocksource']]);
		MockComment::config(['meta' => ['connection' => 'mocksource']]);
		MockCreator::config(['meta' => ['connection' => 'mocksource']]);

		MockSubProduct::config(['meta' => ['connection' => 'mockconn']]);
		MockProduct::config(['meta' => ['connection' => 'mockconn']]);
		MockPostForValidates::config(['meta' => ['connection' => 'mockconn']]);

		$this->_altSchema = new Schema([
			'fields' => [
				'id' => ['type' => 'integer'],
				'author_id' => ['type' => 'integer'],
				'title' => ['type' => 'string'],
				'body' => ['type' => 'text']
			]
		]);
	}

	public function tearDown() {
		Connections::remove('mocksource');
		Connections::remove('mockconn');

		$models = [
			'lithium\tests\mocks\data\MockPost',
			'lithium\tests\mocks\data\MockTag',
			'lithium\tests\mocks\data\MockComment',
			'lithium\tests\mocks\data\MockCreator',
			'lithium\tests\mocks\data\MockSubProduct',
			'lithium\tests\mocks\data\MockProduct',
			'lithium\tests\mocks\data\MockPostForValidates'
		];
		foreach ($models as $model) {
			$model::reset();
			Filters::clear($model);
		}
	}

	public function testOverrideMeta() {
		MockTag::reset();
		MockTag::meta(['id' => 'key']);
		$meta = MockTag::meta();
		$this->assertFalse($meta['connection']);
		$this->assertEqual('mock_tags', $meta['source']);
		$this->assertEqual('key', $meta['id']);
	}

	public function testClassInitialization() {
		$expected = MockPost::instances();
		MockPost::config();
		$this->assertEqual($expected, MockPost::instances());
		Model::config();
		$this->assertEqual($expected, MockPost::instances());

		$this->assertEqual('mock_posts', MockPost::meta('source'));

		MockPost::config(['meta' => ['source' => 'post']]);
		$this->assertEqual('post', MockPost::meta('source'));

		MockPost::config(['meta' => ['source' => false]]);
		$this->assertFalse(MockPost::meta('source'));

		MockPost::config(['meta' => ['source' => null]]);
		$this->assertIdentical('mock_posts', MockPost::meta('source'));

		MockPost::config();
		$this->assertEqual('mock_posts', MockPost::meta('source'));
		$this->assertEqual('mocksource', MockPost::meta('connection'));

		MockPost::config(['meta' => ['source' => 'toreset']]);
		MockPost::reset();
		MockPost::config(['meta' => ['connection' => 'mocksource']]);
		$this->assertEqual('mock_posts', MockPost::meta('source'));
		$this->assertEqual('mocksource', MockPost::meta('connection'));

		MockPost::config(['query' => ['with' => ['MockComment'], 'limit' => 10]]);
		$expected =  [
			'with' => ['MockComment'],
			'limit' => 10,
			'conditions' => null,
			'fields' => null,
			'order' => null,
			'page' => null,
			'having' => null,
			'group' => null,
			'offset' => null,
			'joins' => []
		];
		$this->assertEqual($expected, MockPost::query());

		$finder = [
			'fields' => ['title', 'body']
		];
		MockPost::finder('myFinder', $finder);
		$result = MockPost::find('myFinder');

		$expected = $finder + [
			'order' => null,
			'limit' => 10,
			'conditions' => null,
			'page' => null,
			'with' => ['MockComment'],
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\MockPost',
			'having' => null,
			'group' => null,
			'offset' => null,
			'joins' => []
		];
		$this->assertEqual($expected, $result['options']);

		$finder = [
			'fields' => ['id', 'title']
		];
		MockPost::reset();
		MockPost::config(['meta' => ['connection' => 'mocksource']]);
		$result = MockPost::finder('myFinder');
		$this->assertNull($result);
	}

	public function testInstanceMethods() {
		MockPost::instanceMethods([]);
		$methods = MockPost::instanceMethods();
		$this->assertEmpty($methods);

		MockPost::instanceMethods([
			'first' => [
				'lithium\tests\mocks\data\source\MockMongoPost',
				'testInstanceMethods'
			],
			'second' => function($entity) {}
		]);

		$methods = MockPost::instanceMethods();
		$this->assertCount(2, $methods);

		MockPost::instanceMethods([
			'third' => function($entity) {}
		]);

		$methods = MockPost::instanceMethods();
		$this->assertCount(3, $methods);
	}

	public function testMetaInformation() {
		$class = 'lithium\tests\mocks\data\MockPost';
		$expected = compact('class') + [
			'name'        => 'MockPost',
			'key'         => 'id',
			'title'       => 'title',
			'source'      => 'mock_posts',
			'connection'  => 'mocksource',
			'locked'      => true
		];

		$this->assertEqual($expected, MockPost::meta());

		$class = 'lithium\tests\mocks\data\MockComment';
		$expected = compact('class') + [
			'name'        => 'MockComment',
			'key'         => 'comment_id',
			'title'       => 'comment_id',
			'source'      => 'mock_comments',
			'connection'  => 'mocksource',
			'locked'      => true
		];
		$this->assertEqual($expected, MockComment::meta());

		$expected += ['foo' => 'bar'];
		MockComment::meta('foo', 'bar');
		$this->assertEqual($expected, MockComment::meta());

		$expected += ['bar' => true, 'baz' => false];
		MockComment::meta(['bar' => true, 'baz' => false]);
		$this->assertEqual($expected, MockComment::meta());
	}

	public function testSchemaLoading() {
		$result = MockPost::schema();
		$this->assertNotEmpty($result);
		$this->assertEqual($result->fields(), MockPost::schema()->fields());

		MockPost::config(['schema' => $this->_altSchema]);
		$this->assertEqual($this->_altSchema->fields(), MockPost::schema()->fields());
	}

	public function testSchemaInheritance() {
		$result = MockSubProduct::schema();
		$this->assertTrue(array_key_exists('price', $result->fields()));
	}

	public function testInitializationInheritance() {
		$meta = [
			'name'       => 'MockSubProduct',
			'source'     => 'mock_products',
			'title'      => 'name',
			'class'      => 'lithium\tests\mocks\data\MockSubProduct',
			'connection' => 'mockconn',
			'key'        => 'id',
			'locked'     => true
		];
		$this->assertEqual($meta, MockSubProduct::meta());

		$this->assertArrayHasKey('MockCreator', MockSubProduct::relations());

		$this->assertCount(4, MockSubProduct::finders());

		$this->assertCount(1, MockSubProduct::initializers());

		$config = ['query' => ['with' => ['MockCreator']]];
		MockProduct::config(compact('config'));
		$this->assertEqual(MockProduct::query(), MockSubProduct::query());

		$expected = ['limit' => 50] + MockProduct::query();
		MockSubProduct::config(['query' => $expected]);
		$this->assertEqual($expected, MockSubProduct::query());

		MockPostForValidates::config([
			'classes' => ['connections' => 'lithium\tests\mocks\data\MockConnections'],
			'meta' => ['connection' => new MockCallable()]
		]);
		$conn = MockPostForValidates::connection();

		$this->assertInstanceOf('lithium\tests\mocks\core\MockCallable', $conn);
	}

	public function testCustomAttributesInheritance() {
		$expected = [
			'prop1' => 'value1',
			'prop2' => 'value2'
		];
		$result = MockSubProduct::attribute('_custom');
		$this->assertEqual($expected, $result);
	}

	public function testAttributesInheritanceWithObject() {
		$expected = [
			'id' => ['type' => 'id'],
			'title' => ['type' => 'string', 'null' => false],
			'body' => ['type' => 'text', 'null' => false]
		];
		$schema = new Schema(['fields' => $expected]);

		MockSubProduct::config(compact('schema'));
		$result = MockSubProduct::schema();
		$this->assertEqual($expected, $result->fields());
	}

	public function testFieldIntrospection() {
		$this->assertNotEmpty(MockComment::hasField('comment_id'));
		$this->assertEmpty(MockComment::hasField('foo'));
		$this->assertEqual('comment_id', MockComment::hasField(['comment_id']));
	}

	/**
	 * Tests introspecting the relationship settings for the model as a whole, various relationship
	 * types, and individual relationships.
	 *
	 * @todo Some tests will need to change when full relationship support is built out.
	 */
	public function testRelationshipIntrospection() {
		$result = array_keys(MockPost::relations());
		$expected = ['MockComment'];
		$this->assertEqual($expected, $result);

		$result = MockPost::relations('hasMany');
		$this->assertEqual($expected, $result);

		$result = array_keys(MockComment::relations());
		$expected = ['MockPost'];
		$this->assertEqual($expected, $result);

		$result = MockComment::relations('belongsTo');
		$this->assertEqual($expected, $result);

		$this->assertEmpty(MockComment::relations('hasMany'));
		$this->assertEmpty(MockPost::relations('belongsTo'));

		$expected = [
			'name' => 'MockPost',
			'type' => 'belongsTo',
			'key' => ['mock_post_id' => 'id'],
			'from' => 'lithium\tests\mocks\data\MockComment',
			'to' => 'lithium\tests\mocks\data\MockPost',
			'link' => 'key',
			'fields' => true,
			'fieldName' => 'mock_post',
			'constraints' => [],
			'strategy' => null
		];
		$this->assertEqual($expected, MockComment::relations('MockPost')->data());

		$expected = [
			'name' => 'MockComment',
			'type' => 'hasMany',
			'from' => 'lithium\tests\mocks\data\MockPost',
			'to' => 'lithium\tests\mocks\data\MockComment',
			'fields' => true,
			'key' => ['id' => 'mock_post_id'],
			'link' => 'key',
			'fieldName' => 'mock_comments',
			'constraints' => [],
			'strategy' => null
		];
		$this->assertEqual($expected, MockPost::relations('MockComment')->data());

		MockPost::config(['meta' => ['connection' => false]]);
		MockComment::config(['meta' => ['connection' => false]]);
		MockTag::config(['meta' => ['connection' => false]]);
	}

	/**
	 * Verifies that modifying the default query through the `query()` method works.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/1314
	 */
	public function testDefaultQueryModification() {
		MockPost::query(['limit' => 23]);

		$result = MockPost::query();
		$this->assertEqual(23, $result['limit']);
	}

	public function testSimpleRecordCreation() {
		$comment = MockComment::create([
			'author_id' => 451,
			'text' => 'Do you ever read any of the books you burn?'
		]);

		$this->assertFalse($comment->exists());
		$this->assertNull($comment->comment_id);

		$expected = 'Do you ever read any of the books you burn?';
		$this->assertEqual($expected, $comment->text);

		$comment = MockComment::create(
			['author_id' => 111, 'text' => 'This comment should already exist'],
			['exists' => true]
		);
		$this->assertTrue($comment->exists());
	}

	public function testSimpleFind() {
		$result = MockPost::find('all');
		$this->assertInstanceOf('lithium\data\model\Query', $result['query']);
	}

	public function testMagicFinders() {
		$result = MockPost::findById(5);
		$result2 = MockPost::findFirstById(5);
		$this->assertEqual($result2, $result);

		$expected = ['id' => 5];
		$this->assertEqual($expected, $result['query']->conditions());
		$this->assertEqual('read', $result['query']->type());

		$result = MockPost::findAllByFoo(13, ['order' => ['created_at' => 'desc']]);
		$this->assertEmpty($result['query']->data());
		$this->assertEqual(['foo' => 13], $result['query']->conditions());
		$this->assertEqual(['created_at' => 'desc'], $result['query']->order());

		$this->assertException('/Method `findFoo` not defined or handled in class/', function() {
			MockPost::findFoo();
		});
	}

	/**
	 * Tests the find 'first' filter on a simple record set.
	 */
	public function testSimpleFindFirst() {
		$result = MockComment::first();
		$this->assertInstanceOf('lithium\data\entity\Record', $result);

		$expected = 'First comment';
		$this->assertEqual($expected, $result->text);
	}

	public function testSimpleFindList() {
		$result = MockComment::find('list');
		$this->assertNotEmpty($result);
		$this->assertInternalType('array', $result);
	}

	public function testFilteredFindInvokedMagically() {
		Filters::apply('lithium\tests\mocks\data\MockComment', 'find', function($params, $next) {
			$result = $next($params);
			if ($result !== null) {
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

		$finder = [
			'fields' => ['id', 'email'],
			'conditions' => ['id' => 2]
		];
		MockPost::finder('arrayTest', $finder);
		$result = MockPost::find('arrayTest');
		$expected = $finder + [
			'order' => null,
			'limit' => null,
			'page' => null,
			'with' => [],
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\MockPost',
			'having' => null,
			'group' => null,
			'offset' => null,
			'joins' => []
		];
		$this->assertEqual($expected, $result['options']);
	}

	public function testCustomFindMethods() {
		$result = MockPost::findFirstById(5);
		$query = $result['query'];
		$this->assertEqual(['id' => 5], $query->conditions());
		$this->assertEqual(1, $query->limit());
	}

	public function testKeyGeneration() {
		$this->assertEqual('comment_id', MockComment::key());
		$this->assertEqual(['post_id', 'tag_id'], MockTagging::key());

		$result = MockComment::key(['comment_id' => 5, 'body' => 'This is a comment']);
		$this->assertEqual(['comment_id' => 5], $result);

		$result = MockTagging::key([
			'post_id' => 2,
			'tag_id' => 5,
			'created' => '2009-06-16 10:00:00'
		]);
		$this->assertEqual('id', MockPost::key());
		$this->assertEqual(['id' => 5], MockPost::key(5));
		$this->assertEqual(['post_id' => 2, 'tag_id' => 5], $result);

		$key = new stdClass();
		$key->foo = 'bar';

		$this->assertEqual(['id' => $key], MockPost::key($key));

		$this->assertNull(MockPost::key([]));

		$model = 'lithium\tests\mocks\data\MockModelCompositePk';
		$this->assertNull($model::key(['client_id' => 3]));

		$result = $model::key(['invoice_id' => 5, 'payment' => '100']);
		$this->assertNull($result);

		$expected = ['client_id' => 3, 'invoice_id' => 5];
		$result = $model::key([
			'client_id' => 3,
			'invoice_id' => 5,
			'payment' => '100']
		);
		$this->assertEqual($expected, $result);
	}

	public function testValidatesFalse() {
		$post = MockPostForValidates::create();

		$result = $post->validates();
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = [
			'title' => ['please enter a title'],
			'email' => ['email is empty', 'email is not valid']
		];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesWithWhitelist() {
		$post = MockPostForValidates::create();

		$whitelist = ['title'];
		$post->title = 'title';
		$result = $post->validates(compact('whitelist'));
		$this->assertTrue($result);

		$post->title = '';
		$result = $post->validates(compact('whitelist'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = ['title' => ['please enter a title']];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesTitle() {
		$post = MockPostForValidates::create(['title' => 'new post']);

		$result = $post->validates();
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = [
			'email' => ['email is empty', 'email is not valid']
		];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesEmailIsNotEmpty() {
		$post = MockPostForValidates::create(['title' => 'new post', 'email' => 'something']);

		$result = $post->validates();
		$this->assertFalse($result);

		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = ['email' => [1 => 'email is not valid']];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesEmailIsValid() {
		$post = MockPostForValidates::create([
			'title' => 'new post', 'email' => 'something@test.com'
		]);

		$result = $post->validates();
		$this->assertTrue($result);
		$result = $post->errors();
		$this->assertEmpty($result);
	}

	public function testCustomValidationCriteria() {
		$validates = [
			'title' => 'A custom message here for empty titles.',
			'email' => [
				['notEmpty', 'message' => 'email is empty.']
			]
		];
		$post = MockPostForValidates::create([
			'title' => 'custom validation', 'email' => 'asdf'
		]);

		$result = $post->validates(['rules' => $validates]);
		$this->assertTrue($result);
		$this->assertIdentical([], $post->errors());
	}

	public function testValidatesCustomEventFalse() {
		$post = MockPostForValidates::create();
		$events = 'customEvent';

		$this->assertFalse($post->validates(compact('events')));
		$this->assertNotEmpty($post->errors());

		$expected = [
			'title' => ['please enter a title'],
			'email' => [
				0 => 'email is empty',
				1 => 'email is not valid',
				3 => 'email is not in 1st list'
			]
		];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesCustomEventValid() {
		$post = MockPostForValidates::create([
			'title' => 'new post', 'email' => 'something@test.com'
		]);

		$events = 'customEvent';
		$result = $post->validates(compact('events'));
		$this->assertTrue($result);
		$result = $post->errors();
		$this->assertEmpty($result);
	}

	public function testValidatesCustomEventsFalse() {
		$post = MockPostForValidates::create();

		$events = ['customEvent','anotherCustomEvent'];

		$result = $post->validates(compact('events'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = [
			'title' => ['please enter a title'],
			'email' => [
				0 => 'email is empty',
				1 => 'email is not valid',
				3 => 'email is not in 1st list',
				4 => 'email is not in 2nd list'
			]
		];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesCustomEventsFirstValid() {
		$post = MockPostForValidates::create([
			'title' => 'new post', 'email' => 'foo@bar.com'
		]);

		$events = ['customEvent','anotherCustomEvent'];

		$result = $post->validates(compact('events'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = [
			'email' => [4 => 'email is not in 2nd list']
		];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testValidatesCustomEventsValid() {
		$post = MockPostForValidates::create([
			'title' => 'new post', 'email' => 'something@test.com'
		]);

		$events = ['customEvent','anotherCustomEvent'];

		$result = $post->validates(compact('events'));
		$this->assertTrue($result);
		$result = $post->errors();
		$this->assertEmpty($result);
	}

	public function testValidationInheritance() {
		$product = MockProduct::create();
		$antique = MockSubProduct::create();

		$errors = [
			'name' => ['Name cannot be empty.'],
			'price' => [
				'Price cannot be empty.',
				'Price must have a numeric value.'
			]
		];

		$this->assertFalse($product->validates());
		$this->assertEqual($errors, $product->errors());

		$errors += [
			'refurb' => ['Must have a boolean value.']
		];

		$this->assertFalse($antique->validates());
		$this->assertEqual($errors, $antique->errors());
	}

	public function testErrorsIsClearedOnEachValidates() {
		$post = MockPostForValidates::create(['title' => 'new post']);
		$result = $post->validates();
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$post->email = 'contact@li3.me';
		$result = $post->validates();
		$this->assertTrue($result);
		$result = $post->errors();
		$this->assertEmpty($result);
	}

	public function testDefaultValuesFromSchema() {
		$creator = MockCreator::create();

		$expected = [
			'name' => 'Moe',
			'sign' => 'bar',
			'age' => 0
		];
		$result = $creator->data();
		$this->assertEqual($expected, $result);

		$creator = MockCreator::create(['name' => 'Homer']);
		$expected = [
			'name' => 'Homer',
			'sign' => 'bar',
			'age' => 0
		];
		$result = $creator->data();
		$this->assertEqual($expected, $result);

		$creator = MockCreator::create([
			'sign' => 'Beer', 'skin' => 'yellow', 'age' => 12, 'hair' => false
		]);
		$expected = [
			'name' => 'Moe',
			'sign' => 'Beer',
			'skin' => 'yellow',
			'age' => 12,
			'hair' => false
		];
		$result = $creator->data();
		$this->assertEqual($expected, $result);

		$expected = 'mock_creators';
		$result = MockCreator::meta('source');
		$this->assertEqual($expected, $result);

		$creator = MockCreator::create(['name' => 'Homer'], ['defaults' => false]);
		$expected = [
			'name' => 'Homer'
		];
		$result = $creator->data();
		$this->assertEqual($expected, $result);
	}

	public function testCreateCollection() {
		MockCreator::config([
			'meta' => ['key' => 'name', 'connection' => 'mockconn']
		]);

		$expected = [
			['name' => 'Homer'],
			['name' => 'Bart'],
			['name' => 'Marge'],
			['name' => 'Lisa']
		];

		$data = [];
		foreach ($expected as $value) {
			$data[] = MockCreator::create($value, ['defaults' => false]);
		}

		$result = MockCreator::create($data, ['class' => 'set']);
		$this->assertCount(4, $result);
		$this->assertInstanceOf('lithium\data\collection\RecordSet', $result);

		$this->assertEqual($expected, $result->to('array', ['indexed' => false]));
	}

	public function testModelWithNoBackend() {
		MockPost::reset();
		$this->assertFalse(MockPost::meta('connection'));
		$schema = MockPost::schema();

		MockPost::config(['schema' => $this->_altSchema]);
		$this->assertEqual($this->_altSchema->fields(), MockPost::schema()->fields());

		$post = MockPost::create(['title' => 'New post']);
		$this->assertInstanceOf('lithium\data\Entity', $post);
		$this->assertEqual('New post', $post->title);
	}

	public function testSave() {
		MockPost::config(['schema' => $this->_altSchema]);
		MockPost::config(['schema' => new Schema()]);
		$data = ['title' => 'New post', 'author_id' => 13, 'foo' => 'bar'];
		$record = MockPost::create($data);
		$result = $record->save();

		$this->assertEqual('create', $result['query']->type());
		$this->assertEqual($data, $result['query']->data());
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $result['query']->model());

		MockPost::config(['schema' => $this->_altSchema]);
		$record->tags = ["baz", "qux"];
		$otherData = ['body' => 'foobar'];
		$result = $record->save($otherData);
		$data['body'] = 'foobar';
		$data['tags'] = ["baz", "qux"];

		$expected = ['title' => 'New post', 'author_id' => 13, 'body' => 'foobar'];
		$this->assertNotEqual($data, $result['query']->data());
	}

	public function testSaveWithNoCallbacks() {
		MockPost::config(['schema' => $this->_altSchema]);

		$data = ['title' => 'New post', 'author_id' => 13];
		$record = MockPost::create($data);
		$result = $record->save(null, ['callbacks' => false]);

		$this->assertEqual('create', $result['query']->type());
		$this->assertEqual($data, $result['query']->data());
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $result['query']->model());
	}

	public function testSaveWithFailedValidation() {
		$data = ['title' => '', 'author_id' => 13];
		$record = MockPost::create($data);
		$result = $record->save(null, [
			'validate' => [
				'title' => 'A title must be present'
			]
		]);
		$this->assertFalse($result);
	}

	public function testSaveFailedWithValidationByModelDefinition() {
		$post = MockPostForValidates::create();

		$result = $post->save();
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = [
			'title' => ['please enter a title'],
			'email' => ['email is empty', 'email is not valid']
		];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testSaveFailedWithValidationByModelDefinitionAndTriggeredCustomEvents() {
		$post = MockPostForValidates::create();
		$events = ['customEvent','anotherCustomEvent'];

		$result = $post->save(null,compact('events'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = [
			'title' => ['please enter a title'],
			'email' => [
				0 => 'email is empty',
				1 => 'email is not valid',
				3 => 'email is not in 1st list',
				4 => 'email is not in 2nd list'
			]
		];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testSaveWithValidationAndWhitelist() {
		$post = MockPostForValidates::create();

		$whitelist = ['title'];
		$post->title = 'title';
		$result = $post->save(null, compact('whitelist'));
		$this->assertTrue($result);

		$post->title = '';
		$result = $post->save(null, compact('whitelist'));
		$this->assertFalse($result);
		$result = $post->errors();
		$this->assertNotEmpty($result);

		$expected = ['title' => ['please enter a title']];
		$result = $post->errors();
		$this->assertEqual($expected, $result);
	}

	public function testWhitelistWhenLockedUsingCreateForData() {
		MockPost::config([
			'schema' => $this->_altSchema,
			'meta' => [
				'locked' => true,
				'connection' => 'mocksource'
			]
		]);

		$data = ['title' => 'New post', 'foo' => 'bar'];
		$record = MockPost::create($data);

		$expected = ['title' => 'New post'];
		$result = $record->save();
		$this->assertEqual($expected, $result['query']->data());

		$data = ['foo' => 'bar'];
		$record = MockPost::create($data);

		$expected = [];
		$result = $record->save();
		$this->assertEqual($expected, $result['query']->data());
	}

	public function testWhitelistWhenLockedUsingSaveForData() {
		MockPost::config([
			'schema' => $this->_altSchema,
			'meta' => [
				'locked' => true,
				'connection' => 'mocksource'
			]
		]);

		$data = ['title' => 'New post', 'foo' => 'bar'];
		$record = MockPost::create();

		$expected = ['title' => 'New post'];
		$result = $record->save($data);
		$this->assertEqual($expected, $result['query']->data());

		$data = ['foo' => 'bar'];
		$record = MockPost::create();

		$expected = [];
		$result = $record->save($data);
		$this->assertEqual($expected, $result['query']->data());
	}

	public function testWhitelistWhenLockedUsingCreateWithValidAndSaveForInvalidData() {
		MockPost::config([
			'schema' => $this->_altSchema,
			'meta' => [
				'locked' => true,
				'connection' => 'mocksource'
			]
		]);

		$data = ['title' => 'New post'];
		$record = MockPost::create($data);

		$expected = ['title' => 'New post'];
		$data = ['foo' => 'bar'];
		$result = $record->save($data);
		$this->assertEqual($expected, $result['query']->data());
	}

	public function testImplicitKeyFind() {
		$result = MockPost::find(10);
		$this->assertEqual('read', $result['query']->type());
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $result['query']->model());
		$this->assertEqual(['id' => 10], $result['query']->conditions());
	}

	public function testDelete() {
		$record = MockPost::create(['id' => 5], ['exists' => true]);
		$result = $record->delete();
		$this->assertEqual('delete', $result['query']->type());
		$this->assertEqual('mock_posts', $result['query']->source());
		$this->assertEqual(['id' => 5], $result['query']->conditions());
	}

	public function testMultiRecordUpdate() {
		$result = MockPost::update(
			['published' => false],
			['expires' => ['>=' => '2010-05-13']]
		);
		$query = $result['query'];
		$this->assertEqual('update', $query->type());
		$this->assertEqual(['published' => false], $query->data());
		$this->assertEqual(['expires' => ['>=' => '2010-05-13']], $query->conditions());
	}

	public function testMultiRecordDelete() {
		$result = MockPost::remove(['published' => false]);
		$query = $result['query'];
		$this->assertEqual('delete', $query->type());
		$this->assertEqual(['published' => false], $query->conditions());

		$keys = array_keys(array_filter($query->export(MockPost::connection())));
		$this->assertEqual(['conditions', 'model', 'type', 'source', 'alias'], $keys);
	}

	public function testFindFirst() {
		MockTag::config(['meta' => ['key' => 'id']]);
		$tag = MockTag::find('first', ['conditions' => ['id' => 2]]);
		$tag2 = MockTag::find(2);
		$tag3 = MockTag::first(2);

		$expected = $tag['query']->export(MockTag::connection());
		$this->assertEqual($expected, $tag2['query']->export(MockTag::connection()));
		$this->assertEqual($expected, $tag3['query']->export(MockTag::connection()));

		$tag = MockTag::find('first', [
			'conditions' => ['id' => 2],
			'return' => 'array'
		]);

		$expected['return'] = 'array';
		$this->assertTrue($tag instanceof Query);
		$this->assertEqual($expected, $tag->export(MockTag::connection()));
	}

	/**
	 * Tests that varying `count` syntaxes all produce the same query operation (i.e.
	 * `Model::count(...)`, `Model::find('count', ...)` etc).
	 */
	public function testCountSyntax() {
		$base = MockPost::count(['email' => 'foo@example.com']);
		$query = $base['query'];

		$this->assertEqual('read', $query->type());
		$this->assertEqual('count', $query->calculate());
		$this->assertEqual(['email' => 'foo@example.com'], $query->conditions());

		$result = MockPost::find('count', ['conditions' => [
			'email' => 'foo@example.com'
		]]);
		$this->assertEqual($query, $result['query']);

		$result = MockPost::count(['conditions' => ['email' => 'foo@example.com']]);
		$this->assertEqual($query, $result['query']);
	}

	/**
	 * Test that magic count condition-less syntax works.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/1282
	 */
	public function testCountSyntaxWithoutConditions() {
		$result = MockPost::count([
			'group' => 'name'
		]);
		$this->assertEqual('name', $result['query']->group());
		$this->assertIdentical([], $result['query']->conditions());
	}

	public function testSettingNestedObjectDefaults() {
		$schema = MockPost::schema()->append([
			'nested.value' => ['type' => 'string', 'default' => 'foo']
		]);
		$this->assertEqual('foo', MockPost::create()->nested['value']);

		$data = ['nested' => ['value' => 'bar']];
		$this->assertEqual('bar', MockPost::create($data)->nested['value']);
	}

	/**
	 * Tests that objects can be passed as keys to `Model::find()` and be properly translated to
	 * query conditions.
	 */
	public function testFindByObjectKey() {
		$key = (object) ['foo' => 'bar'];
		$result = MockPost::find($key);
		$this->assertEqual(['id' => $key], $result['query']->conditions());
	}

	public function testLiveConfiguration() {
		MockBadConnection::config(['meta' => ['connection' => false]]);
		$result = MockBadConnection::meta('connection');
		$this->assertFalse($result);
	}

	public function testLazyLoad() {
		$object = MockPost::object();
		$object->belongsTo = ['Unexisting'];
		MockPost::config();
		MockPost::initialize('lithium\tests\mocks\data\MockPost');
		$exception = 'Related model class \'lithium\tests\mocks\data\Unexisting\' not found.';
		$this->assertException($exception, function() {
			MockPost::relations('Unexisting');
		});
	}

	public function testLazyMetadataInit() {
		MockPost::config([
			'schema' => new Schema([
				'fields' => [
					'id' => ['type' => 'integer'],
					'name' => ['type' => 'string'],
					'label' => ['type' => 'string']
				]
			])
		]);

		$this->assertIdentical('mock_posts', MockPost::meta('source'));
		$this->assertIdentical('name', MockPost::meta('title'));
		$this->assertEmpty(MockPost::meta('unexisting'));

		$config = [
			'schema' => new Schema([
				'fields' => [
					'id' => ['type' => 'integer'],
					'name' => ['type' => 'string'],
					'label' => ['type' => 'string']
				]
			]),
			'initializers' => [
				'source' => function($self) {
					return Inflector::tableize($self::meta('name'));
				},
				'name' => function($self) {
					return Inflector::singularize('CoolPosts');
				},
				'title' => function($self) {
					static $i = 1;
					return 'label' . $i++;
				}
			]
		];
		MockPost::reset();
		MockPost::config($config);
		$this->assertIdentical('cool_posts', MockPost::meta('source'));
		$this->assertIdentical('label1', MockPost::meta('title'));
		$this->assertNotIdentical('label2', MockPost::meta('title'));
		$this->assertIdentical('label1', MockPost::meta('title'));
		$meta = MockPost::meta();
		$this->assertIdentical('label1', $meta['title']);
		$this->assertIdentical('CoolPost', MockPost::meta('name'));

		MockPost::reset();
		unset($config['initializers']['title']);
		$config['initializers']['source'] = function($self) {
			return Inflector::underscore($self::meta('name'));
		};
		MockPost::config($config);
		$this->assertIdentical('cool_post', MockPost::meta('source'));
		$this->assertIdentical('name', MockPost::meta('title'));
		$this->assertIdentical('CoolPost', MockPost::meta('name'));

		MockPost::reset();
		MockPost::config($config);
		$expected = [
			'class' => 'lithium\\tests\\mocks\\data\\MockPost',
			'connection' => false,
			'key' => 'id',
			'name' => 'CoolPost',
			'title' => 'name',
			'source' => 'cool_post'
		];
		$this->assertEqual($expected, MockPost::meta());
	}

	public function testHasFinder() {
		$this->assertTrue(MockPost::hasFinder('all'));
		$this->assertTrue(MockPost::hasFinder('count'));

		$this->assertTrue(MockPost::hasFinder('findByFoo'));
		$this->assertTrue(MockPost::hasFinder('findFooByBar'));

		$this->assertTrue(MockPost::hasFinder('fooByBar'));
		$this->assertTrue(MockPost::hasFinder('FooByBar'));

		$this->assertFalse(MockPost::hasFinder('fooBarBaz'));
	}

	public function testFieldName() {
		MockPost::bind('hasMany', 'MockTag');
		$relation = MockPost::relations('MockComment');
		$this->assertEqual('mock_comments', $relation->fieldName());

		$relation = MockPost::relations('MockTag');
		$this->assertEqual('mock_tags', $relation->fieldName());

		$relation = MockComment::relations('MockPost');
		$this->assertEqual('mock_post', $relation->fieldName());
	}

	public function testRelationFromFieldName() {
		MockPost::bind('hasMany', 'MockTag');
		$this->assertEqual('MockComment', MockPost::relations('mock_comments')->name());
		$this->assertEqual('MockTag', MockPost::relations('mock_tags')->name());
		$this->assertEqual('MockPost', MockComment::relations('mock_post')->name());
		$this->assertNull(MockPost::relations('undefined'));
	}

	public function testValidateWithRequiredFalse(){
		$post = MockPost::create([
			'title' => 'post title',
		]);
		$post->validates(['rules' => [
			'title' => 'A custom message here for empty titles.',
			'email' => [
				['notEmpty', 'message' => 'email is empty.', 'required' => false]
			]
		]]);
		$this->assertEmpty($post->errors());
	}

	public function testValidateWithRequiredTrue(){
		$post = MockPost::create([
			'title' => 'post title',
		]);
		$post->sync(1);
		$post->validates(['rules' => [
			'title' => 'A custom message here for empty titles.',
			'email' => [
				['notEmpty', 'message' => 'email is empty.', 'required' => true]
			]
		]]);
		$this->assertNotEmpty($post->errors());
	}

	public function testValidateWithRequiredNull(){
		$validates = [
			'title' => 'A custom message here for empty titles.',
			'email' => [
				['notEmpty', 'message' => 'email is empty.', 'required' => null]
			]
		];

		$post = MockPost::create([
			'title' => 'post title',
		]);

		$post->validates(['rules' => $validates]);
		$this->assertNotEmpty($post->errors());

		$post->sync(1);
		$post->validates(['rules' => $validates]);
		$this->assertEmpty($post->errors());
	}
}

?>