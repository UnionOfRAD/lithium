<?php

namespace Lithium\Tests\Mocks\Storage\Session\Adapter;

class MockPhp extends \Lithium\Storage\Session\Adapter\Php {

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