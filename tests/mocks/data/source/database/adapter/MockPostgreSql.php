<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD,http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\database\adapter;

class MockPostgreSql extends \lithium\data\source\database\adapter\PostgreSql {

	public function get($var) {
		return $this->{$var};
	}

	protected function _execute($sql) {
		return $sql;
	}
}

?>