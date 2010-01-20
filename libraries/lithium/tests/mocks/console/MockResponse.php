<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\console;

class MockResponse extends \lithium\console\Response {

	public $testAction;

	public $testParam;

	public function __construct($config = array()) {
		parent::__construct($config);
		$this->output = null;
		$this->error = null;
	}

	public function output($string) {
		return $this->output .= $string;
	}

	public function error($string) {
		return $this->error .= $string;
	}

	public function __destruct() {
		$this->output = null;
		$this->error = null;
	}
}

?>