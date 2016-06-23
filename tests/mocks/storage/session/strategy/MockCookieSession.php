<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\storage\session\strategy;

class MockCookieSession extends \lithium\core\Object {

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