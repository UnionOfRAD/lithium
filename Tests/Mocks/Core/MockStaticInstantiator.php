<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Core;

class MockStaticInstantiator extends \Lithium\Core\StaticObject {

	protected static $_classes = array('request' => '\Lithium\Tests\Mocks\Core\MockRequest');

	public static function instance($name, array $config = array()) {
		return static::_instance($name, $config);
	}
}

?>