<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */

namespace lithium\data\source\database\adapter;

use \Exception;
use \SQLite3 as SQLite;
use \SQLite3Result;

/**
 * Sqlite database driver
 *
 * @todo fix encoding methods to use class query methods instead of sqlite3 natives
 */
class Sqlite3 extends \lithium\data\source\Database {

	/**
	 * Sqlite column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = array(
		'primary_key' => array('name' => 'integer primary key'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'integer', 'limit' => 11, 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'boolean')
	);

	/**
	 * Constructs the Sqlite adapter
	 *
	 * @param array $config Configuration options for this class. For additional configuration,
	 *        see `lithium\data\source\Database` and `lithium\data\Source`. Available options
	 *        defined by this class:
	 *        - `'database'` _string_: database name. Defaults to none
	 *        - `'flags'` _integer_: Optional flags used to determine how to open the SQLite
	 *          database. By default, open uses SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE.
	 *        - `'key'` _string_: An optional encryption key used when encrypting and decrypting
	 *          an SQLite database.
	 *
	 * Typically, these parameters are set in `Connections::add()`, when adding the adapter to the
	 * list of active connections.
	 * @return The adapter instance.
	 *
	 * @see lithium\data\source\Database::__construct()
	 * @see lithium\data\Source::__construct()
	 * @see lithium\data\Connections::add()
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'database'   => '',
			'flags'      => NULL,
			'key'        => NULL
		);
		parent::__construct((array) $config + $defaults);
	}

	/**
	 * Connects to the database using options provided to the class constructor.
	 *
	 * @return boolean True if the database could be connected, else false
	 */
	public function connect() {
		$config = $this->_config;
		$this->_isConnected = false;

		if ($this->connection = new SQLite($config['database'], $config['flags'], $config['key'])) {
			$this->_isConnected = true;
		}
		return $this->_isConnected;
	}

	/**
	 * Disconnects the adapter from the database.
	 *
	 * @return boolean True on success, else false.
	 */
	public function disconnect() {
		if ($this->_isConnected) {
			$this->_isConnected = !$this->connection->close();
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
			return $self->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
		};
		return $this->_filter(__METHOD__, compact('model'), $method);
	}

	/**
	 * Gets the column schema for a given Sqlite3 table.
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
			$columns = $self->read("PRAGMA table_info({$name})", array('return' => 'array'));
			$fields = array();

			foreach ($columns as $column) {
				list($type, $length) = explode('(', $column['type']);
				$length = trim($length, ')');
				$fields[$column['name']] = array(
					'type' => $type,
					'length' => $length,
					'null'     => ($column['notnull'] == 1 ? true : false),
					'default'  => $column['dflt_value'],
				);
			}
			return $fields;
		});
	}

	/**
	 * Get the last insert id from the database.
	 *
	 * @param \lithium\data\model\Query $context The given query.
	 * @return void
	 */
	protected function _insertId($query) {
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
			$encoding = $this->connection->querySingle('PRAGMA encoding');
			return ($key = array_search($encoding, $encodingMap)) ? $key : $encoding;
		}
		$encoding = isset($encodingMap[$encoding]) ? $encodingMap[$encoding] : $encoding;
		$this->connection->exec("PRAGMA encoding = \"{$encoding}\"");
		return $this->connection->querySingle("PRAGMA encoding");
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
		if (!($resource instanceof SQLite3Result)) {
			return null;
		}

		switch ($type) {
			case 'next':
				$result = $resource->fetchArray(SQLITE3_ASSOC);
			break;
			case 'close':
				$resource->finalize();
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
		return $this->connection->escapeString($value);
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
		$count = $resource->numColumns();

		for ($i = 0; $i < $count; $i++) {
			$result[] = $resource->columnName($i);
		}
		return $result;
	}

	/**
	 * Retrieves database error message and error code.
	 *
	 * @return array
	 */
	public function error() {
		if ($this->connection->lastErrorMsg()) {
			return array($this->connection->lastErrorCode(), $this->connection->lastErrorMsg());
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
	 * @param array $options No available options.
	 * @return resource
	 */
	protected function _execute($sql, array $options = array()) {
		$params = compact('sql', 'options');
		$conn =& $this->connection;

		return $this->_filter(__METHOD__, $params, function($self, $params, $chain) use (&$conn) {
			extract($params);
			$result = $conn->query($sql);
			if ( !($result instanceof SQLite3Result) ) {
				list($code, $error) = $self->error();
				throw new Exception("$sql: $error", $code);
			}
			return $result;
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

		if (!preg_match('/(?P<type>[^(]+)(?:\((?P<length>[^)]+)\))?/', $real, $column)) {
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
}

?>