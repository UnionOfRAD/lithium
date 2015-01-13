<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\storage\session\adapter;

class MockPhp extends \lithium\storage\session\adapter\Php {

	/**
	 * Overridden method for testing.
	 *
	 * @return boolean false.
	 */
	public function isStarted() {
		return false;
	}

	/**
	 * Overriden method for testing.
	 *
	 * @return boolean false.
	 */
	protected function _start() {
		return false;
	}
}

?>