<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data\Source\Database\Adapter;

class MockSqlite3 extends \Lithium\Data\Source\Database\Adapter\Sqlite3 {

	public function get($var) {
		return $this->{$var};
	}
}

?>