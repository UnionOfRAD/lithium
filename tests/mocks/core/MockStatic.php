<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2015, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\core;

use Exception;
use lithium\aop\Filters;

class MockStatic {

	public static function throwException() {
		return Filters::run(get_called_class(), __FUNCTION__, [], function($params) {
			throw new Exception('foo');
			return 'bar';
		});
	}

	public static function foo() {
		$args = func_get_args();
		return $args;
	}
}

?>