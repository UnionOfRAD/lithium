<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Console;

use Lithium\Core\Environment;

class MockDispatcherCommand extends \Lithium\Console\Command {

	protected $_classes = array(
		'response' => '\Lithium\Tests\Mocks\Console\MockResponse'
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

	public function testEnv() {
		$this->response->environment = Environment::get();
	}
}

?>