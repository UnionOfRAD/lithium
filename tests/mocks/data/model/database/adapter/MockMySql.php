<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model\database\adapter;

class MockMySql extends \lithium\data\source\database\adapter\MySql {

	public function __construct(array $config = array()) {
		parent::__construct($config);
		$this->connection = $this;
	}

	public function quote($value) {
		return "'{$value}'";
	}

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}
}

?>