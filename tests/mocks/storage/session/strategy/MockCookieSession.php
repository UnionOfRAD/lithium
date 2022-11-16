<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\storage\session\strategy;

class MockCookieSession {

	protected static $_secret = 'foobar';

	protected static $_data = ['one' => 'foo', 'two' => 'bar'];

	public static function read($key = null, array $options = []) {
		if (isset(static::$_data[$key])) {
			return static::$_data[$key];
		}
		return static::$_data;
	}

	public static function write($key, $value = null, array $options = []) {
		static::$_data[$key] = $value;
		return $value;
	}

	public static function reset() {
		return static::$_data = ['one' => 'foo', 'two' => 'bar'];
	}

	/**
	 * Method for returning data currently stored in this mock.
	 *
	 * @return array
	 */
	public static function data() {
		return static::$_data;
	}
}

?>