<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter;

use \Exception;

/**
* Adapter class for MySQL improved extension.
*
* Implements the methods connecting higher level abstractions to the specifics of the MySQL
* improved extension.
*/
class MySQLi extends \lithium\data\source\Database {

	/**
	 * MySQLi column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = array(
		'primary_key'	=> array('name' => 'NOT NULL AUTO_INCREMENT'),
		'binary' => array('name' => 'blob'),
		'bit' => array('version' => '< 5.0.3', 'name' => 'tinyint', 'length' => 1),
		'bool' => array('name' => 'tinyint', 'length' => 1),
		'boolean' => array('name' => 'tinyint', 'length' => 1),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'datetime' => array(
			'name' => 'datetime',
			'format' => 'Y-m-d H:i:s',
			'formatter' => 'date'),
		'float' 		=> array('name' => 'float', 'formatter' => 'floatval'),
		'integer' 		=> array('name' => 'int', 'length' => 11, 'formatter' => 'intval'),
		'string' 		=> array('name' => 'varchar', 'length' => 255),
		'text' 			=> array('name' => 'text'),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'timestamp' 	=> array(
			'name' => 'timestamp',
			'format' => 'Y-m-d H:i:s',
			'formatter' => 'date')
	);

	/**
	 * MySQLi result object.
	 */
	protected $_mysqliResult;

	/**
	 * MySQLi-specific value denoting whether or not table aliases can be used in DELETE and
	 * UPDATE queries. This is dependent on the MySQL server version.
	 *
	 * @var boolean
	 */
	protected $_useAlias = true;


	/**
	 * Constructs the MySQLi adapter and defaults the port to 3306.
	 *
	 * @param array $config Configuration options for this class. For additional configuration,
	 *        see `lithium\data\source\Database` and `lithium\data\Source`.
	 *        - `'database'`: The name of the database to connect to. Defaults to 'lithium'.
	 *        - `'host'`: The IP or machine name where MySQL is running. Defaults to 'localhost'.
	 *        - `'persistent'`: If supported, should a persistent connection be made. Defaults to true.
	 *        - `'port'`: The port number MySQL is listening on. The default is '3306'.
	 *
	 * Typically, these parameters are set in `Connections::add()`, when adding the adapter to the
	 * list of active connections.
	 *
	 * @return object The adapter instance.
	 *
	 * @see lithium\data\source\Database::__construct()
	 * @see lithium\data\Source::__construct()
	 * @see lithium\data\Connections::add()
	 */
	public function __construct($config = array()) {
		$defaults = array('port' => '3306');
		parent::__construct((array) $config + $defaults);
	}

	/**
	 * In cases where the query is a raw string (as opposed to a `Query` object), the database must
	 * determine the correct column names from the result resource.
	 *
	 * @param object|string $query
	 * @param object $mysqliResult
	 * @param object $context
	 * @return array Field names
	 */
	public function columns($query, $mysqliResult = null, $context = null) {
		if (is_object($query)) {
			return parent::columns($query, $mysqliResult, $context);
		}

		$result = array();
		while ($fieldInfo = $mysqliResult->fetch_field()) {
			$result[] = $fieldInfo->name;
		}
		return $result;
	}

	/**
	 * Connects to the database and sets the connection encoding, using options provided
	 * to the class constructor.
	 *
	 * @return boolean True if the database could be connected, else false.
	 */
	public function connect() {
		$config = $this->_config;
		$this->_isConnected = false;

		$host = $config['persistent'] ? 'p:' : '';
		$host .= $config['host'];

		try {
			$this->_connection = new \mysqli(
				$host, $config['login'], $config['password'], $config['database'], $config['port']
			);
		} catch (exception $e) {
			throw new \RuntimeException($e->error);
		}
		if ($this->_connection !== false) {
			$this->_isConnected = true;

		if (!empty($config['encoding'])) {
			$this->_encoding($config['encoding']);
		}

			$this->_useAlias = (boolean) version_compare(
				$this->_connection->server_info, "4.1", ">="
			);
		}

		return $this->_isConnected;
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
	 *         - `'type'`: The field type name.
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
				preg_match('/(?P<type>\w+)(\((?P<length>\d+)\))?/', $column['Type'], $match);
				$filtered = array_intersect_key($match, array('type' => null, 'length' => null));
				$match = $filtered + array('length' => null);
				// $match['type'] = $self->invokeMethod('_column', $match['type']);

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
	 * Disconnects the adapter from the database.
	 *
	 * @return boolean True on success, else false.
	 */
	public function disconnect() {
		if ($this->_isConnected) {
			$this->_isConnected = !$this->_connection->close();
			return !$this->_isConnected;
		}
		return true;
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
			$encoding = $this->_connection->get_charset();
			return ($key = array_search($encoding->charset, $encodingMap)) ? $key : $encoding['charset'];
		}
		$encoding = isset($encodingMap[$encoding]) ? $encodingMap[$encoding] : $encoding;
		return $this->_connection->set_charset($encoding);
	}

	/**
	 * Returns the list of tables in the currently-connected database.
	 *
	 * @param string $model The fully-name-spaced class name of the model object making the request.
	 * @return array Returns an array of objects to which models can connect.
	 * @filter This method can be filtered.
	 */
	public function entities($model = null) {
	}

	/**
	 * Retrieves database error message and error code
	 *
	 * @return array|null
	 */
	public function error() {
		if ($this->_connection->errno) {
			return array($this->_connection->errno, $this->_connection->error);
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
	 *
	 * @param string $type
	 * @param object $mysqliResult
	 * @param unknown_type $context
	 * @return array|null
	 */
	public function result($type, $mysqliResult, $context) {
		if (!($mysqliResult instanceof  \mysqli_result)) {
			return null;
		}

		switch ($type) {
			case 'next':
				$result = $mysqliResult->fetch_row();
			break;
			case 'close':
				$mysqliResult->close();
				$result = null;
			break;
			default:
				$result = parent::result($type, $mysqliResult, $context);
			break;
		}
		return $result;
	}

	public function value($value) {
		return "'" . $this->_connection->real_escape_string($value) . "'";
	}

	/**
	 * Converts database-layer column types to basic types.
	 *
	 * @param string $real Real database-layer column type (i.e. "varchar(255)").
	 * @return string Abstract column type (i.e. "string").
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

	protected function _entityName($entity) {
		if (class_exists($entity, false) && method_exists($entity, 'meta')) {
			$entity = $entity::meta('name');
		}
		return $entity;
	}

	protected function _execute($sql, $options = array()) {
		$defaults = array('buffered' => true);
		$options += $defaults;

		$params = compact('sql', 'options');
		$conn =& $this->_connection;

		return $this->_filter(__METHOD__, $params, function($self, $params, $chain) use (&$conn) {
			extract($params);
			$mode = ($options['buffered']) ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT;
			$conn->_mysqliResult = $conn->query($sql, $mode);

			if ($conn->errno > 0) {
				list($code, $message) = $self->error();
				throw new Exception("$sql: $message", $code);
			}
			return $conn->_mysqliResult;
		});
	}

	protected function _results($mysqliResult) {
		$numFields = $mysqliResult->field_count;
		$index = $j = 0;

		while ($j < $numFields) {
			$column = $mysqliResult->fetch_field($j);

			if (!empty($column->table)) {
				$this->map[$index++] = array($column->table, $column->name);
			} else {
				$this->map[$index++] = array(0, $column->name);
			}
			$j++;
		}
	}
}

?>