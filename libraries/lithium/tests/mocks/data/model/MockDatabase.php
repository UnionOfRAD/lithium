<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockDatabase extends \lithium\data\source\Database {

	public function connect() {}

	public function disconnect() {}

	public function entities($class = null) {}

	public function describe($entity, $meta = array()) {}

	public function encoding($encoding = null) {}

	public function result($type, $resource, $context) {}

	public function error() {}
}

?>