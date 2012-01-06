<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Core;

use Lithium\Core\StaticObject;
use Lithium\Tests\Mocks\Core\MockRequest;
use Lithium\Tests\Mocks\Core\MockStaticInstantiator;

class StaticObjectTest extends \Lithium\Test\Unit {

	public function testMethodFiltering() {
		$class = 'Lithium\Tests\Mocks\Core\MockStaticMethodFiltering';

		$result = $class::method(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Inside method implementation of ' . $class,
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);

		$class::applyFilter('method', function($self, $params, $chain) {
			$params['data'][] = 'Starting filter';
			$result = $chain->next($self, $params, $chain);
			$result[] = 'Ending filter';
			return $result;
		});

		$result = $class::method(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			'Inside method implementation of ' . $class,
			'Ending filter',
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);

		$class::applyFilter('method', function($self, $params, $chain) {
			$params['data'][] = 'Starting inner filter';
			$result = $chain->next($self, $params, $chain);
			$result[] = 'Ending inner filter';
			return $result;
		});
		$result = $class::method(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			'Starting inner filter',
			'Inside method implementation of ' . $class,
			'Ending inner filter',
			'Ending filter',
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that the correct parameters are always passed in `StaticObject::invokeMethod()`,
	 * regardless of the number.
	 *
	 * @return void
	 */
	public function testMethodInvocationWithParameters() {
		$class = '\Lithium\Tests\Mocks\Core\MockStaticMethodFiltering';

		$this->assertEqual($class::invokeMethod('foo'), array());
		$this->assertEqual($class::invokeMethod('foo', array('bar')), array('bar'));

		$params = array('one', 'two');
		$this->assertEqual($class::invokeMethod('foo', $params), $params);

		$params = array('short', 'parameter', 'list');
		$this->assertEqual($class::invokeMethod('foo', $params), $params);

		$params = array('a', 'longer', 'parameter', 'list');
		$this->assertEqual($class::invokeMethod('foo', $params), $params);

		$params = array('a', 'much', 'longer', 'parameter', 'list');
		$this->assertEqual($class::invokeMethod('foo', $params), $params);

		$params = array('an', 'extremely', 'long', 'list', 'of', 'parameters');
		$this->assertEqual($class::invokeMethod('foo', $params), $params);

		$params = array('an', 'extremely', 'long', 'list', 'of', 'parameters');
		$this->assertEqual($class::invokeMethod('foo', $params), $params);

		$params = array(
			'if', 'you', 'have', 'a', 'parameter', 'list', 'this',
			'long', 'then', 'UR', 'DOIN', 'IT', 'RONG'
		);
		$this->assertEqual($class::invokeMethod('foo', $params), $params);
	}

	/**
	 * Tests that calling a filter-able method with no filters added does not trigger an error.
	 *
	 * @return void
	 */
	public function testCallingUnfilteredMethods() {
		$class = 'Lithium\Tests\Mocks\Core\MockStaticMethodFiltering';
		$result = $class::manual(array(function($self, $params, $chain) {
			return '-' . $chain->next($self, $params, $chain) . '-';
		}));
		$expected = '-Working-';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that filtered methods in parent classes can call methods in subclasses.
	 *
	 * @return void
	 */
	public function testCallingSubclassMethodsInFilteredMethods() {
		$class = 'Lithium\Tests\Mocks\Core\MockStaticFilteringExtended';
		$this->assertEqual('Working', $class::callSubclassMethod());
	}

	public function testClassParents() {
		$class = 'Lithium\Tests\Mocks\Core\MockStaticMethodFiltering';
		$class::parents(null);

		$result = $class::parents();
		$expected = array('Lithium\Core\StaticObject' => 'Lithium\Core\StaticObject');
		$this->assertEqual($expected, $result);

		$cache = $class::parents(true);
		$this->assertEqual(array($class => $expected), $cache);
	}

	public function testInstanceWithClassesKey() {
		$expected = 'Lithium\Tests\Mocks\Core\MockRequest';
		$result = get_class(MockStaticInstantiator::instance('request'));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceWithNamespacedClass() {
		$expected = 'Lithium\Tests\Mocks\Core\MockRequest';
		$result = get_class(MockStaticInstantiator::instance(
			'Lithium\Tests\Mocks\Core\MockRequest'
		));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceWithObject() {
		$request = new MockRequest();
		$expected = 'Lithium\Tests\Mocks\Core\MockRequest';
		$result = get_class(MockStaticInstantiator::instance($request));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceFalse() {
		$this->expectException('/^Invalid class lookup/');
		MockStaticInstantiator::instance(false);
	}
}

?>