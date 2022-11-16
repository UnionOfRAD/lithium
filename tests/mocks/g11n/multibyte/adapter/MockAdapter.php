<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2012, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\g11n\multibyte\adapter;

class MockAdapter {

	public $testStrlenArgs = [];

	public $testStrposArgs = [];

	public $testStrrposArgs = [];

	public $testSubstrArgs = [];

	public static function enabled() {
		return true;
	}

	public function strlen() {
		$this->testStrlenArgs = func_get_args();
	}

	public function strpos() {
		$this->testStrposArgs = func_get_args();
	}

	public function strrpos() {
		$this->testStrrposArgs = func_get_args();
	}

	public function substr() {
		$this->testSubstrArgs = func_get_args();
	}
}

?>