<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\template\helper;

use lithium\action\Request;

class MockFormRenderer extends \lithium\template\view\Renderer {

	public function request() {
		if (empty($this->_request)) {
			$this->_request = new Request();
			$this->_request->params += ['controller' => 'posts', 'action' => 'add'];
		}
		return $this->_request;
	}

	public function render($template, $data = [], array $options = []) {
	}
}

?>