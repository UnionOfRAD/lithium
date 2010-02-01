<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use \lithium\core\StaticObject;

class StaticObjectTest extends \lithium\test\Unit {

	public function testMethodFiltering() {
		$class = 'lithium\tests\mocks\core\MockStaticMethodFiltering';

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
		$class = '\lithium\tests\mocks\core\MockStaticMethodFiltering';

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
		$class = 'lithium\tests\mocks\core\MockStaticMethodFiltering';
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
		$class = '\lithium\tests\mocks\core\MockStaticFilteringExtended';
		$this->assertEqual('Working', $class::callSubclassMethod());
	}
}

?>