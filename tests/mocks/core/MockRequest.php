<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\core;

class MockRequest extends \lithium\core\Object {

	public $url = null;

	public $params = [];

	public $argv = [];

	public function __get($key) {
		if (isset($this->params[$key])) {
			return $this->params[$key];
		}
		return null;
	}

	public function env($key) {
		if (isset($this->_config[$key])) {
			return $this->_config[$key];
		}
		return null;
	}

	public function get($key) {
		return $this->env($key);
	}
}

?>