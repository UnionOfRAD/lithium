<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

class MockCallable extends \lithium\core\Object {

	public $construct = [];

	public $call = [];

	public $get = '';

	public static $callStatic = [];

	public function __construct() {
		$this->construct = func_get_args();
	}

	public function __call($method, $params = []) {
		return $this->call = compact('method', 'params');
	}

	public static function __callStatic($method, $params) {
		return static::$callStatic = compact('method', 'params');
	}

	public function __get($value) {
		return $this->get = $value;
	}
}

?>