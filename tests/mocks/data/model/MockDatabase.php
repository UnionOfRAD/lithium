<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\model;

use lithium\core\Libraries;
use lithium\tests\mocks\data\model\database\MockResult;

class MockDatabase extends \lithium\data\source\Database {

	/**
	 * Mock column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = [
		'primary_key' => ['name' => 'NOT NULL AUTO_INCREMENT'],
		'string' => ['name' => 'varchar', 'length' => 255],
		'text' => ['name' => 'text'],
		'integer' => ['name' => 'int', 'length' => 11, 'formatter' => 'intval'],
		'float' => ['name' => 'float', 'formatter' => 'floatval'],
		'datetime' => ['name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'],
		'timestamp' => [
			'name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'
		],
		'time' => ['name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'],
		'date' => ['name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'],
		'binary' => ['name' => 'blob'],
		'boolean' => ['name' => 'tinyint', 'length' => 1]
	];

	public $connection = null;

	public $sql = null;

	public $logs = [];

	public $log = false;

	public $return = [];

	protected $_quotes = ['{', '}'];

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

	public function sources($class = null) {}

	public function describe($entity, $fields = [], array $meta = []) {
		return Libraries::instance(null, 'schema', compact('fields'), $this->_classes);
	}

	public function encoding($encoding = null) {}

	public function result($type, $resource, $context) {}

	public function error() {}

	public function value($value, array $schema = []) {
		if (($result = parent::value($value, $schema)) !== null) {
			return $result;
		}
		return "'{$value}'";
	}

	public function testConfig() {
		return $this->_config;
	}

	protected function _execute($sql, $options = []) {
		$this->sql = $sql;
		if ($this->log) {
			$this->logs[] = $sql;
		}
		if (isset($this->return['_execute'])) {
			if (is_callable($this->return['_execute'])) {
				return $this->return['_execute']($sql);
			}
			return $this->return['_execute'];
		}
		return new MockResult();
	}

	public function schema($query, $resource = null, $context = null) {
		if (isset($this->return['schema'])) {
			return $this->return['schema'];
		}
		return parent::schema($query, $resource = null, $context = null);
	}

	protected function _buildColumn($field) {
		return $field['name'];
	}

	protected function _insertId($query) {
		$query = $query->export($this);
		ksort($query);
		return sha1(serialize($query));
	}

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

	public function splitFieldname($field) {
		return parent::_splitFieldname($field);
	}
}

?>