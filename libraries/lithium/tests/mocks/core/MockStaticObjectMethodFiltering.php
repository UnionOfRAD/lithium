<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

class MockStaticObjectMethodFiltering extends \lithium\core\StaticObject {

	public static function method($data) {
		$data[] = 'Starting outer method call';
		$result = static::_filter(__METHOD__, compact('data'), function($self, $params, $chain) {
			$params['data'][] = 'Inside method implementation of ' . $self;
			return $params['data'];
		});
		$result[] = 'Ending outer method call';
		return $result;
	}

	public static function method2() {
		$filters =& static::$_methodFilters;
		$method = function($self, $params, $chain) use (&$filters) {
			return $filters;
		};
		return static::_filter(__METHOD__, array(), $method);
	}

	public static function foo() {
		$args = func_get_args();
		return $args;
	}
}

?>