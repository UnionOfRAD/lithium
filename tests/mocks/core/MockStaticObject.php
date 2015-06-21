<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

use Exception;
use lithium\aop\Filters;

class MockStaticObject extends \lithium\core\StaticObject {

	public static function throwException() {
		return Filters::run(get_called_class(), __FUNCTION__, array(), function($params) {
			throw new Exception('foo');
			return 'bar';
		});
	}

	public static function foo() {
		$args = func_get_args();
		return $args;
	}

	public static function parents($get = false) {
		if ($get === null) {
			static::$_parents = array();
		}
		if ($get) {
			return static::$_parents;
		}
		return static::_parents();
	}
}

?>