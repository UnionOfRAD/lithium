<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use lithium\data\Entity;

class EntityTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\source\MockMongoPost';

	public function testSchemaAccess() {
		$schema = array('foo' => array('type' => 'string'));
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
		$entity = new Entity(array('model' => $model, 'data' => array('foo' => true)));
		$this->assertTrue($entity->validates());

		$model::instanceMethods(array(
			'testInstanceMethod' => function($entity) { return 'testInstanceMethod'; }
		));
		$this->assertEqual('testInstanceMethod', $entity->testInstanceMethod($entity));

		$this->expectException("/^No model bound or unhandled method call `foo`.$/");
		$entity->foo();
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
}

?>