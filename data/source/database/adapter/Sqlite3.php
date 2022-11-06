<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\source\database\adapter;

use PDOException;
use lithium\aop\Filters;
use lithium\core\ConfigException;
use lithium\core\Libraries;

/**
 * Sqlite (3) database driver. Extends the `Database` class to implement the necessary
 * SQL-formatting and resultset-fetching features for working with Sqlite databases.
 *
 * - Implements support for file based and in-memory databases.
 *
 * For more information on configuring the database connection, see
 * the `__construct()` method.
 *
 * @see lithium\data\source\database\adapter\Sqlite3::__construct()
 */
class Sqlite3 extends \lithium\data\source\Database {

	/**
	 * Pair of opening and closing quote characters used for quoting identifiers in queries.
	 *
	 * @link http://www.sqlite.org/lang_keywords.html
	 * @var array
	 */
	protected $_quotes = ['"', '"'];

	/**
	 * Sqlite3 column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = [
		'id' => ['use' => 'integer'],
		'string' => ['use' => 'varchar', 'length' => 255],
		'text' => ['use' => 'text'],
		'integer' => ['use' => 'integer', 'formatter' => 'intval'],
		'float' => ['use' => 'real', 'formatter' => 'floatval'],
		'datetime' => ['use' => 'text', 'format' => 'Y-m-d H:i:s'],
		'timestamp' => ['use' => 'text', 'format' => 'Y-m-d H:i:s'],
		'time' => ['use' => 'text', 'format' => 'H:i:s', 'formatter' => 'date'],
		'date' => ['use' => 'text', 'format' => 'Y-m-d', 'formatter' => 'date'],
		'binary' => ['use' => 'blob'],
		'boolean' => ['use' => 'boolean', 'length' => 1]
	];

	/**
	 * Column specific metas used on table creating
	 * By default `'quote'` is false and 'join' is `' '`
	 *
	 * @var array
	 */
	protected $_metas = [
		'column' => [
			'collate' => ['keyword' => 'COLLATE', 'escape' => true]
		]
	];
	/**
	 * Column contraints
	 *
	 * @var array
	 */
	protected $_constraints = [
		'primary' => ['template' => 'PRIMARY KEY ({:column})'],
		'foreign_key' => [
			'template' => 'FOREIGN KEY ({:column}) REFERENCES {:to} ({:toColumn}) {:on}'
		],
		'unique' => [
			'template' => 'UNIQUE {:index} ({:column})'
		],
		'check' => ['template' => 'CHECK ({:expr})']
	];

	/**
	 * Holds commonly regular expressions used in this class.
	 *
	 * @see lithium\data\source\database\adapter\Sqlite3::describe()
	 * @see lithium\data\source\database\adapter\Sqlite3::_column()
	 * @var array
	 */
	protected $_regex = [
		'column' => '(?P<type>[^(]+)(?:\((?P<length>[^)]+)\))?'
	];

	/**
	 * Check for required PHP extension, or supported database feature.
	 *
	 * @param string $feature Test for support for a specific feature, i.e. `'transactions'`.
	 * @return boolean Returns `true` if the particular feature (or if Sqlite) support is enabled,
	 *         otherwise `false`.
	 */
	public static function enabled($feature = null) {
		if (!$feature) {
			return extension_loaded('pdo_sqlite');
		}
		$features = [
			'arrays' => false,
			'transactions' => false,
			'booleans' => true,
			'schema' => true,
			'relationships' => true,
			'sources' => true
		];
		return isset($features[$feature]) ? $features[$feature] : null;
	}

	/**
	 * Constructor.
	 *
	 * @see lithium\data\source\Database::__construct()
	 * @see lithium\data\Source::__construct()
	 * @see lithium\data\Connections::add()
	 * @param array $config The available configuration options are the following. Further
	 *        options are inherited from the parent classes. Typically, these parameters are
	 *        set in `Connections::add()`, when adding the adapter to the list of active
	 *        connections.
	 *        - `'database'` _string_: Can be either a path to a database file or the special
	 *          `':memory'` string. Defaults to in-memory database `':memory:'`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['database' => ':memory:'];
		parent::__construct($config + $defaults);
	}

	/**
	 * Initializer. Constructs a DSN from configuration.
	 *
	 * @return void
	 */
	protected function _init() {
		$this->_config['dsn'] = sprintf("sqlite:%s", $this->_config['database']);
		parent::_init();
	}

	/**
	 * Disconnects the adapter from the database.
	 *
	 * @return boolean True on success, else false.
	 */
	public function disconnect() {
		if ($this->_isConnected) {
			unset($this->connection);
			$this->_isConnected = false;
		}
		return true;
	}

	/**
	 * Returns the list of tables in the currently-connected database.
	 *
	 * @param string $model The fully-name-spaced class name of the model object making the request.
	 * @return array Returns an array of objects to which models can connect.
	 * @filter
	 */
	public function sources($model = null) {
		$params = compact('model');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$sql = "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;";
			$result = $this->_execute($sql);
			$sources = [];

			foreach ($result as $row) {
				$sources[] = $row[0];
			}
			return $sources;
		});
	}

	/**
	 * Gets the column schema for a given Sqlite3 table.
	 *
	 * A column type may not always be available, i.e. when during creation of
	 * the column no type was declared. Those columns are internally treated
	 * by SQLite3 as having a `NONE` affinity. The final schema will contain no
	 * information about type and length of such columns (both values will be
	 * `null`).
	 *
	 * @param mixed $entity Specifies the table name for which the schema should be returned, or
	 *        the class name of the model object requesting the schema, in which case the model
	 *        class will be queried for the correct table name.
	 * @param array $fields Any schema data pre-defined by the model.
	 * @param array $meta
	 * @return array Returns an associative array describing the given table's schema, where the
	 *         array keys are the available fields, and the values are arrays describing each
	 *         field, containing the following keys:
	 *         - `'type'`: The field type name
	 * @filter
	 */
	public function describe($entity, $fields = [], array $meta = []) {
		$params = compact('entity', 'meta', 'fields');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			extract($params);

			if ($fields) {
				return Libraries::instance(null, 'schema', compact('fields'), $this->_classes);
			}
			$name = $this->_entityName($entity, ['quoted' => true]);
			$columns = $this->read("PRAGMA table_info({$name})", ['return' => 'array']);
			$fields = [];

			foreach ($columns as $column) {
				$schema = $this->_column($column['type']);
				$default = $column['dflt_value'];

				if (preg_match("/^'(.*)'/", $default ?? '', $match)) {
					$default = $match[1];
				} elseif ($schema['type'] === 'boolean') {
					$default = !!$default;
				} else {
					$default = null;
				}
				$fields[$column['name']] = $schema + [
					'null' => $column['notnull'] === '1',
					'default' => $default
				];
			}
			return Libraries::instance(null, 'schema', compact('fields'), $this->_classes);
		});
	}

	/**
	 * Gets the last auto-generated ID from the query that inserted a new record.
	 *
	 * @param object $query The `Query` object associated with the query which generated
	 * @return mixed Returns the last inserted ID key for an auto-increment column or a column
	 *         bound to a sequence.
	 */
	protected function _insertId($query) {
		return $this->connection->lastInsertId();
	}

	/**
	 * Getter/Setter for the connection's encoding.
	 *
	 * Sqlite uses the string `utf8` to identify the UTF-8 encoding. In general `UTF-8` is used
	 * to identify that encoding. This methods allows both strings to be used for _setting_ the
	 * encoding (in lower and uppercase, with or without dash) and will transparently convert
	 * to Sqlite native format. When _getting_ the encoding, it is converted back into `UTF-8`.
	 * So that this method should ever only return `UTF-8` when the encoding is used.
	 *
	 * @param null|string $encoding Either `null` to retrieve the current encoding, or
	 *        a string to set the current encoding to. For UTF-8 accepts any permutation.
	 * @return string|boolean When $encoding is `null` returns the current encoding
	 *         in effect, otherwise a boolean indicating if setting the encoding
	 *         succeeded or failed. Returns `'UTF-8'` when this encoding is used.
	 */
	public function encoding($encoding = null) {
		if ($encoding === null) {
			$encoding = $this->connection
				->query('PRAGMA encoding')
				->fetchColumn();

			return $encoding === 'utf8' ? 'UTF-8' : $encoding;
		}
		if (strcasecmp($encoding, 'utf-8') === 0 || strcasecmp($encoding, 'utf8') === 0) {
			$encoding = 'utf8';
		}
		return $this->connection->exec("PRAGMA encoding = \"{$encoding}\"") !== false;
	}

	/**
	 * Retrieves database error message and error code.
	 *
	 * @return array
	 */
	public function error() {
		if ($error = $this->connection->errorInfo()) {
			return [$error[1], $error[2]];
		}
	}

	/**
	 * Execute a given query.
	 *
	 * @see lithium\data\source\Database::renderCommand()
	 * @param string $sql The sql string to execute
	 * @param array $options No available options.
	 * @return \lithium\data\source\Result Returns a result object if the query was successful.
	 * @filter
	 */
	protected function _execute($sql, array $options = []) {
		$params = compact('sql', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			try {
				$resource = $this->connection->query($params['sql']);
			} catch (PDOException $e) {
				$this->_error($params['sql']);
			};
			return Libraries::instance(null, 'result', compact('resource'), $this->_classes);
		});
	}

	/**
	 * Converts database-layer column types to basic types.
	 *
	 * @param string $real Real database-layer column type (i.e. "varchar(255)")
	 * @return string Abstract column type (i.e. "string")
	 */
	protected function _column($real) {
		if (is_array($real)) {
			return $real['type'] . (isset($real['length']) ? "({$real['length']})" : '');
		}

		if (!preg_match("/{$this->_regex['column']}/", $real, $column)) {
			return $real;
		}

		$column = array_intersect_key($column, ['type' => null, 'length' => null]);
		if (isset($column['length']) && $column['length']) {
			$length = explode(',', $column['length']) + [null, null];
			$column['length'] = $length[0] ? (integer) $length[0] : null;
			$length[1] ? $column['precision'] = (integer) $length[1] : null;
		}

		switch (true) {
			case in_array($column['type'], ['date', 'time', 'datetime', 'timestamp']):
				return $column;
			case ($column['type'] === 'tinyint' && $column['length'] == '1'):
			case ($column['type'] === 'boolean'):
				return ['type' => 'boolean'];
			break;
			case (strpos($column['type'], 'int') !== false):
				$column['type'] = 'integer';
			break;
			case (strpos($column['type'], 'char') !== false):
				$column['type'] = 'string';
				$column['length'] = 255;
			break;
			case (strpos($column['type'], 'text') !== false):
				$column['type'] = 'text';
			break;
			case (strpos($column['type'], 'blob') !== false || $column['type'] === 'binary'):
				$column['type'] = 'binary';
			break;
			case preg_match('/real|float|double|decimal/', $column['type']):
				$column['type'] = 'float';
			break;
			default:
				$column['type'] = 'text';
			break;
		}
		return $column;
	}

	/**
	 * Helper for `Database::column()`.
	 *
	 * @see lithium\data\Database::column()
	 * @param array $field A field array.
	 * @return string SQL column string.
	 */
	protected function _buildColumn($field) {
		extract($field);

		if ($type === 'float' && $precision) {
			$use = 'numeric';
		}

		$out = $this->name($name) . ' ' . $use;

		$allowPrecision = preg_match('/^(integer|real|numeric)$/',$use);
		$precision = ($precision && $allowPrecision) ? ",{$precision}" : '';

		if ($length && ($allowPrecision || $use === 'text')) {
			$out .= "({$length}{$precision})";
		}

		$out .= $this->_buildMetas('column', $field, ['collate']);

		if ($type !== 'id') {
			$out .= is_bool($null) ? ($null ? ' NULL' : ' NOT NULL') : '' ;
			$out .= $default ? ' DEFAULT ' . $this->value($default, $field) : '';
		}

		return $out;
	}
}

?>