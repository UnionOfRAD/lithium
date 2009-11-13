<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\console;

class MockCommand extends \lithium\console\Command {

	public $case = null;

	protected $_dontShow = null;

	protected $_classes = array(
		'response' => '\lithium\tests\mocks\console\MockResponse'
	);

	public function testRun() {
		return 'test run';
	}
}

?>