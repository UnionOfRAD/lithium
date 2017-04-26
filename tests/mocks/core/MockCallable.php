<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

class MockCallable extends \lithium\core\Object {

	public $construct = array();

	public $call = array();

	public $get = '';

	public $trace = array();

	public static $callStatic = array();

	public function __construct() {
		$this->trace[] = array(__FUNCTION__, func_get_args());
		$this->construct = func_get_args();
	}

	public function __call($method, $params = array()) {
		$this->trace[] = array(__FUNCTION__, func_get_args());
		return $this->call = compact('method', 'params');
	}

	public static function __callStatic($method, $params) {
		return static::$callStatic = compact('method', 'params');
	}

	public function __get($value) {
		$this->trace[] = array(__FUNCTION__, func_get_args());
		return $this->get = $value;
	}
}

?>