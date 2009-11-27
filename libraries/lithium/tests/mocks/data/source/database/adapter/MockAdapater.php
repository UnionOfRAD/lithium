<?php

namespace lithium\tests\mocks\data\source\database\adapter;

class MockAdapter extends \lithium\data\source\Database {

	public function __construct($config = array()) {
		parent::__construct($config);
	}

	public function encoding($encoding = null) {
		if (empty($encoding)) {
			return '';
		}
		return $encoding;
	}

	public function describe($source, $contect) {
		var_dump($source);
		return null;
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

	function order($order, $context) {
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