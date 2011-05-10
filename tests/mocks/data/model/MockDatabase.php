<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

use lithium\tests\mocks\data\model\mock_database\MockResult;

class MockDatabase extends \lithium\data\source\Database {

	public $sql = null;

	protected $_quotes = array('{', '}');

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	public function sources($class = null) {}

	public function describe($entity, array $meta = array()) {}

	public function encoding($encoding = null) {}

	public function result($type, $resource, $context) {}

	public function error() {}

	public function value($value, array $schema = array()) {
		if (($result = parent::value($value, $schema)) !== null) {
			return $result;
		}
		return "'{$value}'";
	}

	public function cast($entity, array $data, array $options = array()) {
		$defaults = array('first' => false);
		$options += $defaults;
		return $options['first'] ? reset($data) : $data;
	}

	public function testConfig() {
		return $this->_config;
	}

	protected function _execute($sql) {
		$this->sql = $sql;
		return new MockResult();
	}

	protected function _insertId($query) {
		$query = $query->export($this);
		ksort($query);
		return sha1(serialize($query));
	}
}

?>