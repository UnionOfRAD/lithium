<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\core;

class MockAdaptable extends \lithium\core\Adaptable {

	protected static $_configurations = [];

	protected static $_adapters = 'adapter.storage.cache';

	protected static $_strategies = 'strategy.storage.cache';

	public static function testInitialized($name) {
		$config = static::_config($name);
		return isset($config['object']);
	}
}

?>