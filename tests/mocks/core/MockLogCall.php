<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\core;

class MockLogCall {

	public $construct = [];

	public $get = [];

	public $return = [];

	public static $returnStatic = [];

	public $call = [];

	public static $callStatic = [];

	public function __construct() {
		$this->construct = func_get_args();
	}

	public function __clear() {
		$this->call = [];
		$this->return = [];
		$this->get = [];
		static::$callStatic = [];
	}

	public function __call($method, $params = []) {
		$call = compact('method', 'params');
		$this->call[] = $call;
		return isset($this->return[$method]) ? $this->return[$method]: $call;
	}

	public static function __callStatic($method, $params) {
		$callStatic = compact('method', 'params');
		static::$callStatic[] = $callStatic;
		return isset(static::$returnStatic[$method]) ? static::$returnStatic[$method]: $callStatic;
	}

	public function __get($value) {
		return $this->get[] = $value;
	}
}

?>