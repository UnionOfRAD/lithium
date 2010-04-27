<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter;

use \Exception;

class MySql extends \lithium\data\source\Database {

	/**
	 * MySQL column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = array(
		'primary_key' => array('name' => 'NOT NULL AUTO_INCREMENT'),
		'string' => array('name' => 'varchar', 'length' => 255),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'int', 'length' => 11, 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array(
			'name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'
		),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'tinyint', 'length' => 1)
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
	 *        - `'host'`: The IP or machine name where MySQL is running. Defaults to 'localhost'.
	 *        - `'persistent'`: If a persistent connection (if available) should be made.
	 *          Defaults to true.
	 *        - `'port'`: The port number MySQL is listening on. The default is '3306'.
	 *
	 * Typically, these parameters are set in `Connections::add()`, when adding the adapter to the
	 * list of active connections.
	 * @return The adapter instance.
	 */
	public function __construct(array $config = array()) {
		$defaults = array('port' => '3306', 'encoding' => null);
		parent::__construct($config + $defaults);
	}

	/**
	 * Check for required PHP extension
	 *
	 * @return boolean
	 */
	public static function enabled() {
		return extension_loaded('mysql');
	}

	/**
	 * Connects to the database using the options provided to the class constructor.
	 *
	 * @return boolean Returns `true` if a database connection could be established, otherwise
	 *         `false`.
	 */
	public function connect() {
		$config = $this->_config;
		$this->_isConnected = false;
		$host = $config['host'] . ':' . $config['port'];

		if (!$config['database']) {
			return false;
		}

		if ($config['persistent']) {
			$this->connection = mysql_connect($host, $config['login'], $config['password'], true);
		} else {
			$this->connection = mysql_pconnect($host, $config['login'], $config['password']);
		}

		if (mysql_select_db($config['database'], $this->connection)) {
			$this->_isConnected = true;
		}

		if ($config['encoding']) {
			$this->encoding($config['encoding']);
		}

		$this->_useAlias = (boolean) version_compare(
			mysql_get_server_info($this->connection), "4.1", ">="
		);
		return $this->_isConnected;
	}

	/**
	 * Disconnects the adapter from the database.
	 *
	 * @return boolean True on success, else false.
	 */
	public function disconnect() {
		if ($this->_isConnected) {
			$this->_isConnected = !mysql_close($this->connection);
			return !$this->_isConnected;
		}
		return true;
	}

	/**
	 * Returns the list of tables in the currently-connected database.
	 *
	 * @param string $model The fully-name-spaced class name of the model object making the request.
	 * @return array Returns an array of objects to which models can connect.
	 * @filter This method can be filtered.
	 */
	public function entities($model = null) {
		$config = $this->_config;
		$method = function($self, $params, $chain) use ($config) {
			$name = $this->name($config['database']);
			return $self->query("SHOW TABLES FROM {$name};");
		};
		return $this->_filter(__METHOD__, compact('model'), $method);
	}

	/**
	 * Gets the column schema for a given MySQL table.
	 *
	 * @param mixed $entity Specifies the table name for which the schema should be returned, or
	 *        the class name of the model object requesting the schema, in which case the model
	 *        class will be queried for the correct table name.
	 * @param array $meta
	 * @return array Returns an associative array describing the given table's schema, where the
	 *         array keys are the available fields, and the values are arrays describing each
	 *         field, containing the following keys:
	 *         - `'type'`: The field type name
	 * @filter This method can be filtered.
	 */
	public function describe($entity, $meta = array()) {
		$params = compact('entity', 'meta');
		return $this->_filter(__METHOD__, $params, function($self, $params, $chain) {
			extract($params);

			$name = $self->invokeMethod('_entityName', array($entity));
			$columns = $self->read("DESCRIBE {$name}", array('return' => 'array'));
			$fields = array();

			foreach ($columns as $column) {
				$match = $self->invokeMethod('_column', array($column['Type']));

				$fields[$column['Field']] = $match + array(
					'null'     => ($column['Null'] == 'YES' ? true : false),
					'default'  => $column['Default'],
				);
				//if (!empty($column['Key']) && isset($this->index[$column[0]['Key']])) {
				//	$fields[$column['Field']]['key'] = $this->index[$column[0]['Key']];
				//}
			}
			return $fields;
		});
	}

	/**
	 * Gets or sets the encoding for the connection.
	 *
	 * @param $encoding
	 * @return boolean|string If setting the encoding; returns true on success, else false.
	 *         When getting, returns the encoding.
	 */
	public function encoding($encoding = null) {
		$encodingMap = array('UTF-8' => 'utf8');

		if (empty($encoding)) {
			$encoding = mysql_client_encoding($this->connection);
			return ($key = array_search($encoding, $encodingMap)) ? $key : $encoding;
		}
		$encoding = isset($encodingMap[$encoding]) ? $encodingMap[$encoding] : $encoding;
		return mysql_set_charset($encoding, $this->connection);
	}

	/**
	 * Handle the result.
	 *
	 * @param string $type next|close The current step in the iteration.
	 * @param mixed $resource The result resource returned from the database.
	 * @param \lithium\data\model\Query $context The given query.
	 * @return mixed Result
	 */
	public function result($type, $resource, $context) {
		if (!is_resource($resource)) {
			return null;
		}

		switch ($type) {
			case 'next':
				$result = mysql_fetch_row($resource);
			break;
			case 'close':
				mysql_free_result($resource);
				$result = null;
			break;
			default:
				$result = parent::result($type, $resource, $context);
			break;
		}
		return $result;
	}

	/**
	 * Converts a given value into the proper type based on a given schema definition.
	 *
	 * @see \lithium\data\source\Database::schema()
	 * @param mixed $value The value to be converted. Arrays will be recursively converted.
	 * @param array $schema Formatted array from `\lithium\data\source\Database::schema()`
	 * @return mixed Value with converted type.
	 */
	public function value($value, array $schema = array()) {
		if (is_array($value)) {
			return parent::value($value, $schema);
		}
		$result = parent::value($value, $schema);;

		if (is_string($result)) {
			return "'" . mysql_real_escape_string($value, $this->connection) . "'";
		}
		return $result;
	}

	/**
	 * In cases where the query is a raw string (as opposed to a `Query` object), to database must
	 * determine the correct column names from the result resource.
	 *
	 * @param mixed $query
	 * @param resource $resource
	 * @param object $context
	 * @return array
	 */
	public function schema($query, $resource = null, $context = null) {
		if (is_object($query)) {
			return parent::schema($query, $resource, $context);
		}

		$result = array();
		$count = mysql_num_fields($resource);

		for ($i = 0; $i < $count; $i++) {
			$result[] = mysql_field_name($resource, $i);
		}
		return $result;
	}

	/**
	 * Retrieves database error message and error code.
	 *
	 * @return array
	 */
	public function error() {
		if (mysql_error($this->connection)) {
			return array(mysql_errno($this->connection), mysql_error($this->connection));
		}
		return null;
	}

	/**
	 * Quotes identifiers.
	 *
	 * Currently, this method simply returns the identifier.
	 *
	 * @param string $name The identifier to quote.
	 * @return string The quoted identifier.
	 */
	public function name($name) {
		return $name;
	}

	/**
	 * Execute a given query.
 	 *
 	 * @see \lithium\data\source\Database::renderCommand()
	 * @param string $sql The sql string to execute
	 * @param array $options Available options:
	 *        - 'buffered': If set to `false` uses mysql_unbuffered_query which
	 *          sends the SQL query query to MySQL without automatically 
	 *          fetching and buffering the result rows as mysql_query() does (for
	 *          less memory usage).
	 * @return resource
	 */
	protected function _execute($sql, array $options = array()) {
		$defaults = array('buffered' => true);
		$options += $defaults;

		$conn =& $this->connection;
		$params = compact('sql', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params, $chain) use (&$conn) {
			extract($params);
			$func = ($options['buffered']) ? 'mysql_query' : 'mysql_unbuffered_query';
			$result = $func($sql, $conn);

			if (!(is_resource($result) || $result === true)) {
				list($code, $error) = $self->error();
				throw new Exception("{$sql}: {$error}", $code);
			}
			return $result;
		});
	}

	protected function _results($results) {
		$numFields = mysql_num_fields($results);
		$index = $j = 0;

		while ($j < $numFields) {
			$column = mysql_fetch_field($results, $j);
			$name = $column->name;
			$table = $column->table;
			$this->map[$index++] = empty($table) ? array(0, $name) : array($table, $name);
			$j++;
		}
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
		list($id) = $this->result('next', $resource, null);
		$this->result('close', $resource, null);

		if (!empty($id) && $id !== '0') {
			return $id;
		}
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

		if (!preg_match('/(?P<type>\w+)(?:\((?P<length>\d+)\))?/', $real, $column)) {
			return $real;
		}
		$column = array_intersect_key($column, array('type' => null, 'length' => null));

		switch (true) {
			case in_array($column['type'], array('date', 'time', 'datetime', 'timestamp')):
				return $column;
			case ($column['type'] == 'tinyint' && $column['length'] == '1'):
			case ($column['type'] == 'boolean'):
				return array('type' => 'boolean');
			break;
			case (strpos($column['type'], 'int') !== false):
				$column['type'] = 'integer';
			break;
			case (strpos($column['type'], 'char') !== false || $column['type'] == 'tinytext'):
				$column['type'] = 'string';
			break;
			case (strpos($column['type'], 'text') !== false):
				$column['type'] = 'text';
			break;
			case (strpos($column['type'], 'blob') !== false || $column['type'] == 'binary'):
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
	 * Helper method that retrieves an entity's name via its metadata.
	 *
	 * @param string $entity Entity name.
	 * @return string Name.
	 */
	protected function _entityName($entity) {
		if (class_exists($entity, false) && method_exists($entity, 'meta')) {
			$entity = $entity::meta('name');
		}
		return $entity;
	}
}

?>