<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\storage\cache\strategy;

/**
 * Mock strategy.
 * Simulates the 'Serializer' strategy.
 */
class MockSerializer extends \lithium\core\Object {

	/**
	 * Write strategy method.
	 * Serializes the passed data.
	 *
	 * @see http://php.net/manual/en/function.serialize.php
	 * @param mixed $data The data to be serialized.
	 * @return string Serialized data.
	 */
	public static function write($data) {
		return serialize($data);
	}

	/**
	 * Read strategy method.
	 * Unserializes the passed data.
	 *
	 * @see http://php.net/manual/en/function.unserialize.php
	 * @param string $data Serialized data.
	 * @return mixed Result of unserialization.
	 */
	public static function read($data) {
		return unserialize($data);
	}
}

?>