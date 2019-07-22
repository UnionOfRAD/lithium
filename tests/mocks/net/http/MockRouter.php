<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2017, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\net\http;

class MockRouter extends \lithium\net\http\Router {

	public static function compileScope(array $config) {
		return parent::_compileScope($config);
	}

	public static function parseScope($name, $request) {
		return parent::_parseScope($name, $request);
	}
}

?>