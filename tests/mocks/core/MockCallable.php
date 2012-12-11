<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

class MockCallable extends \lithium\core\Object {

	public $call = array();

	public static $statiCall = array();

	public function __call($method, $params = array()) {
		return $this->call = compact('method', 'params');
	}

	public static function __callStatic($method, $params) {
		return static::$statiCall = compact('method', 'params');
	}
}

?>