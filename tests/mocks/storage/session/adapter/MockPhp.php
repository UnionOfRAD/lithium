<?php

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