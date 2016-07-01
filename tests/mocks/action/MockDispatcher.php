<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\action;

use stdClass;

class MockDispatcher extends \lithium\action\Dispatcher {

	public static $dispatched = [];

	public static function reset() {
		static::$dispatched = [];
		static::$_rules = [];
	}

	protected static function _callable($request, $params, $options) {
		$callable = new stdClass();
		$callable->params = $params;
		return $callable;
	}

	protected static function _call($callable, $request, $params) {
		if (is_callable($callable->params['controller'])) {
			return parent::_call($callable->params['controller'], $request, $params);
		}
		static::$dispatched[] = $callable;
	}
}

?>