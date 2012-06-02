<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use lithium\data\Entity;
use lithium\data\Schema;

class EntityTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\MockPost';

	public function testSchemaAccess() {
		$fields = array('foo' => array('type' => 'string'));
		$schema = new Schema(compact('fields'));
		$entity = new Entity(compact('schema'));
		$this->assertEqual($schema, $entity->schema());
	}

	public function testPropertyAccess() {
		$entity = new Entity(array('model' => 'Foo', 'exists' => false));
		$this->assertEqual('Foo', $entity->model());
		$this->assertFalse($entity->exists());

		$entity = new Entity(array('exists' => true));
		$this->assertTrue($entity->exists());

		$expected = array(
			'exists' => true, 'data' => array(), 'update' => array(), 'increment' => array()
		);
		$this->assertEqual($expected, $entity->export());
	}

	public function testPropertyIssetEmpty() {
		$entity = new Entity(array(
			'model' => 'Foo',
			'exists' => true,
			'data' => array('test_field' => 'foo'),
			'relationships' => array('test_relationship' => array('test_me' => 'bar'))
		));

		$this->assertEqual('foo', $entity->test_field);
		$this->assertEqual(array('test_me' => 'bar'), $entity->test_relationship);

		$this->assertTrue(isset($entity->test_field));
		$this->assertTrue(isset($entity->test_relationship));

		$this->assertFalse(empty($entity->test_field));
		$this->assertFalse(empty($entity->test_relationship));

		$this->assertTrue(empty($entity->test_invisible_field));
		$this->assertTrue(empty($entity->test_invisible_relationship));
	}

	public function testIncrement() {
		$entity = new Entity(array('data' => array('counter' => 0)));
		$this->assertEqual(0, $entity->counter);

		$entity->increment('counter');
		$this->assertEqual(1, $entity->counter);

		$entity->decrement('counter', 5);
		$this->assertEqual(-4, $entity->counter);

		$this->assertNull($entity->increment);
		$entity->increment('foo');
		$this->assertEqual(1, $entity->foo);

		$this->assertFalse(isset($entity->bar));
		$entity->bar = 'blah';
		$entity->sync();

		$this->expectException("/^Field 'bar' cannot be incremented.$/");
		$entity->increment('bar');
	}

	public function testMethodDispatch() {
		$model = $this->_model;
		$data = array('foo' => true);

		$entity = new Entity(compact('model', 'data'));
		$this->assertTrue($entity->validates());

		$model::instanceMethods(array('testInstanceMethod' => function($entity) {
			return 'testInstanceMethod';
		}));
		$this->assertEqual('testInstanceMethod', $entity->testInstanceMethod($entity));

		$this->expectException("/^No model bound or unhandled method call `foo`.$/");
		$entity->foo();
	}

	public function testMagicMethodDispatch() {
		$model = $this->_model;
		$data = array('foo' => true);
		$entity = new Entity(compact('model', 'data'));

		$this->assertTrue($model::isStatic('relations'));
		$this->assertTrue($model::isStatic('hasField'));
		$this->assertTrue($model::isStatic('key'));
		$this->assertTrue($model::isStatic('meta'));

		$this->assertFalse($model::isStatic('save'));
		$this->assertFalse($model::isStatic('delete'));
		$this->assertFalse($model::isStatic('validates'));
		$this->assertFalse($model::isStatic('title'));

		$this->assertTrue($entity->isStatic('relations'));
		$this->assertFalse($entity->isStatic('save'));

		$result = $model::relations();
		$this->assertTrue(isset($result['MockComment']));

		$result = $entity->relations();
		$this->assertTrue(isset($result['MockComment']));

		$result = $model::key();
		$this->assertEqual('id', $result);

		$result = $entity->key();
		$this->assertEqual('id', $result);

		$result = $entity->staticMethod('bob');
		$this->assertEqual('bob', $result[0]);
		$this->assertFalse(isset($result[1]));

		$result = $entity->instanceMethod('bob');
		$this->assertTrue($result[0] instanceof Entity);
		$this->assertEqual('bob', $result[1]);
		$this->assertFalse(isset($result[2]));

		$this->assertTrue($model::isStatic('staticMethod'));
		$this->assertFalse($model::isStatic('instanceMethod'));

		$parent = 'lithium\tests\mocks\data\MockBase';
		$this->assertNull($parent::isStatic('staticMethod'));
		$this->assertNull($parent::isStatic('instanceMethod'));
	}

	public function testErrors() {
		$entity = new Entity();
		$errors = array('foo' => 'Something bad happened.');
		$this->assertEqual(array(), $entity->errors());

		$entity->errors($errors);
		$this->assertEqual($errors, $entity->errors());
		$this->assertEqual('Something bad happened.', $entity->errors('foo'));
	}

	public function testConversion() {
		$data = array('foo' => '!!', 'bar' => '??', 'baz' => '--');
		$entity = new Entity(compact('data'));

		$this->assertEqual($data, $entity->to('array'));
		$this->assertEqual($data, $entity->data());
		$this->assertEqual($entity, $entity->to('foo'));
	}

	public function testModified() {
		$entity = new Entity();

		$this->assertEqual(array(), $entity->modified());

		$data = array('foo' => 'bar', 'baz' => 'dib');
		$entity->set($data);
		$this->assertEqual(array('foo' => true, 'baz' => true), $entity->modified());
	}

	/**
	 * Tests that an entity can be cast to a string based on its bound model's meta data.
	 */
	public function testStringCasting() {
		$model = $this->_model;
		$old = $model::meta('title') ?: 'title';

		$model::meta('title', 'firstName');
		$object = new Entity(compact('model'));

		$object->firstName = 'Bob';
		$this->assertEqual('Bob', (string) $object);

		$object->firstName = 'Rob';
		$this->assertEqual('Rob', (string) $object);

		$model::meta('title', $old);
	}
}

?>
