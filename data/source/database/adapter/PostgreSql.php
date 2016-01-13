<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright	  Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license		  http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter;

use PDO;
use PDOException;
use lithium\aop\Filters;

/**
 * PostgreSQL database driver. Extends the `Database` class to implement the necessary
 * SQL-formatting and resultset-fetching features for working with PostgreSQL databases.
 *
 * - Implements timezone support.
 * - Implements schema/searchPath support.
 *
 * For more information on configuring the database connection, see
 * the `__construct()` method.
 *
 * @see lithium\data\source\database\adapter\PostgreSql::timezone()
 * @see lithium\data\source\database\adapter\PostgreSql::searchPath()
 * @see lithium\data\source\database\adapter\PostgreSql::__construct()
 */
class PostgreSql extends \lithium\data\source\Database {

	/**
	 * PostgreSQL column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = array(
		'id' => array('use' => 'integer', 'increment' => true),
		'string' => array('use' => 'varchar', 'length' => 255),
		'text' => array('use' => 'text'),
		'integer' => array('use' => 'integer', 'formatter' => 'intval'),
		'float' => array('use' => 'real', 'formatter' => 'floatval'),
		'datetime' => array('use' => 'timestamp', 'format' => 'Y-m-d H:i:s'),
		'timestamp' => array('use' => 'timestamp', 'format' => 'Y-m-d H:i:s'),
		'time' => array('use' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('use' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('use' => 'bytea'),
		'boolean' => array('use' => 'boolean'),
		'inet' => array('use' => 'inet')
	);

	/**
	 * Column/table metas
	 * By default `'escape'` is false and 'join' is `' '`
	 *
	 * @var array
	 */
	protected $_metas = array(
		'table' => array(
			'tablespace' => array('keyword' => 'TABLESPACE')
		)
	);

	/**
	 * Column contraints
	 *
	 * @var array
	 */
	protected $_constraints = array(
		'primary' => array('template' => 'PRIMARY KEY ({:column})'),
		'foreign_key' => array(
			'template' => 'FOREIGN KEY ({:column}) REFERENCES {:to} ({:toColumn}) {:on}'
		),
		'unique' => array(
			'template' => 'UNIQUE {:index} ({:column})'
		),
		'check' => array('template' => 'CHECK ({:expr})')
	);

	/**
	 * Pair of opening and closing quote characters used for quoting identifiers in queries.
	 *
	 * @var array
	 */
	protected $_quotes = array('"', '"');

	/**
	 * Constructor. Constructs the PostgreSQL adapter and sets the default port to 5432.
	 *
	 * @see lithium\data\source\Database::__construct()
	 * @see lithium\data\Source::__construct()
	 * @see lithium\data\Connections::add()
	 * @param array $config The available configuration options are the following. Further
	 *        options are inherited from the parent classes. Typically, these parameters are
	 *        set in `Connections::add()`, when adding the adapter to the list of active
	 *        connections.
	 *        - `'host'` _string_: Defaults to `'localhost:5432'`.
	 *        - `'schema'` _string_: The name of the database schema to use. Defaults to `'public'`.
	 *        - `'timezone'` _string_: The timezone to use. Defaults to `'null'`
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'host' => 'localhost:5432',
			'schema' => 'public',
			'timezone' => null
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Check for required PHP extension, or supported database feature.
	 *
	 * @param string $feature Test for support for a specific feature, i.e. `"transactions"` or
	 *        `"arrays"`.
	 * @return boolean Returns `true` if the particular feature (or if PostgreSQL) support is
	 *         enabled, otherwise `false`.
	 */
	public static function enabled($feature = null) {
		if (!$feature) {
			return extension_loaded('pdo_pgsql');
		}
		$features = array(
			'arrays' => false,
			'transactions' => true,
			'booleans' => true,
			'schema' => true,
			'relationships' => true,
			'sources' => true
		);
		return isset($features[$feature]) ? $features[$feature] : null;
	}

	/**
	 * Connects to the database by constructing DSN string and creating a PDO intance using
	 * the parent class. Will set specific options on the connection as provided (timezone,
	 * schema).
	 *
	 * @see lithium\data\source\dataase\adapter\PostgreSql::timezone()
	 * @return boolean Returns `true` if a database connection could be established,
	 *         otherwise `false`.
	 */
	public function connect() {
		if (!$this->_config['dsn']) {
			$host = $this->_config['host'];
			list($host, $port) = explode(':', $host) + array(1 => "5432");
			$dsn = "pgsql:host=%s;port=%s;dbname=%s";
			$this->_config['dsn'] = sprintf($dsn, $host, $port, $this->_config['database']);
		}

		if (!parent::connect()) {
			return false;
		}

		if ($this->_config['schema']) {
			$this->searchPath($this->_config['schema']);
		}

		if ($this->_config['timezone']) {
			$this->timezone($this->_config['timezone']);
		}
		return true;
	}

	/**
	 * Returns the list of tables in the currently-connected database.
	 *
	 * @param string $model The fully-name-spaced class name of the model object making the request.
	 * @return array Returns an array of sources to which models can connect.
	 * @filter
	 */
	public function sources($model = null) {
		$params = compact('model');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$schema = $this->connection->quote($this->_config['schema']);

			$sql  = "SELECT table_name as name FROM INFORMATION_SCHEMA.tables";
			$sql .= " WHERE table_schema = {$schema}";

			if (!$result = $this->_execute($sql)) {
				return null;
			}
			$sources = array();

			foreach ($result as $row) {
				$sources[] = $row[0];
			}
			return $sources;
		});
	}

	/**
	 * Gets the column schema for a given PostgreSQL table.
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
	public function describe($entity, $fields = array(), array $meta = array()) {
		$schema = $this->_config['schema'];
		$params = compact('entity', 'meta', 'fields', 'schema');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			extract($params);

			if ($fields) {
				return $this->_instance('schema', compact('fields'));
			}
			$name = $this->connection->quote($this->_entityName($entity));
			$schema = $this->connection->quote($schema);

			$sql  = 'SELECT "column_name" AS "field", "data_type" AS "type",	';
			$sql .= '"is_nullable" AS "null", "column_default" AS "default", ';
			$sql .= '"character_maximum_length" AS "char_length" ';
			$sql .= 'FROM "information_schema"."columns" WHERE "table_name" = ' . $name;
			$sql .= ' AND table_schema = ' . $schema . ' ORDER BY "ordinal_position"';

			$columns = $this->connection->query($sql)->fetchAll(PDO::FETCH_ASSOC);
			$fields = array();

			foreach ($columns as $column) {
				$schema = $this->_column($column['type']);
				$default = $column['default'];

				if (preg_match("/^'(.*)'::/", $default, $match)) {
					$default = $match[1];
				} elseif ($default === 'true') {
					$default = true;
				} elseif ($default === 'false') {
					$default = false;
				} else {
					$default = null;
				}
				$fields[$column['field']] = $schema + array(
					'null'     => ($column['null'] === 'YES' ? true : false),
					'default'  => $default
				);
				if ($fields[$column['field']]['type'] === 'string') {
					$fields[$column['field']]['length'] = $column['char_length'];
				}
			}
			return $this->_instance('schema', compact('fields'));
		});
	}

	/**
	 * Getter/Setter for the connection's search path.
	 *
	 * @param null|string $searchPath Either `null` to retrieve the current one, or
	 *        a string to set the current one to.
	 * @return string|boolean When $searchPath is `null` returns the current search path
	 *         in effect, otherwise a boolean indicating if setting the search path
	 *         succeeded or failed.
	 */
	public function searchPath($searchPath = null) {
		if ($searchPath === null) {
			return explode(',', $this->connection->query('SHOW search_path')->fetchColumn(1));
		}
		return $this->connection->exec("SET search_path TO {$searchPath}") !== false;
	}

	/**
	 * Getter/Setter for the connection's timezone.
	 *
	 * @param null|string $timezone Either `null` to retrieve the current TZ, or
	 *        a string to set the current TZ to.
	 * @return string|boolean When $timezone is `null` returns the current TZ
	 *         in effect, otherwise a boolean indicating if setting the TZ
	 *         succeeded or failed.
	 */
	public function timezone($timezone = null) {
		if ($timezone === null) {
			return $this->connection->query('SHOW TIME ZONE')->fetchColumn();
		}
		return $this->connection->exec("SET TIME ZONE '{$timezone}'") !== false;
	}

	/**
	 * Getter/Setter for the connection's encoding.
	 *
	 * PostgreSQL uses the string `UTF8` to identify the UTF-8 encoding. In general `UTF-8` is used
	 * to identify that encoding. This methods allows both strings to be used for _setting_ the
	 * encoding (in lower and uppercase, with or without dash) and will transparently convert
	 * to PostgreSQL native format. When _getting_ the encoding, it is converted back into `UTF-8`.
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
				->query('SHOW client_encoding')
				->fetchColumn();

			return $encoding === 'UTF8' ? 'UTF-8' : $encoding;
		}
		if (stripos($encoding, 'utf-8') !== false || stripos($encoding, 'utf8') !== false) {
			$encoding = 'UTF8';
		}
		return $this->connection->exec("SET NAMES '{$encoding}'") !== false;
	}

	/**
	 * Converts a given value into the proper type based on a given schema definition.
	 *
	 * @see lithium\data\source\Database::schema()
	 * @param mixed $value The value to be converted. Arrays will be recursively converted.
	 * @param array $schema Formatted array from `lithium\data\source\Database::schema()`
	 * @return mixed Value with converted type.
	 */
	public function value($value, array $schema = array()) {
		if (($result = parent::value($value, $schema)) !== null) {
			return $result;
		}
		return $this->connection->quote((string) $value);
	}

	/**
	 * Retrieves database error message and error code.
	 *
	 * @return array
	 */
	public function error() {
		if ($error = $this->connection->errorInfo()) {
			return array($error[1], $error[2]);
		}
		return null;
	}

	public function alias($alias, $context) {
		if ($context->type() === 'update' || $context->type() === 'delete') {
			return;
		}
		return parent::alias($alias, $context);
	}

	/**
	 * @todo Eventually, this will need to rewrite aliases for DELETE and UPDATE queries, same with
	 *       order().
	 * @param string $conditions
	 * @param string $context
	 * @param array $options
	 * @return string
	 */
	public function conditions($conditions, $context, array $options = array()) {
		return parent::conditions($conditions, $context, $options);
	}

	/**
	 * Execute a given query.
	 *
	 * @see lithium\data\source\Database::renderCommand()
	 * @param string $sql The sql string to execute
	 * @param array $options Available options:
	 * @return \lithium\data\source\Result Returns a result object if the query was successful.
	 * @filter
	 */
	protected function _execute($sql, array $options = array()) {
		$params = compact('sql', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			try {
				$resource = $this->connection->query($params['sql']);
			} catch (PDOException $e) {
				$this->_error($params['sql']);
			};
			return $this->_instance('result', compact('resource'));
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
		$model = $query->model();
		$field = $model::key();
		$source = $model::meta('source');
		$sequence = "{$source}_{$field}_seq";
		$id = $this->connection->lastInsertId($sequence);
		return ($id && $id !== '0') ? $id : null;
	}

	/**
	 * Converts database-layer column types to basic types.
	 *
	 * @param string $real Real database-layer column type (i.e. `"varchar(255)"`)
	 * @return array Column type (i.e. "string") plus 'length' when appropriate.
	 */
	protected function _column($real) {
		if (is_array($real)) {
			return $real['type'] . (isset($real['length']) ? "({$real['length']})" : '');
		}

		if (!preg_match('/(?P<type>\w+)(?:\((?P<length>[\d,]+)\))?/', $real, $column)) {
			return $real;
		}
		$column = array_intersect_key($column, array('type' => null, 'length' => null));

		if (isset($column['length']) && $column['length']) {
			$length = explode(',', $column['length']) + array(null, null);
			$column['length'] = $length[0] ? (integer) $length[0] : null;
			$length[1] ? $column['precision'] = (integer) $length[1] : null;
		}

		switch (true) {
			case in_array($column['type'], array('date', 'time', 'datetime')):
				return $column;
			case ($column['type'] === 'timestamp'):
				$column['type'] = 'datetime';
			break;
			case ($column['type'] === 'tinyint' && $column['length'] == '1'):
			case ($column['type'] === 'boolean'):
				return array('type' => 'boolean');
			break;
			case (strpos($column['type'], 'int') !== false):
				$column['type'] = 'integer';
			break;
			case (strpos($column['type'], 'char') !== false || $column['type'] === 'tinytext'):
				$column['type'] = 'string';
			break;
			case (strpos($column['type'], 'text') !== false):
				$column['type'] = 'text';
			break;
			case (strpos($column['type'], 'blob') !== false || $column['type'] === 'binary'):
				$column['type'] = 'binary';
			break;
			case preg_match('/float|double|decimal/', $column['type']):
				$column['type'] = 'float';
			break;
			default:
				$column['type'] = 'text';
			break;
		}
		return $column;
	}

	/**
	 * Provide an associative array of Closures to be used as the "formatter" key inside of the
	 * `Database::$_columns` specification.
	 *
	 * @see lithium\data\source\Database::_formatters()
	 */
	protected function _formatters() {
		$datetime = $timestamp = function($format, $value) {
			if ($format && (($time = strtotime($value)) !== false)) {
				$val = date($format, $time);
				if (!preg_match('/^' . preg_quote($val) . '\.\d+$/', $value)) {
					$value = $val;
				}
			}
			return $this->connection->quote($value);
		};

		return compact('datetime', 'timestamp') + array(
			'boolean' => function($value) {
				return $this->connection->quote($value ? 't' : 'f');
			}
		) + parent::_formatters();
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

		if ($precision) {
			$precision = $use === 'numeric' ? ",{$precision}" : '';
		}

		$out = $this->name($name);

		if (isset($increment) && $increment) {
			$out .= ' serial NOT NULL';
		} else {
			$out .= ' ' . $use;

			if ($length && preg_match('/char|numeric|interval|bit|time/',$use)) {
				$out .= "({$length}{$precision})";
			}

			$out .= is_bool($null) ? ($null ? ' NULL' : ' NOT NULL') : '' ;
			$out .= $default ? ' DEFAULT ' . $this->value($default, $field) : '';
		}

		return $out;
	}

	/**
	 * Helper method for `PostgreSql::_quryExport()` to export data
	 * for use in distinct query.
	 *
	 * @see lithium\data\source\PostgreSql::_quryExport()
	 * @param object $query The query object.
	 * @return array Returns an array with the fields as the first
	 *         value and the orders as the second value.
	 */
	protected function _distinctExport($query) {
		$model = $query->model();
		$orders = $query->order();
		$result = array(
			'fields' => array(),
			'orders' => array(),
		);

		if (is_string($orders)) {
			$direction = 'ASC';
			if (preg_match('/^(.*?)\s+((?:A|DE)SC)$/i', $orders, $match)) {
				$orders = $match[1];
				$direction = $match[2];
			}
			$orders = array($orders => $direction);
		}

		if (!is_array($orders)) {
			return array_values($result);
		}

		foreach ($orders as $column => $dir) {
			if (is_int($column)) {
				$column = $dir;
				$dir = 'ASC';
			}

			if ($model && $model::schema($column)) {
				$name = $this->name($query->alias()) . '.' . $this->name($column);
				$alias = $this->name('_' . $query->alias() . '_' . $column . '_');
			} else {
				list($alias, $field) = $this->_splitFieldname($column);
				$name = $this->name($column);
				$alias = $this->name('_' . $alias . '_' . $field . '_');
			}

			$result['fields'][] = "{$name} AS {$alias}";
			$result['orders'][] = "{$alias} {$dir}";
		}
		return array_values($result);
	}

	/**
	 * Helper method for `Database::read()` to export query while handling additional joins
	 * when using relationships and limited result sets. Filters conditions on subsequent
	 * queries to just the ones applying to the relation.
	 *
	 * @see lithium\data\source\Database::read()
	 * @param object $query The query object.
	 * @return array The exported query returned by reference.
	 */
	protected function &_queryExport($query) {
		$data = $query->export($this);

		if (!$query->limit() || !($model = $query->model())) {
			return $data;
		}

		foreach ($query->relationships() as $relation) {
			if ($relation['type'] !== 'hasMany') {
				continue;
			}
			$pk = $this->name($model::meta('name') . '.' . $model::key());

			if ($query->order()) {
				list($fields, $orders) = $this->_distinctExport($query);
				array_unshift($fields, "DISTINCT ON($pk) $pk AS _ID_");

				$command = $this->renderCommand('read', array(
					'limit' => null, 'order' => null,
					'fields' => implode(', ', $fields)
				) + $data);
				$command = rtrim($command, ';');

				$command = $this->renderCommand('read', array(
					'source' => "( $command ) AS _TEMP_",
					'fields' => '_ID_',
					'order' => "ORDER BY " . implode(', ', $orders),
					'limit' => $data['limit'],
				));
			} else {
				$command = $this->renderCommand('read', array(
					'fields' => "DISTINCT({$pk}) AS _ID_"
				) + $data);
			}

			$result = $this->_execute($command);
			$ids = array();

			foreach ($result as $row) {
				$ids[] = $row[0];
			}
			if (!$ids) {
				$data = null;
				break;
			}

			$conditions = array();
			$relations = array_keys($query->relationships());
			$pattern = '/^(' . implode('|', $relations) . ')\./';
			foreach ($query->conditions() as $key => $value) {
				if (preg_match($pattern, $key)) {
					$conditions[$key] = $value;
				}
			}
			$data['conditions'] = $this->conditions(
				array($pk => $ids) + $conditions, $query
			);

			$data['limit'] = '';
			break;
		}
		return $data;
	}
}

?>