<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\core;

class MockCallable {

	public $construct = [];

	public $call = [];

	public $get = '';

	public static $callStatic = [];

	public $trace = [];

	public function __construct() {
		$this->trace[] = [__FUNCTION__, func_get_args()];
		$this->construct = func_get_args();
	}

	public function __call($method, $params = []) {
		$this->trace[] = [__FUNCTION__, func_get_args()];
		return $this->call = compact('method', 'params');
	}

	public static function __callStatic($method, $params) {
		return static::$callStatic = compact('method', 'params');
	}

	public function __get($value) {
		$this->trace[] = [__FUNCTION__, func_get_args()];
		return $this->get = $value;
	}
}

?>