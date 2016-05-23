<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

/**
 * @deprecated
 */
class MockStaticMethodFiltering extends \lithium\core\StaticObject {

	public static function method($data) {
		$data[] = 'Starting outer method call';
		$result = static::_filter(__FUNCTION__, compact('data'), function($self, $params, $chain) {
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
		return static::_filter(__FUNCTION__, array(), $method);
	}

	public static function manual($filters) {
		$method = function($self, $params, $chain) {
			return "Working";
		};
		return static::_filter(__FUNCTION__, array(), $method, $filters);
	}

	public static function callSubclassMethod() {
		return static::_filter(__FUNCTION__, array(), function($self, $params, $chain) {
			return $self::childMethod();
		});
	}
}

?>