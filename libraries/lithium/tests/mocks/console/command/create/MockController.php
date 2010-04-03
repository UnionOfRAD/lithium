<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\console\command\create;

class MockController extends \lithium\console\command\create\ControllerTwo {

	public function parse($template) {
		return $this->_parse($template);
	}
}

?>