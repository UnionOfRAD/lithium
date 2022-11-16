<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2013, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\test;

class MockStdClass {

	protected $_data = [];

	public function __set($key, $value) {
		return $this->_data[$key] = $value;
	}

	public function &__get($key) {
		if (isset($this->_data[$key])) {
			$data =& $this->_data[$key];
			return $data;
		}
		$data = null;
		return $data;
	}

	public function &data() {
		$data =& $this->_data;
		return $data;
	}

	public function filterableData() {
		return $this->_data;
	}

	public function method1() {
		return true;
	}

	public function method2() {
		return false;
	}

	public function getClass() {
		return get_class($this);
	}

	public function isExecutable() {
		return is_executable(__FILE__);
	}

	public static function methodFoo() {
		return true;
	}

	public function __call($method, $params) {
		return $this->methodFoo();
	}

	public static function __callStatic($method, $params) {
		return static::methodFoo();
	}

	public function methodBar() {
		$this->methodBaz(1);
		return $this->__call('methodBaz', [2]);
	}

}

?>