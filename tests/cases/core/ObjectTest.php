<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\core;

use lithium\aop\Filters;
use lithium\tests\mocks\core\MockRequest;
use lithium\tests\mocks\core\MockMethodFiltering;
use lithium\tests\mocks\core\MockExposed;
use lithium\tests\mocks\core\MockCallable;
use lithium\tests\mocks\core\MockObjectForParents;
use lithium\tests\mocks\core\MockObjectConfiguration;
use lithium\tests\mocks\core\MockInstantiator;

class ObjectTest extends \lithium\test\Unit {

	protected $_backup = null;

	public function setUp() {
		error_reporting(($this->_backup = error_reporting()) & ~E_USER_DEPRECATED);
	}

	public function tearDown() {
		error_reporting($this->_backup);
	}

	/**
	 * Tests that the correct parameters are always passed in Object::invokeMethod(), regardless of
	 * the number.
	 */
	public function testMethodInvocationWithParameters() {
		$callable = new MockCallable();

		$result = $callable->invokeMethod('foo');
		$this->assertEqual($result['method'], 'foo');
		$this->assertEqual($result['params'], []);

		$expected = ['bar'];
		$result = $callable->invokeMethod('foo', $expected);
		$this->assertEqual($result['method'], 'foo');
		$this->assertEqual($result['params'], $expected);

		$expected = ['one', 'two'];
		$result = $callable->invokeMethod('foo', $expected);
		$this->assertEqual($result['method'], 'foo');
		$this->assertEqual($result['params'], $expected);

		$expected = ['short', 'parameter', 'list'];
		$result = $callable->invokeMethod('foo', $expected);
		$this->assertEqual($result['method'], 'foo');
		$this->assertEqual($result['params'], $expected);

		$expected = ['a', 'longer', 'parameter', 'list'];
		$result = $callable->invokeMethod('foo', $expected);
		$this->assertEqual($result['method'], 'foo');
		$this->assertEqual($result['params'], $expected);

		$expected = ['a', 'much', 'longer', 'parameter', 'list'];
		$result = $callable->invokeMethod('foo', $expected);
		$this->assertEqual($result['method'], 'foo');
		$this->assertEqual($result['params'], $expected);

		$expected = ['an', 'extremely', 'long', 'list', 'of', 'parameters'];
		$result = $callable->invokeMethod('foo', $expected);
		$this->assertEqual($result['method'], 'foo');
		$this->assertEqual($result['params'], $expected);

		$expected = ['an', 'extremely', 'long', 'list', 'of', 'parameters'];
		$result = $callable->invokeMethod('bar', $expected);
		$this->assertEqual($result['method'], 'bar');
		$this->assertEqual($result['params'], $expected);

		$expected = [
			'if', 'you', 'have', 'a', 'parameter', 'list', 'this',
			'long', 'then', 'UR', 'DOIN', 'IT', 'RONG'
		];
		$result = $callable->invokeMethod('foo', $expected);
		$this->assertEqual($result['method'], 'foo');
		$this->assertEqual($result['params'], $expected);
	}

	/**
	 * Test configuration handling
	 */
	public function testObjectConfiguration() {
		$expected = ['testScalar' => 'default', 'testArray' => ['default']];
		$config = new MockObjectConfiguration();
		$this->assertEqual($expected, $config->getConfig());

		$config = new MockObjectConfiguration(['autoConfig' => ['testInvalid']]);
		$this->assertEqual($expected, $config->getConfig());

		$expected = ['testScalar' => 'override', 'testArray' => ['default', 'override']];
		$config = new MockObjectConfiguration(['autoConfig' => [
			'testScalar', 'testArray' => 'merge'
		]] + $expected);
		$this->assertEqual($expected, $config->getConfig());
	}

	public function testInstanceWithClassesKey() {
		$object = new MockInstantiator();
		$expected = 'lithium\tests\mocks\core\MockRequest';
		$result = get_class($object->instance('request'));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceWithNamespacedClass() {
		$object = new MockInstantiator();
		$expected = 'lithium\tests\mocks\core\MockRequest';
		$result = get_class($object->instance('lithium\tests\mocks\core\MockRequest'));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceWithObject() {
		$object = new MockInstantiator();
		$request = new MockRequest();
		$expected = 'lithium\tests\mocks\core\MockRequest';
		$result = get_class($object->instance($request));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceFalse() {
		$object = new MockInstantiator();
		$this->assertException('/^Invalid class lookup/', function() use ($object) {
			$object->instance(false);
		});
	}

	public function testRespondsTo() {
		$obj = new MockRequest();
		$this->assertTrue($this->respondsTo('get'));
		$this->assertFalse($this->respondsTo('fooBarBaz'));
	}

	public function testRespondsToProtectedMethod() {
		$obj = new MockRequest();
		$this->assertFalse($obj->respondsTo('_parents'));
		$this->assertTrue($obj->respondsTo('_parents', 1));
	}

	/* Deprecated / BC */

	public function testParents() {
		$expected = ['lithium\core\Object' => 'lithium\core\Object'];

		$result = MockObjectForParents::parents();
		$this->assertEqual($expected, $result);

		$result = MockObjectForParents::parents();
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that an object can be instantiated using the magic `__set_state()` method.
	 *
	 * @deprecated
	 */
	public function testStateBasedInstantiation() {
		$result = MockObjectConfiguration::__set_state([
			'key' => 'value', '_protected' => 'test'
		]);
		$expected = 'lithium\tests\mocks\core\MockObjectConfiguration';
		$this->assertEqual($expected, get_class($result));

		$this->assertEqual('test', $result->getProtected());
	}
}

?>