<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockDatabase extends \lithium\data\source\Database {

	public function connect() {}

	public function disconnect() {}

	public function entities($class = null) {}

	public function describe($entity, $meta = array()) {}

	public function encoding($encoding = null) {}

	public function result($type, $resource, $context) {}

	public function error() {}

	public function conditions($cond, $context) {
		if (!is_array($cond)){
			return '';
		}
		$ret = ' WHERE ';
		foreach ($cond as $field => $value) {
		    $ret .= '`' . $context->model() . '`.';
			$ret .= '`' . $field . "`=\'" . $value . "\',";
		}
		$ret = substr($ret,0,-1);
		return $ret;
	}

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
}

?>