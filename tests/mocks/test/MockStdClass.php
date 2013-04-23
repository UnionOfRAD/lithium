<?php

namespace lithium\tests\mocks\test;

class MockStdClass extends \lithium\core\Object {

	protected $_data = array();

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
		return $this->__call('methodBaz', array(2));
	}

}

?>