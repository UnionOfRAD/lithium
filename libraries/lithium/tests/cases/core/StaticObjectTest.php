<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use \lithium\core\StaticObject;

class TestMethodFilteringStatic extends \lithium\core\StaticObject {

	public static function method($data) {
		$data[] = 'Starting outer method call';
		$result = static::_filter(__METHOD__, compact('data'), function($self, $params, $chain) {
			$params['data'][] = 'Inside method implementation of ' . $self;
			return $params['data'];
		});
		$result[] = 'Ending outer method call';
		return $result;
	}

	public static function method2() {
		$filters =& static::$_methodFilters;
		$method = function($self, $params, $chain) use (&$filters) {
			return $filters;
		};
		return static::_filter(__METHOD__, array(), $method);
	}

	public static function foo() {
		$args = func_get_args();
		return $args;
	}
}

class StaticObjectTest extends \lithium\test\Unit {

	public function testMethodFiltering() {
		$class = __NAMESPACE__ . '\TestMethodFilteringStatic';

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
	 * Tests that the correct parameters are always passed in StaticObject::invokeMethod(),
	 * regardless of the number.
	 *
	 * @return void
	 */
	public function testMethodInvokationWithParameters() {
		$class = __NAMESPACE__ . '\TestMethodFilteringStatic';

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
}

?>