<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\aop;

use lithium\aop\Filters;

class MockStaticFiltered {

	public static function method() {
		return Filters::run(get_called_class(), __FUNCTION__, [], function($params) {
			return 'method';
		});
	}

	public static function method2() {
		return Filters::run(get_called_class(), __FUNCTION__, [], function($params) {
			return 'method2';
		});
	}

	public static function methodTracing(array $trace = []) {
		$trace[] = 'Starting outer method call';

		$result = Filters::run(get_called_class(), __FUNCTION__, compact('trace'), function($params) {
			$params['trace'][] = 'Inside method implementation of ' . get_called_class();
			return $params['trace'];
		});
		$result[] = 'Ending outer method call';
		return $result;
	}

	public static function callSubclassMethod() {
		return Filters::run(get_called_class(), __FUNCTION__, [], function($params) {
			return static::childMethod();
		});
	}
}

?>