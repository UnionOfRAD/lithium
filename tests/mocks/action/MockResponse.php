<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\action;

class MockResponse extends \lithium\action\Response {

	public $testHeaders = [];

	public $data = [];

	public $options = [];

	public function render() {
		$this->testHeaders = [];
		parent::render();
		$this->headers = [];
	}

	protected function _writeHeaders($header, $code = null) {
		$this->testHeaders = array_merge($this->testHeaders, (array) $header);
	}
}

?>