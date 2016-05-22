<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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