<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter;

use lithium\data\model\QueryException;

/**
 * Extends the `Database` class to implement the necessary SQL-formatting and resultset-fetching
 * features for working with PostgreSQL databases.
 *
 * For more information on configuring the database connection, see the `__construct()` method.
 *
 * @see lithium\data\source\database\adapter\PostgreSQL::__construct()
 */
class PostgreSql extends \lithium\data\source\Database {

	protected $_classes = array(
		'entity' => 'lithium\data\entity\Record',
		'set' => 'lithium\data\collection\RecordSet',
		'relationship' => 'lithium\data\model\Relationship',
		'result' => 'lithium\data\source\database\adapter\postgre_sql\Result'
	);

	/**
	 * PostgreSQL column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = array(
		'primary_key' => array('name' => 'serial NOT NULL'),
		'string' => array('name' => 'varchar', 'length' => 255),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'integer', 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array(
			'name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'
			),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'bytea'),
		'boolean' => array('name' => 'boolean')
	);

	/**
	 * Pair of opening and closing quote characters used for quoting identifiers in queries.
	 *
	 * @var array
	 */
	protected $_quotes = array('"', '"');

	/**
	 * Constructs the PostgreSQL adapter and sets the default port to 3306.
	 *
	 * @see lithium\data\source\Database::__construct()
	 * @see lithium\data\Source::__construct()
	 * @see lithium\data\Connections::add()
	 * @param array $config Configuration options for this class. For additional configuration,
	 *        see `lithium\data\source\Database` and `lithium\data\Source`. Available options
	 *        defined by this class:
	 *        - `'database'`: The name of the database to connect to. Defaults to 'lithium'.
	 *        - `'host'`: The IP or machine name where PostgreSQL is running, followed by a colon,
	 *          followed by a port number or socket. Defaults to `'localhost:3306'`.
	 *        - `'persistent'`: If a persistent connection (if available) should be made.
	 *          Defaults to true.
	 *
	 * Typically, these parameters are set in `Connections::add()`, when adding the adapter to the
	 * list of active connections.
	 */
	public function __construct(array $config = array()) {
		$defaults = array('host' => 'localhost:5432', 'encoding' => null);
		parent::__construct($config + $defaults);
	}

	/**
	 * Check for required PHP extension, or supported database feature.
	 *
	 * @param string $feature Test for support for a specific feature, i.e. `"transactions"` or
	 *               `"arrays"`.
	 * @return boolean Returns `true` if the particular feature (or if PostgreSQL) support is enabled,
	 *         otherwise `false`.
	 */
	public static function enabled($feature = null) {
		if (!$feature) {
			return extension_loaded('pgsql');
		}
		$features = array(
			'arrays' => false,
			'transactions' => false,
			'booleans' => true,
			'relationships' => true
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
		$config = $this->_config;
		$this->_isConnected = false;

		if ( isset($config['port']) ) {
			$conn  = "host='{$config['host']}' port='{$config['port']}' dbname='{$config['database']}' ";
		} else {
			$conn  = "host='{$config['host']}' dbname='{$config['database']}' ";
		}
		$conn .= "user='{$config['login']}' password='{$config['password']}'";

		if (!$config['database']) {
			return false;
		}

		if (!$config['persistent']) {
			$this->connection = pg_connect($conn, PGSQL_CONNECT_FORCE_NEW);
		} else {
			$this->connection = pg_pconnect($conn);
		}

		if ($this->connection) {
			$this->_isConnected = true;
            if ( isset($config['schema']) ){
                pg_query($this->connection,"set search_path=\"{$config['schema']}\";");
            }
		}

		if ($config['encoding']) {
			$this->encoding($config['encoding']);
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
			$this->_isConnected = !pg_close($this->connection);
			return !$this->_isConnected;
		}
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

			if (!$result = $self->invokeMethod('_execute', array("select table_name from information_schema.tables where table_type = 'BASE TABLE' and table_catalog='{$name}';"))) {
				return null;
			}
			$sources = array();

			while ($data = $result->next()) {
				list($sources[]) = $data;
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
	 * @param array $meta
	 * @return array Returns an associative array describing the given table's schema, where the
	 *         array keys are the available fields, and the values are arrays describing each
	 *         field, containing the following keys:
	 *         - `'type'`: The field type name
	 * @filter This method can be filtered.
	 */
	public function describe($entity, array $meta = array()) {
		$params = compact('entity', 'meta');
		return $this->_filter(__METHOD__, $params, function($self, $params) {
			extract($params);

			$name = $self->invokeMethod('_entityName', array($entity));
			$columns = $self->read("SELECT DISTINCT column_name AS field, data_type AS type, is_nullable AS null,
					column_default AS default, ordinal_position AS position, character_maximum_length AS char_length,
					character_octet_length AS oct_length FROM information_schema.columns
				WHERE table_name = '{$name}' ORDER BY position;", array('return' => 'array', 'schema' => array(
				'field', 'type', 'null', 'default', 'position', 'char_length', 'oct_length'
			)));

			$fields = array();

			foreach ($columns as $column) {
				$match = $self->invokeMethod('_column', array($column['type']));

				$fields[$column['field']] = $match + array(
					'null'     => ($column['null'] == 'YES' ? true : false),
					'default'  => $column['default']
				);
			}

			return $fields;
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
		$encodingMap = array('UTF-8' => 'UTF8');

		if (empty($encoding)) {
			$encoding = pg_client_encoding($this->connection);
			return ($key = array_search($encoding, $encodingMap)) ? $key : $encoding;
		}
		$encoding = isset($encodingMap[$encoding]) ? $encodingMap[$encoding] : $encoding;
		return pg_set_client_encoding($encoding, $this->connection);
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
		return "'" . pg_escape_string($this->connection,(string) $value) . "'";
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
		$count = pg_num_fields($resource->resource());

		for ($i = 0; $i < $count; $i++) {
			$result[] = pg_field_name($resource->resource(), $i);
		}
		return $result;
	}

	/**
	 * Retrieves database error message and error code.
	 *
	 * @return array
	 */
	public function error() {
		if (pg_last_error($this->connection)) {
			return array(pg_last_error($this->connection), pg_last_error($this->connection));
		}
		return null;
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
	 *
	 * @return resource Returns the result resource handle if the query is successful.
	 * @filter
	 */
	protected function _execute($sql, array $options = array()) {
		$defaults = array('buffered' => true);
		$options += $defaults;

		return $this->_filter(__METHOD__, compact('sql', 'options'), function($self, $params) {
			$sql = $params['sql'];
			$options = $params['options'];

			$resource = pg_query($self->connection,$sql);

			if ($resource === true) {
				return true;
			}
			if (is_resource($resource)) {
				return $self->invokeMethod('_instance', array('result', compact('resource')));
			}
			list($code, $error) = $self->error();
			throw new QueryException("{$sql}: {$error}", $code);
		});
	}

	protected function _results($results) {
		$numFields = pg_num_fields($results);
		$index = $j = 0;

		while ($j < $numFields) {
			$column = pg_field_name($results, $j);
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
	protected function _insertId($query, $field = 'id') {
		// inspired by cakephp
		$seq = "{$column->table}_{$field}_seq";
		$recource = $this->_execute("SELECT currval('{$seq}') as max");
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