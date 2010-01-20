<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\database\adapter;

class MockAdapter extends \lithium\data\source\Database {

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	public function entities($class = null) {

	}

	public function encoding($encoding = null) {
		if (empty($encoding)) {
			return '';
		}
		return $encoding;
	}

	public function  describe($entity, $meta = array()) {
		return array();
	}

	public function create($record, $options = array()) {
		return true;
	}

	public function read($query, $options = array()) {
		return true;
	}

	public function update($query, $options) {
		return true;
	}

	public function delete($query, $options) {
		return true;
	}

	public function result($type, $resource, $context) {
		return true;
	}

	public function error() {
		return false;
	}

	public function name($name) {
		return $name;
	}

	public function value($value) {
		return $value;
	}

	public function columns($query, $resource = null, $context = null) {
		return true;
	}

	public function conditions($conditions, $context) {
		return $conditions;
	}

	public function fields($fields, $context) {
		if (empty($fields)) {
			return $context->fields();
		}
		return $fields;
	}

	public function limit($limit, $context) {
		if (empty($limit)) {
			return '';
		}
		return $limit;
	}

	public function order($order, $context) {
		if (empty($order)) {
			return '';
		}
		return $order;

	}

	public function renderCommand($type, $data, $context) {
		return '';
	}
}

?>