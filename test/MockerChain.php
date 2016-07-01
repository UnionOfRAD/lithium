<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\test;

$message  = 'lithium\test\MockerChain has been deprecated, as alternatives ';
$message .= 'exist (i.e. Mockery) which take the task of maintaining a ';
$message .= 'mocking framework from us.';
trigger_error($message, E_USER_DEPRECATED);

/**
 * Mocker chain is used to aid in assertion of method calls.
 *
 * Asserting if `method1` was not called
 * ```
 * $mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
 * $this->assertFalse(Mocker::chain($mock)->called('method1')->success());
 * ```
 *
 * Asserting if `method1` was called 2 times
 * ```
 * $mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
 * $this->assertTrue(Mocker::chain($mock)->called('method1')->eq(2)->success());
 * ```
 *
 * Asserting if `method2` was called after `method1`
 * ```
 * $mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
 * $this->assertTrue(Mocker::chain($mock)->called('method1')->called('method2')->success());
 * ```
 *
 * Asserting if `method2` was called after `method1`, and `method2` had specific arguments.
 * ```
 * $mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
 * $this->assertTrue(Mocker::chain($mock)
 * 	->called('method1')
 * 	->called('method2')->with('foo', 'bar')
 * 		->success());
 * ```
 * @deprecated Please use an alternative mocking framework, i.e. Mockery.
 */
class MockerChain extends \lithium\core\Object {

	/**
	 * Data to be used in the class.
	 *
	 * `results` Cached mock results
	 * `method` Method we are asserting
	 * `args` Args we are asserting
	 * `success` Success flag
	 * `callTime` Last method call
	 *
	 * @var array
	 */
	protected $_data = [
		'results' => null,
		'method' => false,
		'args' => false,
		'success' => true,
		'callTime' => 0,
	];

	/**
	 * Constructor. Saves the results from the mock.
	 *
	 * @param array $results Results from the mock
	 * @return void
	 */
	public function __construct($results) {
		$this->_data['results'] = $results;
	}

	/**
	 * Validates that a given methodis called a set number of times.
	 *
	 * @param  string $comparison Comparison type 'gt', 'gte', 'lt', 'lte', or 'eq'.
	 * @param  array  $args       The first argument is the expected result.
	 * @return object
	 */
	public function __call($comparison, $args) {
		$methodExists = in_array($comparison, ['gt', 'gte', 'lt', 'lte', 'eq'], true);
		if (!$this->_data['success'] || !$methodExists) {
			return $this;
		}
		if (count($args) === 0 || !is_int($args[0])) {
			$this->_data['success'] = false;
			return $this;
		}
		$result = 0;
		$expected = $args[0];
		$method = $this->_data['method'];
		$args = $this->_data['args'];
		foreach ($this->_data['results'][$method] as $call) {
			$correctTime = $this->_data['callTime'] <= $call['time'];
			$correctArgs = !is_array($args) || $args === $call['args'];
			if ($correctTime && $correctArgs) {
				$this->_data['callTime'] = $call['time'];
				$result++;
			}
		}
		switch ($comparison) {
			case 'gt':
				$this->_data['success'] = $result > $expected;
			break;
			case 'gte':
				$this->_data['success'] = $result >= $expected;
			break;
			case 'lt':
				$this->_data['success'] = $result < $expected;
			break;
			case 'lte':
				$this->_data['success'] = $result <= $expected;
			break;
			case 'eq':
				$this->_data['success'] = $result === $expected;
			break;
		}
		return $this;
	}

	/**
	 * Determines if a given method can be called.
	 *
	 * @param string $method Name of the method.
	 * @param boolean $internal Provide `true` to perform check from inside the
	 *                class/object. When `false` checks also for public visibility;
	 *                defaults to `false`.
	 * @return boolean Returns `true` if the method can be called, `false` otherwise.
	 */
	public function respondsTo($method, $internal = false) {
		$methodExists = in_array($method, ['gt', 'gte', 'lt', 'lte', 'eq'], true);
		return $methodExists || parent::respondsTo($method, $internal);
	}

	/**
	 * Valides the method was called after the last call.
	 *
	 * @param  string $method Method to assert
	 * @return object
	 */
	public function called($method) {
		if (!$this->_data['success']) {
			return $this;
		}

		$this->_data['method'] = $method;
		$this->_data['args'] = false;
		if (!isset($this->_data['results'][$method])) {
			$this->_data['success'] = false;
			return $this;
		}

		foreach ($this->_data['results'][$method] as $call) {
			if ($this->_data['callTime'] < $call['time']) {
				$this->_data['callTime'] = $call['time'];
				return $this;
			}
		}

		$this->_data['success'] = false;
		return $this;
	}

	/**
	 * Will further narrow down the original 'called' method.
	 *
	 * Valides the cached method name was called with these args
	 *
	 * @param  mixed $arg,... Optional arguments to test against
	 * @return object
	 */
	public function with() {
		if (!$this->_data['success']) {
			return $this;
		}

		$method = $this->_data['method'];
		$this->_data['args'] = $args = func_get_args();

		foreach ($this->_data['results'][$method] as $call) {
			$correctTime = $this->_data['callTime'] <= $call['time'];
			$correctArgs = $args === $call['args'];
			if ($correctTime && $correctArgs) {
				$this->_data['callTime'] = $call['time'];
				return $this;
			}
		}

		$this->_data['success'] = false;
		return $this;
	}

	/**
	 * Gives back the success flag
	 *
	 * @return bool
	 */
	public function success() {
		$success = $this->_data['success'];
		$this->_data = [
			'results' => $this->_data['results'],
			'method' => false,
			'args' => false,
			'success' => true,
			'callTime' => 0,
		];
		return $success;
	}

}

?>