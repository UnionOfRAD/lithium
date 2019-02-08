<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\source\database\adapter;

use lithium\tests\mocks\data\model\database\adapter\MockSqlite3;

class Sqlite3Test extends \lithium\test\Unit {

	public function testDsn() {
		$db = new MockSqlite3([
			'autoConnect' => false,
			'database' => '/tmp/test.sqlite'
		]);
		$expected = 'sqlite:/tmp/test.sqlite';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);

		$db = new MockSqlite3([
			'autoConnect' => false,
			'database' => ':memory:'
		]);
		$expected = 'sqlite::memory:';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);
	}
}

?>