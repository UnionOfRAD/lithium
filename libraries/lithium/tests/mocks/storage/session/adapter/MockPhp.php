<?php

namespace lithium\tests\mocks\storage\session\adapter;

use \lithium\storage\session\adapter\Php;

class MockPhp extends Php {

	/**
	 * Overriden method for testing.
	 *
	 * @return boolean false.
	 */
	public static function isStarted() {
		return false;
	}

	/**
	 * Overriden method for testing.
	 *
	 * @return boolean false.
	 */
	protected static function _startup() {
		return false;
	}
}

?>