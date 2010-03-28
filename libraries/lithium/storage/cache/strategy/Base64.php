<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\strategy;

/**
 * A PHP base64 encoding strategy.
 */
class Base64 extends \lithium\core\Object {

	/**
	 * Write strategy method.
	 * Base64 encodes the passed data.
	 *
	 * @see http://php.net/manual/en/function.base64-encode.php
	 * @param mixed $data The data to be serialized.
	 * @return string Serialized data.
	 */
	public function write($data) {
		return base64_encode($data);
	}

	/**
	 * Read strategy method.
	 * Unserializes the passed data.
	 *
	 * @see http://php.net/manual/en/function.base64-decode.php
	 * @param string $data Serialized data.
	 * @return mixed Result of unserialization.
	 */
	public function read($data) {
		return base64_decode($data);
	}
}

?>