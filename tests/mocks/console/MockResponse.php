<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\console;

use lithium\util\Text;

class MockResponse extends \lithium\console\Response {

	public $testAction;

	public $testParam;

	public function __construct(array $config = []) {
		parent::__construct($config);
		$this->output = null;
		$this->error = null;
	}

	public function output($output) {
		return $this->output .= Text::insert($output, $this->styles(false));
	}

	public function error($error) {
		return $this->error .= Text::insert($error, $this->styles(false));
	}

	public function __destruct() {
		$this->output = null;
		$this->error = null;
	}
}

?>