<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\database\adapter;

use lithium\tests\mocks\data\model\database\adapter\MockSqlite3;

class Sqlite3Test extends \lithium\test\Unit {

	public function testDsn() {
		$db = new MockSqlite3(array(
			'autoConnect' => false,
			'database' => '/tmp/test.sqlite'
		));
		$expected = 'sqlite:/tmp/test.sqlite';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);

		$db = new MockSqlite3(array(
			'autoConnect' => false,
			'database' => ':memory:'
		));
		$expected = 'sqlite::memory:';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);
	}
}

?>