<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\strategy;

/**
 * A JSON encoder/decoder strategy.
 */
class Json extends \lithium\core\Object {

	/**
	 * Write strategy method.
	 * Will json_encode to the passed data.
	 *
	 * @see http://php.net/manual/en/function.json_encode.php
	 * @param mixed $data The data to be encoded.
	 * @return string The encoded  data.
	 */
	public function write($data) {
		return json_encode($data);
	}

	/**
	 * Read strategy method.
	 * Applies json_decode to the passed data.
	 *
	 * @see http://php.net/manual/en/function.json_decode.php
	 * @param string $data Serialized data.
	 * @return mixed Result of unserialization.
	 */
	public function read($data) {
		return json_decode($data);
	}
}

?>