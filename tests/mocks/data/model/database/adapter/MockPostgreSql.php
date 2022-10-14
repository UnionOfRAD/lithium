<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2014, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\model\database\adapter;

class MockPostgreSql extends \lithium\data\source\database\adapter\PostgreSql {

	public $log = false;

	public $logs = [];

	public $return;

	public $sql;

	protected $_quotes = ['{', '}'];

	public function __construct(array $config = []) {
		parent::__construct($config + ['database' => 'mock']);
		$this->connection = $this;
	}

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	protected function _execute($sql, $options = []) {
		$this->sql = $sql;
		if ($this->log) {
			$this->logs[] = $sql;
		}
		if (isset($this->return['_execute'])) {
			return $this->return['_execute'];
		}
		return new MockResult();
	}

	public function dsn() {
		return $this->_config['dsn'];
	}
}

?>