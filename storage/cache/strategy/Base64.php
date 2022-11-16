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
 * A PHP base64-encoding strategy.
 */
class Base64 {

	/**
	 * Write strategy method.
	 *
	 * Base64-encodes the passed data.
	 *
	 * @link http://php.net/function.base64-encode.php PHP Manual: base64_encode()
	 * @param mixed $data The data to be serialized.
	 * @return string Serialized data.
	 */
	public function write($data) {
		return base64_encode($data);
	}

	/**
	 * Read strategy method.
	 *
	 * Unserializes the passed data.
	 *
	 * @link http://php.net/function.base64-decode.php PHP Manual: base64_decode()
	 * @param string $data Serialized data.
	 * @return mixed Result of unserialization.
	 */
	public function read($data) {
		return base64_decode($data);
	}
}

?>