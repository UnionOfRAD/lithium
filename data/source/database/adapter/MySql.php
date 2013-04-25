<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter;

use PDO;
use PDOException;

/**
 * Extends the `Database` class to implement the necessary SQL-formatting and resultset-fetching
 * features for working with MySQL databases.
 *
 * For more information on configuring the database connection, see the `__construct()` method.
 *
 * @see lithium\data\source\database\adapter\MySql::__construct()
 */
class MySql extends \lithium\data\source\Database {

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
	 * Meta atrribute syntax
	 * By default `'escape'` is false and 'join' is `' '`
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
	 * MySQL-specific value denoting whether or not table aliases should be used in DELETE and
	 * UPDATE queries.
	 *
	 * @var boolean
	 */
	protected $_useAlias = true;

	/**
	 * Constructs the MySQL adapter and sets the default port to 3306.
	 *
	 * @see lithium\data\source\Database::__construct()
	 * @see lithium\data\Source::__construct()
	 * @see lithium\data\Connections::add()
	 * @param array $config Configuration options for this class. For additional configuration,
	 *        see `lithium\data\source\Database` and `lithium\data\Source`. Available options
	 *        defined by this class:
	 *        - `'database'`: The name of the database to connect to. Defaults to 'lithium'.
	 *        - `'host'`: The IP or machine name where MySQL is running, followed by a colon,
	 *          followed by a port number or socket. Defaults to `'localhost:3306'`.
	 *        - `'persistent'`: If a persistent connection (if available) should be made.
	 *          Defaults to true.
	 *        Typically, these parameters are set in `Connections::add()`, when adding the
	 *        adapter to the list of active connections.
	 */
	public function __construct(array $config = array()) {
		$defaults = array('host' => 'localhost:3306', 'encoding' => null);
		parent::__construct($config + $defaults);
	}

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
	 * Connects to the database using the options provided to the class constructor.
	 *
	 * @return boolean Returns `true` if a database connection could be established, otherwise
	 *         `false`.
	 */
	public function connect() {
		if (!$this->_config['dsn']) {
			$host = $this->_config['host'];
			list($host, $port) = explode(':', $host) + array(1 => "3306");
			$dsn = "mysql:host=%s;port=%s;dbname=%s";
			$this->_config['dsn'] = sprintf($dsn, $host, $port, $this->_config['database']);
		}

		if (!parent::connect()) {
			return false;
		}

		$info = $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
		$this->_useAlias = (boolean) version_compare($info, "4.1", ">=");
		return true;
	}

	/**
	 * Returns the list of tables in the currently-connected database.
	 *
	 * @param string $model The fully-name-spaced class name of the model object making the request.
	 * @return array Returns an array of sources to which models can connect.
	 * @filter This method can be filtered.
	 */
	public function sources($model = null) {
		$_config = $this->_config;
		$params = compact('model');

		return $this->_filter(__METHOD__, $params, function($self, $params) use ($_config) {
			$name = $self->name($_config['database']);

			if (!$result = $self->invokeMethod('_execute', array("SHOW TABLES FROM {$name};"))) {
				return null;
			}
			$sources = array();

			while ($data = $result->next()) {
				$sources[] = array_shift($data);
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
	 * @filter This method can be filtered.
	 */
	public function describe($entity,  $fields = array(), array $meta = array()) {
		$params = compact('entity', 'meta', 'fields');
		return $this->_filter(__METHOD__, $params, function($self, $params) {
			extract($params);

			if ($fields) {
				return $self->invokeMethod('_instance', array('schema', compact('fields')));
			}
			$name = $self->invokeMethod('_entityName', array($entity, array('quoted' => true)));
			$columns = $self->read("DESCRIBE {$name}", array('return' => 'array', 'schema' => array(
				'field', 'type', 'null', 'key', 'default', 'extra'
			)));
			$fields = array();

			foreach ($columns as $column) {
				$schema = $self->invokeMethod('_column', array($column['type']));
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
			return $self->invokeMethod('_instance', array('schema', compact('fields')));
		});
	}

	/**
	 * Gets or sets the encoding for the connection.
	 *
	 * @param $encoding
	 * @return mixed If setting the encoding; returns true on success, else false.
	 *         When getting, returns the encoding.
	 */
	public function encoding($encoding = null) {
		$encodingMap = array('UTF-8' => 'utf8');

		if (empty($encoding)) {
			$query = $this->connection->query("SHOW VARIABLES LIKE 'character_set_client'");
			$encoding = $query->fetchColumn(1);
			return ($key = array_search($encoding, $encodingMap)) ? $key : $encoding;
		}
		$encoding = isset($encodingMap[$encoding]) ? $encodingMap[$encoding] : $encoding;

		try {
			$this->connection->exec("SET NAMES '{$encoding}'");
			return true;
		} catch (PDOException $e) {
			return false;
		}
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
	 * @todo Eventually, this will need to rewrite aliases for DELETE and UPDATE queries, same with
	 *       order().
	 * @param string $conditions
	 * @param string $context
	 * @param array $options
	 * @return void
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
	 *        - 'buffered': If set to `false` uses mysql_unbuffered_query which
	 *          sends the SQL query query to MySQL without automatically fetching and buffering the
	 *          result rows as `mysql_query()` does (for less memory usage).
	 * @return resource Returns the result resource handle if the query is successful.
	 * @filter
	 */
	protected function _execute($sql, array $options = array()) {
		$defaults = array('buffered' => true);
		$options += $defaults;
		$this->connection->exec("USE  `{$this->_config['database']}`");

		$conn = $this->connection;

		$params = compact('sql', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) use ($conn) {
			$sql = $params['sql'];
			$options = $params['options'];
			$conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $options['buffered']);

			try {
				$resource = $conn->query($sql);
			} catch(PDOException $e) {
				$self->invokeMethod('_error', array($sql));
			};
			return $self->invokeMethod('_instance', array('result', compact('resource')));
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
		$resource = $this->_execute('SELECT LAST_INSERT_ID() AS insertID');
		list($id) = $resource->next();
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
			$column['length'] = $length[0] ? intval($length[0]) : null;
			$length[1] ? $column['precision'] = intval($length[1]) : null;
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
	 * Helper for `DatabaseSchema::_column()`
	 *
	 * @param array $field A field array
	 * @return string The SQL column string
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