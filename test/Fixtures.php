<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\test;

use lithium\core\ConfigException;

class Fixtures extends \lithium\core\Adaptable {

	/**
	 * Stores configuration arrays for session adapters, keyed by configuration name.
	 *
	 * @var array
	 */
	protected static $_configurations = [];

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.test.fixtures';

	/**
	 * Delegate calls to adapters
	 *
	 * @param string $method The called method name.
	 * @param array $params The parameters array.
	 * @return mixed
	 */
	public static function __callStatic($method, $params) {
		$name = array_shift($params);

		if (($config = static::_config($name)) === null) {
			throw new ConfigException("Configuration `{$name}` has not been defined.");
		}
		return call_user_func_array([static::adapter($name), $method], $params);
	}
}

?>