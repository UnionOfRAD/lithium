<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use \lithium\core\Object;
use \lithium\tests\mocks\core\MockMethodFiltering;
use \lithium\tests\mocks\core\MockExposed;
use \lithium\tests\mocks\core\MockCallable;
use \lithium\tests\mocks\core\MockObjectForParents;

class ObjectTest extends \lithium\test\Unit {

	public function testMethodFiltering() {
		$test = new MockMethodFiltering();
		$result = $test->method(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Inside method implementation',
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);

		$test->applyFilter('method', function($self, $params, $chain) {
			$params['data'][] = 'Starting filter';
			$result = $chain->next($self, $params, $chain);
			$result[] = 'Ending filter';
			return $result;
		});

		$result = $test->method(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			'Inside method implementation',
			'Ending filter',
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);

		$test->applyFilter('method', function($self, $params, $chain) {
			$params['data'][] = 'Starting inner filter';
			$result = $chain->next($self, $params, $chain);
			$result[] = 'Ending inner filter';
			return $result;
		});
		$result = $test->method(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			'Starting inner filter',
			'Inside method implementation',
			'Ending inner filter',
			'Ending filter',
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Verifies workaround for accessing protected properties in filtered methods.
	 *
	 * @return void
	 */
	function testFilteringWithProtectedAccess() {
		$object = new MockExposed();
		$this->assertEqual($object->get(), 'secret');
		$this->assertTrue($object->tamper());
		$this->assertEqual($object->get(), 'tampered');
	}

	/**
	 * Attaches a single filter to multiple methods.
	 *
	 * @return void
	 */
	function testMultipleMethodFiltering() {
		$object = new MockMethodFiltering();
		$this->assertIdentical($object->method2(), array());

		$object->applyFilter(array('method', 'method2'), function($self, $params, $chain) {
			return $chain->next($self, $params, $chain);
		});
		$this->assertIdentical(array_keys($object->method2()), array('method', 'method2'));
	}

	/**
	 * Tests that the correct parameters are always passed in Object::invokeMethod(), regardless of
	 * the number.
	 *
	 * @return void
	 */
	public function testMethodInvokationWithParameters() {
		$callable = new MockCallable();

		$this->assertEqual($callable->invokeMethod('foo'), array());
		$this->assertEqual($callable->invokeMethod('foo', array('bar')), array('bar'));

		$params = array('one', 'two');
		$this->assertEqual($callable->invokeMethod('foo', $params), $params);

		$params = array('short', 'parameter', 'list');
		$this->assertEqual($callable->invokeMethod('foo', $params), $params);

		$params = array('a', 'longer', 'parameter', 'list');
		$this->assertEqual($callable->invokeMethod('foo', $params), $params);

		$params = array('a', 'much', 'longer', 'parameter', 'list');
		$this->assertEqual($callable->invokeMethod('foo', $params), $params);

		$params = array('an', 'extremely', 'long', 'list', 'of', 'parameters');
		$this->assertEqual($callable->invokeMethod('foo', $params), $params);

		$params = array('an', 'extremely', 'long', 'list', 'of', 'parameters');
		$this->assertEqual($callable->invokeMethod('foo', $params), $params);

		$params = array(
			'if', 'you', 'have', 'a', 'parameter', 'list', 'this',
			'long', 'then', 'UR', 'DOIN', 'IT', 'RONG'
		);
		$this->assertEqual($callable->invokeMethod('foo', $params), $params);
	}

	public function testParents() {
		$expected = array('lithium\\core\\Object' => 'lithium\\core\\Object');

		$result = MockObjectForParents::parents();
		$this->assertEqual($expected, $result);

		// For caching
		$result = MockObjectForParents::parents();
		$this->assertEqual($expected, $result);
	}
}

?>
