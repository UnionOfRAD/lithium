<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\console;

/**
 * This is the Mock Command
 *
 */
class MockCommand extends \lithium\console\Command {

	public $case = null;

	public $face = true;

	/**
	 * Mace.
	 *
	 * @var string Describe value of mace.
	 */
	public $mace = 'test';

	public $race;

	/**
	 * Lace.
	 *
	 * @var boolean Describe value of lace.
	 */
	public $lace = true;

	protected $_dontShow = null;

	protected $_classes = [
		'response' => 'lithium\tests\mocks\console\MockResponse'
	];

	public function testRun() {
		$this->response->testAction = __FUNCTION__;
	}

	public function clear() {}

	public function testReturnNull() {
		return null;
	}

	public function testReturnTrue() {
		return true;
	}

	public function testReturnFalse() {
		return false;
	}

	public function testReturnNegative1() {
		return -1;
	}

	public function testReturn1() {
		return 1;
	}

	public function testReturn3() {
		return 3;
	}

	public function testReturnString() {
		return 'this is a string';
	}

	public function testReturnEmptyArray() {
		return [];
	}

	public function testReturnArray() {
		return ['a' => 'b'];
	}
}

?>