<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\console;

class MockDispatcherCommand extends \lithium\console\Command {

	protected $_classes = array(
		'response' => '\lithium\tests\mocks\console\MockResponse'
	);

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
}

?>