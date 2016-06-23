<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\console\command;

class MockCreate extends \lithium\console\command\Create {

	protected $_classes = [
		'response' => 'lithium\tests\mocks\console\MockResponse'
	];

	public function save($template, $params = []) {
		return $this->_save($template, $params);
	}
}

?>