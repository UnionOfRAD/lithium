<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

class MockAdapter extends \lithium\core\Adaptable {

	protected static $_configurations = null;

	public static function adapter($name) {
		return static::_adapter('adapters.storage.cache', $name);
	}

}
?>
