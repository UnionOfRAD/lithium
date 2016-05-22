<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter;

use PDO;
use PDOException;
use lithium\aop\Filters;
use lithium\core\ConfigException;
use lithium\net\HostString;

/**
 * MySQL database driver. Extends the `Database` class to implement the necessary
 * SQL-formatting and resultset-fetching features for working with MySQL databases.
 *
 * - Implements optional strict mode.
 *
 * For more information on configuring the database connection, see
 * the `__construct()` method.
 *
 * @see lithium\data\source\database\adapter\MySql::__construct()
 * @see lithium\data\source\database\adapter\MySql::strict()
 */
class MySql extends \lithium\data\source\Database {

	/**
	 * The default host used to connect to the server.
	 */
	const DEFAULT_HOST = 'localhost';

	/**
	 * The default port used to connect to the server.
	 */
	const DEFAULT_PORT = 3306;

	/**
	 * MySQL column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = array(
		'id' => array('use' => 'int', 'length' => 11, 'increment' => true),
		'string' => array('use' => 'varchar', 'length' => 255),
		'text' => array('use' => 'text'),
		'integer' => array('use' => 'int', 'length' => 11, 'formatter' => 'intval'),
		'float' => array('use' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('use' => 'datetime', 'format' => 'Y-m-d H:i:s'),
		'timestamp' => array('use' => 'timestamp', 'format' => 'Y-m-d H:i:s'),
		'time' => array('use' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('use' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('use' => 'blob'),
		'boolean' => array('use' => 'tinyint', 'length' => 1)
	);

	/**
	 * Meta atrribute syntax.
	 *
	 * By default `'escape'` is false and 'join' is `' '`.
	 *
	 * @var array
	 */
	protected $_metas = array(
		'column' => array(
			'charset' => array('keyword' => 'CHARACTER SET'),
			'collate' => array('keyword' => 'COLLATE'),
			'comment' => array('keyword' => 'COMMENT', 'escape' => true)
		),
		'table' => array(
			'charset' => array('keyword' => 'DEFAULT CHARSET'),
			'collate' => array('keyword' => 'COLLATE'),
			'engine' => array('keyword' => 'ENGINE'),
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
		'index' => array('template' => 'INDEX ({:column})'),
		'unique' => array(
			'template' => 'UNIQUE {:index} ({:column})',
			'key' => 'KEY',
			'index' => 'INDEX'
		),
		'check' => array('template' => 'CHECK ({:expr})')
	);

	/**
	 * Pair of opening and closing quote characters used for quoting identifiers in queries.
	 *
	 * @var array
	 */
	protected $_quotes = array('`', '`');

	/**
	 * Check for required PHP extension, or supported database feature.
	 *
	 * @param string $feature Test for support for a specific feature, i.e. `"transactions"` or
	 *        `"arrays"`.
	 * @return boolean Returns `true` if the particular feature (or if MySQL) support is enabled,
	 *         otherwise `false`.
	 */
	public static function enabled($feature = null) {
		if (!$feature) {
			return extension_loaded('pdo_mysql');
		}
		$features = array(
			'arrays' => false,
			'transactions' => false,
			'booleans' => true,
			'schema' => true,
			'relationships' => true,
			'sources' => true
		);
		return isset($features[$feature]) ? $features[$feature] : null;
	}

	/**
	 * Constructor.
	 *
	 * @link http://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sql-mode-strict
	 * @see lithium\data\source\Database::__construct()
	 * @see lithium\data\Source::__construct()
	 * @see lithium\data\Connections::add()
	 * @param array $config The available configuration options are the following. Further
	 *        options are inherited from the parent classes. Typically, these parameters are
	 *        set in `Connections::add()`, when adding the adapter to the list of active
	 *        connections.
	 *        - `'host'` _string_: A string in the form of `'<host>'`, `'<host>:<port>'` or
	 *          `':<port>'` indicating the host and/or port to connect to. When one or both are
	 *          not provided uses general server defaults.
	 *          To use Unix sockets specify the path to the socket (i.e. `'/path/to/socket'`).
	 *        - `'strict'` _boolean|null_: When `true` will enable strict mode by setting
	 *          sql-mode to `STRICT_ALL_TABLES`. When `false` will disable strict mode
	 *          explictly by settings sql-mode to an empty value ``. A value of `null`
	 *          leaves the setting untouched (this is the default) and the default setting
	 *          of the database is used.
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'host' => static::DEFAULT_HOST . ':' . static::DEFAULT_PORT,
			'strict' => null
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Initializer. Adds MySQL-specific operators to `$_operators`. Constructs
	 * a DSN from configuration.
	 *
	 * @see lithium\data\source\database\adapter\MySql::$_operators
	 * @see lithium\data\source\Database::$_operators
	 * @return void
	 */
	protected function _init() {
		if (!$this->_config['host']) {
			throw new ConfigException('No host configured.');
		}

		if (HostString::isSocket($this->_config['host'])) {
			$this->_config['dsn'] = sprintf(
				'mysql:unix_socket=%s;dbname=%s',
				$this->_config['host'],
				$this->_config['database']
			);
		} else {
			$host = HostString::parse($this->_config['host']) + array(
				'host' => static::DEFAULT_HOST,
				'port' => static::DEFAULT_PORT
			);
			$this->_config['dsn'] = sprintf(
				'mysql:host=%s;port=%s;dbname=%s',
				$host['host'],
				$host['port'],
				$this->_config['database']
			);
		}
		parent::_init();

		$this->_operators += array(
			'REGEXP' => array(),
			'NOT REGEXP' => array(),
			'SOUNDS LIKE' => array()
		);
	}

	/**
	 * Connects to the database by creating a PDO intance using the constructed DSN string.
	 * Will set specific options on the connection as provided.
	 *
	 * @return boolean Returns `true` if a database connection could be established,
	 *         otherwise `false`.
	 */
	public function connect() {
		if (!parent::connect()) {
			return false;
		}
		if ($this->_config['strict'] !== null && !$this->strict($this->_config['strict'])) {
			return false;
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
			$name = $this->name($this->_config['database']);

			if (!$result = $this->_execute("SHOW TABLES FROM {$name};")) {
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
	 * Gets the column schema for a given MySQL table.
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
	public function describe($entity,  $fields = array(), array $meta = array()) {
		$params = compact('entity', 'meta', 'fields');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			extract($params);

			if ($fields) {
				return $this->_instance('schema', compact('fields'));
			}
			$name = $this->_entityName($entity, array('quoted' => true));
			$columns = $this->read("DESCRIBE {$name}", array('return' => 'array', 'schema' => array(
				'field', 'type', 'null', 'key', 'default', 'extra'
			)));
			$fields = array();

			foreach ($columns as $column) {
				$schema = $this->_column($column['type']);
				$default = $column['default'];

				if ($default === 'CURRENT_TIMESTAMP') {
					$default = null;
				} elseif ($schema['type'] === 'boolean') {
					$default = !!$default;
				}
				$fields[$column['field']] = $schema + array(
					'null'     => ($column['null'] === 'YES' ? true : false),
					'default'  => $default
				);
			}
			return $this->_instance('schema', compact('fields'));
		});
	}

	/**
	 * Getter/Setter for the connection's encoding.
	 *
	 * MySQL uses the string `utf8` to identify the UTF-8 encoding. In general `UTF-8` is used
	 * to identify that encoding. This methods allows both strings to be used for _setting_ the
	 * encoding (in lower and uppercase, with or without dash) and will transparently convert
	 * to MySQL native format. When _getting_ the encoding, it is converted back into `UTF-8`.
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
				->query("SHOW VARIABLES LIKE 'character_set_client'")
				->fetchColumn(1);

			return $encoding === 'utf8' ? 'UTF-8' : $encoding;
		}
		if (strcasecmp($encoding, 'utf-8') === 0 || strcasecmp($encoding, 'utf8') === 0) {
			$encoding = 'utf8';
		}
		return $this->connection->exec("SET NAMES '{$encoding}'") !== false;
	}

	/**
	 * Enables/disables or retrieves strictness setting.
	 *
	 * This method will only operate on _session_ level, it will not check/set
	 * _global_ settings. `STRICT_ALL_TABLES` mode is used to enable strict mode.
	 *
	 * @link http://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sql-mode-strict
	 * @param boolean|null $value `true` to enable strict mode, `false` to disable or `null`
	 *        to retrieve the current setting.
	 * @return boolean When setting, returns `true` on success, else `false`. When $value was
	 *         `null` return either `true` or `false` indicating whether strict mode is enabled
	 *         or disabled.
	 */
	public function strict($value = null) {
		if ($value === null) {
			return strpos(
				$this->connection->query('SELECT @@SESSION.sql_mode')->fetchColumn(),
				'STRICT_ALL_TABLES'
			) !== false;
		}
		if ($value) {
			$sql = "SET SESSION sql_mode = 'STRICT_ALL_TABLES'";
		} else {
			$sql = "SET SESSION sql_mode = ''";
		}
		return $this->connection->exec($sql) !== false;
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
	}

	public function alias($alias, $context) {
		if ($context->type() === 'update' || $context->type() === 'delete') {
			return;
		}
		return parent::alias($alias, $context);
	}

	/**
	 * Execute a given query.
	 *
	 * @see lithium\data\source\Database::renderCommand()
	 * @param string $sql The sql string to execute
	 * @param array $options Available options:
	 *        - 'buffered': If set to `false` uses mysql_unbuffered_query which
	 *          sends the SQL query query to MySQL without automatically fetching and buffering the
	 *          result rows as `mysql_query()` does (for less memory usage).
	 * @return \lithium\data\source\Result Returns a result object if the query was successful.
	 * @filter
	 */
	protected function _execute($sql, array $options = array()) {
		$defaults = array('buffered' => true);
		$options += $defaults;

		$params = compact('sql', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$this->connection->setAttribute(
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
				$params['options']['buffered']
			);
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
		$row = $this->_execute('SELECT LAST_INSERT_ID() AS insertID')->current();
		return ($row[0] && $row[0] !== '0') ? $row[0] : null;
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
			case in_array($column['type'], array('date', 'time', 'datetime', 'timestamp')):
				return $column;
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
	 * Helper for `Database::column()`
	 *
	 * @see lithium\data\Database::column()
	 * @param array $field A field array.
	 * @return string The SQL column string.
	 */
	protected function _buildColumn($field) {
		extract($field);

		if ($type === 'float' && $precision) {
			$use = 'decimal';
		}

		$out = $this->name($name) . ' ' . $use;

		$allowPrecision = preg_match('/^(decimal|float|double|real|numeric)$/',$use);
		$precision = ($precision && $allowPrecision) ? ",{$precision}" : '';

		if ($length && ($allowPrecision || preg_match('/(char|binary|int|year)/',$use))) {
			$out .= "({$length}{$precision})";
		}

		$out .= $this->_buildMetas('column', $field, array('charset', 'collate'));

		if (isset($increment) && $increment) {
			$out .= ' NOT NULL AUTO_INCREMENT';
		} else {
			$out .= is_bool($null) ? ($null ? ' NULL' : ' NOT NULL') : '' ;
			$out .= $default ? ' DEFAULT ' . $this->value($default, $field) : '';
		}

		return $out . $this->_buildMetas('column', $field, array('comment'));
	}
}

?>