<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\core;

use lithium\aop\Filters;
use lithium\tests\mocks\core\MockRequest;
use lithium\tests\mocks\core\MockStaticInstantiator;
use lithium\tests\mocks\core\MockStaticObject;

class StaticObjectTest extends \lithium\test\Unit {

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

	public function testClassParents() {
		$class = 'lithium\tests\mocks\core\MockStaticObject';
		$class::parents(null);

		$result = $class::parents();
		$expected = ['lithium\core\StaticObject' => 'lithium\core\StaticObject'];
		$this->assertEqual($expected, $result);

		$cache = $class::parents(true);
		$this->assertEqual([$class => $expected], $cache);
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
		$this->assertTrue(MockStaticInstantiator::respondsTo('applyFilter'));
		$this->assertFalse(MockStaticInstantiator::respondsTo('fooBarBaz'));
	}

	public function testRespondsToProtectedMethod() {
		$this->assertFalse(MockStaticInstantiator::respondsTo('_foo'));
		$this->assertTrue(MockStaticInstantiator::respondsTo('_foo', 1));
	}

	/* Deprecated / BC */

	public function testMethodFiltering() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$class = 'lithium\tests\mocks\core\MockStaticMethodFiltering';

		$result = $class::method(['Starting test']);
		$expected = [
			'Starting test',
			'Starting outer method call',
			'Inside method implementation of ' . $class,
			'Ending outer method call'
		];
		$this->assertEqual($expected, $result);

		$class::applyFilter('method', function($self, $params, $chain) {
			$params['data'][] = 'Starting filter';
			$result = $chain->next($self, $params, $chain);
			$result[] = 'Ending filter';
			return $result;
		});

		$result = $class::method(['Starting test']);
		$expected = [
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			'Inside method implementation of ' . $class,
			'Ending filter',
			'Ending outer method call'
		];
		$this->assertEqual($expected, $result);

		$class::applyFilter('method', function($self, $params, $chain) {
			$params['data'][] = 'Starting inner filter';
			$result = $chain->next($self, $params, $chain);
			$result[] = 'Ending inner filter';
			return $result;
		});
		$result = $class::method(['Starting test']);
		$expected = [
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			'Starting inner filter',
			'Inside method implementation of ' . $class,
			'Ending inner filter',
			'Ending filter',
			'Ending outer method call'
		];
		$this->assertEqual($expected, $result);

		Filters::clear('lithium\tests\mocks\core\MockStaticMethodFiltering');
		error_reporting($original);
	}

	/**
	 * Tests that calling a filter-able method with no filters added does not trigger an error.
	 */
	public function testCallingUnfilteredMethods() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$class = 'lithium\tests\mocks\core\MockStaticMethodFiltering';
		$result = $class::manual([function($self, $params, $chain) {
			return '-' . $chain->next($self, $params, $chain) . '-';
		}]);
		$expected = '-Working-';
		$this->assertEqual($expected, $result);

		Filters::clear('lithium\tests\mocks\core\MockStaticMethodFiltering');
		error_reporting($original);
	}

	/**
	 * Tests that filtered methods in parent classes can call methods in subclasses.
	 */
	public function testCallingSubclassMethodsInFilteredMethods() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$class = 'lithium\tests\mocks\core\MockStaticFilteringExtended';
		$this->assertEqual('Working', $class::callSubclassMethod());

		error_reporting($original);
	}

	public function testResetMethodFilter() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$class = 'lithium\tests\mocks\core\MockStaticMethodFiltering';
		$class::applyFilter(false);
		$class::applyFilter('method2', function($self, $params, $chain) {
			return false;
		});

		$this->assertFalse($class::method2());

		$class::applyFilter('method2', false);

		$this->assertNotIdentical($class::method2(), false);

		Filters::clear('lithium\tests\mocks\core\MockStaticMethodFiltering');
		error_reporting($original);
	}

	public function testResetMultipleFilters() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$class = 'lithium\tests\mocks\core\MockStaticMethodFiltering';
		$class::applyFilter(false);
		$class::applyFilter(['method2', 'manual'], function($self, $params, $chain) {
			return false;
		});

		$this->assertFalse($class::method2());
		$this->assertFalse($class::manual([]));

		$class::applyFilter('method2', false);

		$this->assertNotIdentical($class::method2(), false);
		$this->assertFalse($class::manual([]));

		Filters::clear('lithium\tests\mocks\core\MockStaticMethodFiltering');
		error_reporting($original);
	}

	public function testResetFiltersInClass() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$class = 'lithium\tests\mocks\core\MockStaticMethodFiltering';
		$class::applyFilter(false);
		$class::applyFilter(['method2', 'manual'], function($self, $params, $chain) {
			return false;
		});

		$this->assertFalse($class::method2());
		$this->assertFalse($class::manual([]));

		$class::applyFilter(false);

		$this->assertNotIdentical($class::method2(), false);
		$this->assertNotIdentical($class::manual([]), false);

		Filters::clear('lithium\tests\mocks\core\MockStaticMethodFiltering');
		error_reporting($original);
	}
}

?>