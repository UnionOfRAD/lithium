<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\console;

class MockRequestForDispatcher extends \lithium\console\Request {

	public $params = array(
		'command' => '\lithium\tests\mocks\console\MockCommandForDispatcher'
	);
}

?>