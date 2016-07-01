<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
		return static::_filter(__FUNCTION__, [], $method);
	}

	public static function manual($filters) {
		$method = function($self, $params, $chain) {
			return "Working";
		};
		return static::_filter(__FUNCTION__, [], $method, $filters);
	}

	public static function callSubclassMethod() {
		return static::_filter(__FUNCTION__, [], function($self, $params, $chain) {
			return $self::childMethod();
		});
	}
}

?>