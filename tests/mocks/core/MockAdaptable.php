<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

class MockAdaptable extends \lithium\core\Adaptable {

	protected static $_configurations = array();

	protected static $_adapters = 'adapter.storage.cache';

	protected static $_strategies = 'strategy.storage.cache';

	public static function testInitialized($name) {
		$config = static::_config($name);
		return isset($config['object']);
	}
}

?>