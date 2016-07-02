<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\core;

/**
 * Provides methods to configure an object.
 */
trait AutoConfigurable {

	/**
	 * Assigns configuration values to object properties.
	 *
	 * For example, given the following:
	 * ```
	 * class Bar{
	 * 	protected $_foo;
	 *
	 * 	public function __construct(array $config = []) {
	 *		$this->_autoConfig($config, ['foo']);
	 * 	}
	 * }
	 *
	 * $instance = new Bar(['foo' => 'baz']);
	 * ```
	 *
	 * The `foo` property would automatically be set to `'baz'`. If `foo` was an array,
	 * `$auto` could be set to `['foo' => 'merge']`, and the config value of `'foo'`
	 * would be merged with the default value of the `foo` property and assigned to it.
	 *
	 * @param array $config
	 * @param array $auto An array of values that should be processed. Each value should have
	 *      a matching protected property (prefixed with `_`) defined in the class. If the
	 *      property is an array, the property name should be the key and the value should
	 *      be `'merge'`.
	 * @return void
	 */
	protected function _autoConfig(array $config, array $auto) {
		foreach ($auto as $key => $flag) {
			if (!isset($config[$key]) && !isset($config[$flag])) {
				continue;
			}
			if ($flag === 'merge') {
				$this->{"_{$key}"} = $config[$key] + $this->{"_{$key}"};
			} else {
				$this->{"_$flag"} = $config[$flag];
			}
		}
	}
}

?>