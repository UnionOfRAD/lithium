<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\model\database\adapter;

class MockMySql extends \lithium\data\source\database\adapter\MySql {

	public function __construct(array $config = []) {
		parent::__construct($config + ['database' => 'mock']);
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

	public function dsn() {
		return $this->_config['dsn'];
	}
}

?>