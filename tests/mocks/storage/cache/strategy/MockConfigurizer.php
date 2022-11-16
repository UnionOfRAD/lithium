<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\storage\cache\strategy;

/**
 * Mock strategy.
 * For testing strategies that need construct-time parameters.
 */
class MockConfigurizer {

	public static $parameters = [];

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = []) {
		static::$parameters = $config;
	}

	/**
	 * Write strategy method.
	 *
	 * @param mixed $data The data to be modified.
	 * @return string Modified data.
	 */
	public static function write($data) {
		return static::$parameters;
	}

	/**
	 * Read strategy method.
	 * Unserializes the passed data.
	 *
	 * @param string $data Data read.
	 * @return mixed Modified data.
	 */
	public static function read($data) {
		return static::$parameters;
	}
}

?>