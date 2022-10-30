<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source\database\adapter;

use lithium\core\Libraries;

class MockAdapter extends \lithium\data\source\Database {

	/**
	 * An array of records to test.
	 *
	 * This is useful for testing how an `Adapter` returns the data when invoking
	 * the `Adapter::result()` function
	 *
	 * @var array
	 */
	protected $_records = [];

	/**
	 * A list of columns for the current test
	 *
	 * @var array
	 */
	protected $_columns = [];

	/**
	 * Holds an array of values that should be processed on initialisation.
	 *
	 * @var array
	 */
	protected $_autoConfig = ['records', 'columns'];

	/**
	 * Internal pointer to indicate the current record.
	 *
	 * @var array
	 */
	protected $_pointer = 0;

	public function __construct(array $config = []) {
		$defaults =  ['records' => [], 'columns' => [], 'database' => 'mock'];
		$config['autoConnect'] = false;
		parent::__construct((array) $config + $defaults);
	}

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	public function sources($class = null) {
	}

	public function encoding($encoding = null) {
		return $encoding ?: '';
	}

	public function describe($entity, $fields = [], array $meta = []) {
		return Libraries::instance(null, 'schema', compact('fields', 'meta'), $this->_classes);
	}

	public function create($record, array $options = []) {
		return true;
	}

	public function read($query, array $options = []) {
		return true;
	}

	public function update($query, array $options = []) {
		return true;
	}

	public function delete($query, array $options = []) {
		return true;
	}

	public function result($type, $resource, $context) {
		$return = null;
		if (array_key_exists($this->_pointer, $this->_records)) {
			$return = $this->_records[$this->_pointer++];
		}
		return $return;
	}

	public function error() {
		return false;
	}

	public function name($name) {
		return $name;
	}

	public function value($value, array $schema = []) {
		if (is_array($value)) {
			return parent::value($value, $schema);
		}
		return $value;
	}

	public function schema($query, $resource = null, $context = null) {
		return $this->_columns;
	}

	public function conditions($conditions, $context, array $options = []) {
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

	public function renderCommand($type, $data = null, $context = null) {
		return '';
	}

	public function key() {
	}

	protected function _execute($sql, $options = []) {
		return $sql;
	}

	protected function _buildColumn($field) {
		return $field['name'];
	}

	protected function _insertId($query) {}

	public static function enabled($feature = null) {
		if (!$feature) {
			return true;
		}
		$features = [
			'arrays' => false,
			'transactions' => true,
			'booleans' => true,
			'schema' => true,
			'relationships' => true,
			'sources' => true
		];
		return isset($features[$feature]) ? $features[$feature] : null;
	}
}

?>