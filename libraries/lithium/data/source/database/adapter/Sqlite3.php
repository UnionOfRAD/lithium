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
	public function __construct($config = array()) {
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

		if ($this->_connection = new \SQLite3($config['database'], $config['flags'], $config['key'])) {
			$this->_isConnected = true;
		}
		return $this->_isConnected;
	}

	public function disconnect() {
		if ($this->_isConnected) {
			$this->_isConnected = !$this->_connection->close();
			return !$this->_isConnected;
		}
		return true;
	}

	public function entities($model = null) {
		$config = $this->_config;
		$method = function($self, $params, $chain) use ($config) {
			return $self->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
		};
		return $this->_filter(__METHOD__, compact('model'), $method);
	}

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

	public function encoding($encoding = null) {
		$encodingMap = array('UTF-8' => 'utf8');

		if (empty($encoding)) {
			$encoding = $this->_connection->querySingle('PRAGMA encoding');
			return ($key = array_search($encoding, $encodingMap)) ? $key : $encoding;
		}
		$encoding = isset($encodingMap[$encoding]) ? $encodingMap[$encoding] : $encoding;
		$this->_connection->exec("PRAGMA encoding = \"{$encoding}\"");
		return $this->_connection->querySingle("PRAGMA encoding");
	}

	public function result($type, $resource, $context) {
		if (!($resource instanceof \SQLite3Result)) {
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

	public function value($value) {
		return $this->_connection->escapeString($value);
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
	public function columns($query, $resource = null, $context = null) {
		if (is_object($query)) {
			return parent::columns($query, $resource, $context);
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
		if ($this->_connection->lastErrorMsg()) {
			return array($this->_connection->lastErrorCode(), $this->_connection->lastErrorMsg());
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

	protected function _execute($sql, $options = array()) {
		$params = compact('sql', 'options');
		$conn =& $this->_connection;

		return $this->_filter(__METHOD__, $params, function($self, $params, $chain) use (&$conn) {
			extract($params);
			$result = $conn->query($sql);
			if ( !($result instanceof \SQLite3Result) ) {
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