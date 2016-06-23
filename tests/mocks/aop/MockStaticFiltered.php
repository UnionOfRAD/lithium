<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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