<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\console;

class MockCommandForDispatcher extends \lithium\console\Command {

	public function run($param = null) {
		return 'test run' . $param;
	}

	public function testAction() {
		return 'test action';
	}
}

?>