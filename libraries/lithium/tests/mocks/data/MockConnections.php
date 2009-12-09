<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockConnections extends \lithium\core\Adaptable {

	protected static $_configurations = null;

	protected static $_connections = null;


	public function configureClass($name) {
		return array();
	}

	public static function get($name) {
		return new MockConnections($name);
	}
}
?>