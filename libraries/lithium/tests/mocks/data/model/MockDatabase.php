<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockDatabase extends \lithium\data\source\Database {

	protected $_columns = array(
		'string' => array('length' => 255)
	);

	public function connect() {}

	public function disconnect() {}

	public function entities($class = null) {}

	public function describe($entity, $meta = array()) {}

	public function encoding($encoding = null) {}

	public function result($type, $resource, $context) {}

	public function error() {}

	public function order($value, $context) {
		if (empty($value)) return '';
		$ret = ' ORDER BY ';
		if (is_array($value)) {
			foreach ($value as $field){
				$ret .= $field;
			}
		} elseif (is_string($value)) {
			$ret .= $value;
		} else {
			return '';
		}
		return $ret;
	}

	public function value($value, array $schema = array()) {
		if (is_array($value)) {
			return parent::value($value, $schema);
		}
		if ($value === null) {
			return 'NULL';
		}

		switch ($type = isset($schema['type']) ? $schema['type'] : $this->_introspectType($value)) {
			case 'boolean':
				return $this->_toBoolean($value);
			case 'float':
				return floatval($value);
			case 'integer':
				return intval($value);
		}
		return "'{$value}'";
	}

	protected function _execute($sql) {
		return $sql;
	}

	protected function _insertId($query) {
	}
}

?>