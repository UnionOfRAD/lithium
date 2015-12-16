<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\strategy;

use lithium\core\Filterable;

/**
 * A JSON encoder/decoder strategy.
 */
class Json extends \lithium\core\Object {
	use Filterable;

	/**
	 * Write strategy method.
	 *
	 * Encodes the passed data from an array to JSON format.
	 *
	 * @link http://php.net/function.json-encode.php PHP Manual: json_encode()
	 * @param mixed $data The data to be encoded.
	 * @return string The encoded  data.
	 */
	public function write($data) {
		return json_encode($data);
	}

	/**
	 * Read strategy method.
	 *
	 * Decodes JSON data and returns an array or object structure.
	 *
	 * @link http://php.net/function.json-decode.php PHP Manual: json_decode()
	 * @param string $data Serialized data.
	 * @return mixed Result of unserialization.
	 */
	public function read($data) {
		return json_decode($data, true);
	}
}

?>