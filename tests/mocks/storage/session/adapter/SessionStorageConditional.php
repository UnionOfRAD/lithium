<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\storage\session\adapter;

class SessionStorageConditional extends \lithium\storage\session\adapter\Memory {

	public function read($key = null, array $options = []) {
		return isset($options['fail']) ? null : parent::read($key, $options);
	}

	public function write($key, $value, array $options = []) {
		return isset($options['fail']) ? null : parent::write($key, $value, $options);
	}
}

?>