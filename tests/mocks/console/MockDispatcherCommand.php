<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\console;

use lithium\core\Environment;

class MockDispatcherCommand extends \lithium\console\Command {

	public $env = [];

	protected $_classes = [
		'response' => 'lithium\tests\mocks\console\MockResponse'
	];

	public function testRun() {
		$this->response->testAction = __FUNCTION__;
	}

	public function run($param = null) {
		$this->response->testAction = __FUNCTION__;
		$this->response->testParam = $param;
	}

	public function testAction() {
		$this->response->testAction = __FUNCTION__;
	}

	public function testEnv() {
		$this->response->environment = Environment::get();
	}
}

?>