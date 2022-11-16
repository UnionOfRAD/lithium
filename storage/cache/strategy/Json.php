<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\cache\strategy;

/**
 * A JSON encoder/decoder strategy.
 */
class Json {

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
		return json_decode($data ?? '', true);
	}
}

?>