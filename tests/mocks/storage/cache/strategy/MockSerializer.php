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
 * Simulates the 'Serializer' strategy.
 */
class MockSerializer {

	/**
	 * Write strategy method.
	 * Serializes the passed data.
	 *
	 * @link http://php.net/function.serialize.php PHP Manual: serialize()
	 * @param mixed $data The data to be serialized.
	 * @return string Serialized data.
	 */
	public function write($data) {
		return serialize($data);
	}

	/**
	 * Read strategy method.
	 * Unserializes the passed data.
	 *
	 * @link http://php.net/function.unserialize.php PHP Manual: unserialize()
	 * @param string $data Serialized data.
	 * @return mixed Result of unserialization.
	 */
	public function read($data) {
		return unserialize($data);
	}
}

?>