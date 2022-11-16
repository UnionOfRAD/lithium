<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\storage\session\adapter;

class MockPhp extends \lithium\storage\session\adapter\Php {

	public function config() {
		return $this->_config;
	}

	/**
	 * Overridden method for testing.
	 *
	 * @return boolean false.
	 */
	public function isStarted() {
		return false;
	}

	/**
	 * Overriden method for testing.
	 *
	 * @return boolean false.
	 */
	protected function _start() {
		return false;
	}
}

?>