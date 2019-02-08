<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source\database\adapter;

class MockSqlite3 extends \lithium\data\source\database\adapter\Sqlite3 {

	public function get($var) {
		return $this->{$var};
	}

	protected function _execute($sql) {
		return $sql;
	}
}

?>