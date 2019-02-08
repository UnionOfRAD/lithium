<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\model\database\adapter;

class MockSqlite3 extends \lithium\data\source\database\adapter\Sqlite3 {

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	public function dsn() {
		return $this->_config['dsn'];
	}
}

?>