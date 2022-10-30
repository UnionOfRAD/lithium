<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\test;

use lithium\core\ConfigException;
use lithium\core\Libraries;

class Fixture extends \lithium\data\Schema {

	/**
	 * Classes used by `Fixture`.
	 *
	 * @var array
	 */
	protected $_classes = [
		'connections' => 'lithium\data\Connections',
		'schema' => 'lithium\data\Schema',
		'query' => 'lithium\data\model\Query'
	];
	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = [
		'connection', 'source', 'locked', 'model', 'fields' => 'merge', 'meta', 'records'
	];

	/**
	 * The connection name
	 *
	 * @var string
	 */
	protected $_connection = null;

	/**
	 * The name of the source. Used if `Fixture::_model` is not set (i.e. `null`).
	 *
	 * @var string
	 */
	protected $_source = null;

	/**
	 * The fully-namespaced attached model class name
	 *
	 * @var string
	 */
	protected $_model = null;

	/**
	 * Fields definition
	 *
	 * Example:
	 * {{{
	 * protected $_fields = [
	 *     'id'  => ['type' => 'id'],
	 *     'firstname' => ['type' => 'string', 'default' => 'foo', 'null' => false],
	 *     'lastname' => ['type' => 'string', 'default' => 'bar', 'null' => false]
	 * ];
	 * }}}
	 *
	 * @var array
	 */
	protected $_fields = [];

	/**
	 * Altered fields definition
	 *
	 * @var array
	 */
	protected $_alteredFields = [];

	/**
	 * Metas for the fixture.
	 *
	 * Example:
	 * {{{
	 * protected $_meta = [
	 *     'constraints' => [
	 *         [
	 *             'type' => 'foreign_key',
	 *             'column' => 'id',
	 *             'toColumn' => 'id',
	 *             'to' => 'other_table'
	 *         ]
	 *      ],
	 *     'table' => ['charset' => 'utf8', 'engine' => 'InnoDB']
	 * ];
	 * }}}
	 *
	 * @var array
	 */
	protected $_meta = [];

	/**
	 * The records should be an array of rows. Each row should have values keyed by
	 * the column name.
	 *
	 * Example:
	 * {{{
	 * protected $_records = [
	 *     ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe'],
	 *     ['id' => 2, 'firstname' => 'Pamela', 'lastname' => 'A.'],
	 *     ['id' => 6, 'firstname' => 'Jay', 'lastname' => 'Miner'],
	 *     ['id' => 9, 'firstname' => 'Obi-Wan', 'lastname' => 'Kenobi']
	 * ];
	 * }}}
	 *
	 * @var array
	 */
	protected $_records = [];

	/**
	 * If `true` only fields in `Fixture::_fields` are allowed in records (kind of whitelist).
	 *
	 * @var boolean
	 */
	protected $_locked = null;

	/**
	 * Initializes class configuration (`$_config`), and assigns object properties using the
	 * `_init()` method, unless otherwise specified by configuration. See below for details.
	 *
	 * @see lithium\core\Object::__construct()
	 * @param array $config The configuration options
	 */
	public function __construct($config = []) {
		parent::__construct($config + ['alters' => []]);
	}

	/**
	 * Initializer function called by the constructor unless the constructor
	 *
	 * @see lithium\core\Object::_init()
	 * @throws ConfigException
	 */
	protected function _init() {
		parent::_init();

		if (!$this->_connection) {
			throw new ConfigException("The `'connection'` option must be set.");
		}

		if (!$this->_source && !$this->_model) {
			throw new ConfigException("The `'model'` or `'source'` option must be set.");
		}

		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);

		if ($model = $this->_model) {
			$model::config(['meta' => ['connection' => $this->_connection]]);
			$this->_source = $this->_source ? : $model::meta('source');
			$this->_locked = ($this->_locked === null) ? $model::meta('locked') : $this->_locked;
		}

		if ($this->_locked === null) {
			if ($db::enabled('schema')) {
				$this->_locked = true;
			} else {
				$this->_locked = false;
			}
		}

		foreach ($this->_config['alters'] as $mode => $values) {
			foreach ($values as $key => $value) {
				$this->alter($mode, $key, $value);
			}
		}
	}

	/**
	 * Create the fixture's schema.
	 *
	 * @return boolean Returns `true` on success or if there are no records to import,
	 *         return `false` on failure.
	 */
	public function create($drop = true) {
		return $this->_create($drop, false);
	}

	/**
	 * Create the fixture's schema and import records.
	 *
	 * @return boolean Returns `true` on success or if there are no records to import,
	 *         return `false` on failure.
	 */
	public function save($drop = true) {
		return $this->_create($drop, true);
	}

	/**
	 * Create the fixture's schema and import records.
	 *
	 * @param boolean $drop If `true` drop the fixture before creating it
	 * @param boolean $load If `true` load fixture's records
	 * @return boolean True on success, false on failure
	 */
	public function _create($drop = true, $save = true) {
		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);

		if ($drop && !$this->drop()) {
			return false;
		}

		$this->_alteredFields = $this->_alterFields($this->_fields);
		$return = true;

		if ($db::enabled('schema')) {
			$schema = Libraries::instance(null, 'schema', [
				'fields' => $this->_alteredFields,
				'meta' => $this->_meta,
				'locked' => $this->_locked
			], $this->_classes);

			$return = $db->createSchema($this->_source, $schema);
		}

		if ($return && $save) {
			foreach ($this->_records as $record) {
				if (!$this->populate($record, true)) {
					return false;
				}
			}
		}
		return $return;
	}

	/**
	 * Drop table for this fixture.
	 *
	 * @param boolean $soft If `true` and there's no existing schema, no drop query is generated.
	 * @return boolean True on success, false on failure
	 */
	public function drop($soft = true) {
		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);
		if (!$db::enabled('schema')) {
			return $this->truncate();
		}
		if ($soft && $db::enabled('sources')) {
			$sources = $db->sources();
			if(!in_array($this->_source, $sources)) {
				return true;
			}
		}
		return $db->dropSchema($this->_source);
	}

	/**
	 * Populate custom records in the database.
	 *
	 * @param array $record The data of the record
	 * @param boolean $alter If true, the `$record` will be altered according the alter rules.
	 * @return boolean Returns `true` on success `false` otherwise.
	 */
	public function populate(array $record = [], $alter = true) {
		if (!$record) {
			return true;
		}
		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);
		$data = $alter ? $this->_alterRecord($record) : $record;
		if ($this->_locked) {
			$data = array_intersect_key($data, $this->_alteredFields);
		}
		$options = [
			'type' => 'create', 'source' => $this->_source, 'data' => ['data' => $data]
		];
		$query = Libraries::instance(null, 'query', $options, $this->_classes);
		return $db->create($query);
	}

	/**
	 * Truncates the current fixture.
	 *
	 * @param boolean $soft If `true` and there's no existing schema, no truncate is generated.
	 * @return boolean
	 */
	public function truncate($soft = true) {
		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);
		if ($soft && $db::enabled('sources')) {
			$sources = $db->sources();
			if(!in_array($this->_source, $sources)) {
				return true;
			}
		}
		$options = ['source' => $this->_source];
		$query = Libraries::instance(null, 'query', $options, $this->_classes);
		return $db->delete($query);
	}

	public function alter($mode = null, $key = null, $value = []) {
		if ($mode === null) {
			return $this->_config['alters'];
		}
		if ($key && $mode === 'drop') {
			$this->_config['alters']['drop'][] = $key;
			return;
		}
		if ($key && $value) {
			$this->_config['alters'][$mode][$key] = $value;
		}
	}

	/**
	 * Apply the configured value mapping.
	 *
	 * @param array $record The record array.
	 * @return array Returns the modified record.
	 */
	public function _alterRecord(array $record = []) {
		$result = [];
		foreach ($record as $name => $value) {
			if (isset($this->_config['alters']['change'][$name])) {
				$alter = $this->_config['alters']['change'][$name];
				if (isset($alter['value'])) {
					$function = $alter['value'];
					$value = $function($record[$name]);
				} else {
					$value = $record[$name];
				}
				if (isset($alter['to'])) {
					$result[$alter['to']] = $value;
				} else {
					$result[$name] = $value;
				}
			} else {
				$result[$name] = $value;
			}
		}
		return $result;
	}

	public function _alterFields(array $fields = []) {
		foreach ($this->_config['alters'] as $mode => $values) {
			foreach ($values as $key => $value) {
				switch($mode) {
					case 'add':
						$fields[$key] = $value;
						break;
					case 'change':
						if (isset($fields[$key]) && isset($value['to'])) {
							$field = $fields[$key];
							unset($fields[$key]);
							$to = $value['to'];
							unset($value['to']);
							unset($value['value']);
							$fields[$to] = $value + $field;
						}
						break;
					case 'drop':
						unset($fields[$value]);
						break;
				}
			}
		}
		return $fields;
	}
}

?>
