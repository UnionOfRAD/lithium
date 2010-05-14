<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockDatabase extends \lithium\data\source\Database {

	public $sql = null;

	protected $_quotes = array('{', '}');

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	public function entities($class = null) {}

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

	public function testConfig() {
		return $this->_config;
	}

	protected function _execute($sql) {
		return $this->sql = $sql;
	}

	protected function _insertId($query) {
		return sha1(serialize($query));
	}
}

?>