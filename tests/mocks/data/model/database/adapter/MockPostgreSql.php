<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2014, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model\database\adapter;

class MockPostgreSql extends \lithium\data\source\database\adapter\PostgreSql {

	protected $_quotes = array('{', '}');

	public function __construct(array $config = array()) {
		parent::__construct($config + array('database' => 'mock'));
		$this->connection = $this;
	}

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	protected function _execute($sql) {
		$this->sql = $sql;
		if ($this->log) {
			$this->logs[] = $sql;
		}
		if (isset($this->return['_execute'])) {
			return $this->return['_execute'];
		}
		return new MockResult();
	}
}

?>