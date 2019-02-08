<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data;

use lithium\data\Entity;
use lithium\data\Schema;

class EntityTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\MockPost';

	public function testSchemaAccess() {
		$fields = ['foo' => ['type' => 'string']];
		$schema = new Schema(compact('fields'));
		$entity = new Entity(compact('schema'));
		$this->assertEqual($schema, $entity->schema());
	}

	public function testPropertyAccess() {
		$entity = new Entity([
			'model' => 'lithium\tests\mocks\data\MockPost',
			'exists' => false
		]);
		$this->assertEqual('lithium\tests\mocks\data\MockPost', $entity->model());
		$this->assertFalse($entity->exists());

		$entity = new Entity(['exists' => true]);
		$this->assertTrue($entity->exists());

		$expected = [
			'exists' => true, 'data' => [], 'update' => [], 'increment' => []
		];
		$this->assertEqual($expected, $entity->export());
	}

	public function testPropertyIssetEmpty() {
		$entity = new Entity([
			'model' => 'lithium\tests\mocks\data\MockPost',
			'exists' => true,
			'data' => ['test_field' => 'foo'],
			'relationships' => ['test_relationship' => ['test_me' => 'bar']]
		]);

		$this->assertEqual('foo', $entity->test_field);
		$this->assertEqual(['test_me' => 'bar'], $entity->test_relationship);

		$this->assertFalse(isset($entity->field));
		$this->assertTrue(isset($entity->test_relationship));

		$this->assertNotEmpty($entity->test_field);
		$this->assertNotEmpty($entity->test_relationship);

		$this->assertEmpty($entity->test_invisible_field);
		$this->assertEmpty($entity->test_invisible_relationship);
	}

	public function testIncrement() {
		$entity = new Entity(['data' => ['counter' => 0]]);
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

		$this->assertException("/^Field `'bar'` cannot be incremented.$/", function() use ($entity) {
			$entity->increment('bar');
		});
	}

	public function testMethodDispatch() {
		$model = $this->_model;
		$data = ['foo' => true];

		$entity = new Entity(compact('model', 'data'));
		$this->assertTrue($entity->validates());

		$model::instanceMethods(['testInstanceMethod' => function($entity) {
			return 'testInstanceMethod';
		}]);
		$this->assertEqual('testInstanceMethod', $entity->testInstanceMethod($entity));

		$this->assertException("/^Unhandled method call `foo`.$/", function() use ($entity) {
			$entity->foo();
		});
	}

	public function testMethodDispatchWithNoModel() {
		$data = ['foo' => true];
		$entity = new Entity(compact('data'));
		$this->assertException("/^No model bound to call `foo`.$/", function() use ($entity) {
			$entity->foo();
		});
	}

	public function testMethodDispatchWithEntityAsModel() {
		$data = ['foo' => true];
		$model = 'lithium\data\Entity';
		$entity = new Entity(compact('model', 'data'));
		$this->assertException("/^No model bound to call `foo`.$/", function() use ($entity) {
			$entity->foo();
		});
	}

	public function testErrors() {
		$entity = new Entity();
		$errors = ['foo' => 'Something bad happened.'];
		$this->assertEqual([], $entity->errors());

		$entity->errors($errors);
		$this->assertEqual($errors, $entity->errors());
		$this->assertEqual('Something bad happened.', $entity->errors('foo'));

		$otherError = ['bar' => 'Something really bad happened.'];
		$errors += $otherError;
		$entity->errors($otherError);
		$this->assertEqual($errors, $entity->errors());

		$this->assertCount(2, $entity->errors());
		$this->assertEqual('Something bad happened.', $entity->errors('foo'));
		$this->assertEqual('Something really bad happened.', $entity->errors('bar'));
	}

	public function testResetErrors() {
		$entity = new Entity();
		$errors = [
			'foo' => 'Something bad happened.',
			'bar' => 'Something really bad happened.'
		];

		$entity->errors($errors);
		$this->assertEqual($errors, $entity->errors());

		$entity->errors(false);
		$this->assertEmpty($entity->errors());
	}

	public function testAppendingErrors() {
		$entity = new Entity();
		$expected = [
			'Something bad happened.',
			'Something really bad happened.'
		];

		$entity->errors('foo', $expected[0]);
		$entity->errors('foo', $expected[1]);

		$this->assertCount(1, $entity->errors());
		$this->assertEqual($expected, $entity->errors('foo'));
	}

	public function testAppendingErrorsWithArraySyntax() {
		$entity = new Entity();
		$expected = [
			'Something bad happened.',
			'Something really bad happened.'
		];

		$entity->errors(['foo' => $expected[0]]);
		$entity->errors(['foo' => $expected[1]]);

		$this->assertCount(1, $entity->errors());
		$this->assertEqual($expected, $entity->errors('foo'));
	}

	public function testAppendingErrorsWithMixedSyntax() {
		$entity = new Entity();
		$expected = [
			'Something bad happened.',
			'Something really bad happened.'
		];

		$entity->errors('foo', $expected[0]);
		$entity->errors(['foo' => $expected[1]]);

		$this->assertCount(1, $entity->errors());
		$this->assertEqual($expected, $entity->errors('foo'));
	}

	public function testConversion() {
		$data = ['foo' => '!!', 'bar' => '??', 'baz' => '--'];
		$entity = new Entity(compact('data'));

		$this->assertEqual($data, $entity->to('array'));
		$this->assertEqual($data, $entity->data());
		$this->assertEqual($entity, $entity->to('foo'));
	}

	public function testModified() {
		$entity = new Entity();

		$this->assertEqual([], $entity->modified());

		$data = ['foo' => 'bar', 'baz' => 'dib'];
		$entity->set($data);
		$this->assertEqual(['foo' => true, 'baz' => true], $entity->modified());

		$this->assertTrue($entity->modified('foo'));
		$this->assertTrue($entity->modified('baz'));

		/* and last, checking a non-existing field */
		$this->assertNull($entity->modified('ole'));

		$subentity = new Entity();
		$subentity->set($data);
		$entity->set(['ble' => $subentity]);
		$this->assertEqual(['foo' => true, 'baz' => true, 'ble' => true], $entity->modified());

		$this->assertTrue($entity->ble->modified('foo'));
		$this->assertEmpty($entity->ble->modified('iak'));
		$this->assertEqual($entity->ble->modified(), ['foo' => true, 'baz' => true]);

		$data = ['foo' => 'bar', 'baz' => 'dib']; //it's the default data array in the test
		$entity = new Entity();
		$entity->set($data);
		$entity->sync();

		/* Checking empty values */
		$entity->foo = '';
		$this->assertTrue($entity->modified('foo'));
		$this->assertEqual(['foo' => true, 'baz' => false], $entity->modified());

		/* and checking null values */
		$entity->sync();
		$entity->foo = null;
		$this->assertTrue($entity->modified('foo'));
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

	public function testRespondsTo() {
		$model = $this->_model;
		$data = ['foo' => true];
		$entity = new Entity(compact('model', 'data'));

		$this->assertTrue($entity->respondsTo('foobar'));
		$this->assertTrue($entity->respondsTo('findByFoo'));
		$this->assertFalse($entity->respondsTo('barbaz'));
		$this->assertTrue($entity->respondsTo('model'));
		$this->assertTrue($entity->respondsTo('instances'));
	}

	public function testRespondsToParentCall() {
		$model = $this->_model;
		$data = ['foo' => true];
		$entity = new Entity(compact('model', 'data'));

		$this->assertTrue($entity->respondsTo('invokeMethod'));
		$this->assertFalse($entity->respondsTo('fooBarBaz'));
	}

	public function testHandlers() {
		$handlers = [
			'stdClass' => function($value) { return substr($value->scalar, -1); }
		];
		$array = new Entity(compact('handlers') + [
			'data' => [
				'value' => (object) 'hello'
			]
		]);

		$expected = ['value' => 'o'];
		$this->assertIdentical($expected, $array->to('array', ['indexed' => false]));
	}
}

?>