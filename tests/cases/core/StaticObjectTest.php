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
use lithium\tests\mocks\core\MockStaticInstantiator;
use lithium\tests\mocks\core\MockStaticObject;

class StaticObjectTest extends \lithium\test\Unit {

	protected $_backup = null;

	public function setUp() {
		error_reporting(($this->_backup = error_reporting()) & ~E_USER_DEPRECATED);
	}

	public function tearDown() {
		error_reporting($this->_backup);
	}

	/**
	 * Tests that the correct parameters are always passed in `StaticObject::invokeMethod()`,
	 * regardless of the number.
	 */
	public function testMethodInvocationWithParameters() {
		$this->assertEqual(MockStaticObject::invokeMethod('foo'), []);
		$this->assertEqual(MockStaticObject::invokeMethod('foo', ['bar']), ['bar']);

		$params = ['one', 'two'];
		$this->assertEqual(MockStaticObject::invokeMethod('foo', $params), $params);

		$params = ['short', 'parameter', 'list'];
		$this->assertEqual(MockStaticObject::invokeMethod('foo', $params), $params);

		$params = ['a', 'longer', 'parameter', 'list'];
		$this->assertEqual(MockStaticObject::invokeMethod('foo', $params), $params);

		$params = ['a', 'much', 'longer', 'parameter', 'list'];
		$this->assertEqual(MockStaticObject::invokeMethod('foo', $params), $params);

		$params = ['an', 'extremely', 'long', 'list', 'of', 'parameters'];
		$this->assertEqual(MockStaticObject::invokeMethod('foo', $params), $params);

		$params = ['an', 'extremely', 'long', 'list', 'of', 'parameters'];
		$this->assertEqual(MockStaticObject::invokeMethod('foo', $params), $params);

		$params = [
			'if', 'you', 'have', 'a', 'parameter', 'list', 'this',
			'long', 'then', 'UR', 'DOIN', 'IT', 'RONG'
		];
		$this->assertEqual(MockStaticObject::invokeMethod('foo', $params), $params);
	}

	public function testInstanceWithClassesKey() {
		$expected = 'lithium\tests\mocks\core\MockRequest';
		$result = get_class(MockStaticInstantiator::instance('request'));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceWithNamespacedClass() {
		$expected = 'lithium\tests\mocks\core\MockRequest';
		$result = get_class(MockStaticInstantiator::instance(
			'lithium\tests\mocks\core\MockRequest'
		));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceWithObject() {
		$request = new MockRequest();
		$expected = 'lithium\tests\mocks\core\MockRequest';
		$result = get_class(MockStaticInstantiator::instance($request));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceFalse() {
		$this->assertException('/^Invalid class lookup/', function() {
			MockStaticInstantiator::instance(false);
		});
	}

	public function testRespondsTo() {
		$this->assertTrue(MockStaticInstantiator::respondsTo('invokeMethod'));
		$this->assertFalse(MockStaticInstantiator::respondsTo('fooBarBaz'));
	}

	public function testRespondsToProtectedMethod() {
		$this->assertFalse(MockStaticInstantiator::respondsTo('_foo'));
		$this->assertTrue(MockStaticInstantiator::respondsTo('_foo', 1));
	}

	/* Deprecated / BC */

	public function testClassParents() {
		$class = 'lithium\tests\mocks\core\MockStaticObject';
		$class::parents(null);

		$result = $class::parents();
		$expected = ['lithium\core\StaticObject' => 'lithium\core\StaticObject'];
		$this->assertEqual($expected, $result);

		$cache = $class::parents(true);
		$this->assertEqual([$class => $expected], $cache);
	}
}

?>