<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter;

use lithium\data\model\QueryException;
use interbase\Result;
use lithium\analysis\Debugger;

/**
 * Extends the `Database` class to implement the necessary SQL-formatting and resultset-fetching
 * features for working with Interbase databases.
 *
 * For more information on configuring the database connection, see the `__construct()` method.
 *
 * @see lithium\data\source\database\adapter\Interbase::__construct()
 */
class Interbase extends \lithium\data\source\Database {
  
	protected $_classes = array(
		'entity' => 'lithium\data\entity\Record',
		'set' => 'lithium\data\collection\RecordSet',
		'relationship' => 'lithium\data\model\Relationship',
		'result' => 'lithium\data\source\database\adapter\interbase\Result'
	);

	/**
	 * Interbase column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = array(
		'primary_key' => array('name' => 'IDENTITY (1, 1) NOT NULL'),
		'string' => array('name' => 'varchar', 'limit' => 255),
		'text' => array('name' => 'BLOB SUB_TYPE 1 SEGMENT SIZE 100 CHARACTER SET NONE'),
		'integer' => array('name' => 'integer', 'length' => 11, 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
    'datetime'  => array('name' => 'timestamp', 'format'  => 'd.m.Y H:i:s', 'formatter' => 'date'),
    'timestamp' => array('name' => 'timestamp', 'format'   => 'd.m.Y H:i:s', 'formatter' => 'date'),
	  'time'    => array('name' => 'time', 'format'    => 'H:i:s', 'formatter' => 'date'),
    'date'    => array('name' => 'date', 'format'    => 'd.m.Y', 'formatter' => 'date'),
    'binary'  => array('name' => 'blob'),
    'boolean' => array('name' => 'smallint')
	);

	/**
	 * Pair of opening and closing quote characters used for quoting identifiers in queries.
	 *
	 * @var array
	 */
	protected $_quotes = array("\'", "\'");

	/**
	 * Interbase-specific value denoting whether or not table aliases should be used in DELETE and
	 * UPDATE queries.
	 *
	 * @var boolean
	 */
	protected $_useAlias = true;

	/**
	 * Constructs the Interbase adapter and sets the default port to 3050.
	 *
	 * @see lithium\data\source\Database::__construct()
	 * @see lithium\data\Source::__construct()
	 * @see lithium\data\Connections::add()
	 * @param array $config Configuration options for this class. For additional configuration,
	 *        see `lithium\data\source\Database` and `lithium\data\Source`. Available options
	 *        defined by this class:
   *        - `'host'`: The IP or machine name where Interbase is running 
	 *        - `'database'`: The FILENAME of the database to connect to. Defaults to 'c:\\DB\\LITHIUM.FB'.
   *        - `'port'`: The PORT number or socket. Defaults to `'3050'` Firebird port.
	 *        - `'connect'`: The name of PHP's Interbase connect-function, 
   *            either `'ibase_connect'` or `'ibase_pconnect'` (for persistent connections).
   *            Defaults to `'ibase_pconnect'`
	 *
	 * Typically, these parameters are set in `Connections::add()`, when adding the adapter to the
	 * list of active connections.
	 */
	public function __construct(array $config = array()) {
		$defaults = array('host' => 'localhost', 
		                  'database' => 'c:\\DB\\LITHIUM.FB',
                      'port' => '3050',
		                  'login' => 'SYSDBA',
		                  'password' => 'masterkey',
                      'persistent' => false,
                      'encoding' => 'utf-8'
		            );
		parent::__construct($config + $defaults);
	}

	/**
	 * Check for required PHP extension, or supported database feature.
	 *
	 * @param string $feature Test for support for a specific feature, i.e. `"transactions"` or
	 *               `"arrays"`.
	 * @return boolean Returns `true` if the particular feature (or if Interbase) support is enabled,
	 *         otherwise `false`.
	 */
  public static function enabled($feature = null) {
    if (!$feature) {
      return extension_loaded('interbase');
    }
    $features = array(
      'arrays' => true,
      'transactions' => true,
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
    $connect = ( (bool) $config['persistent'] == true ? 'ibase_pconnect' : 'ibase_connect' );
    $host = ( (bool) $config['host'] == true ? $config['host'] : Exception() );
    $database = $config['database'];
    $str_connect = "$host:$database";
	  $this->connection = $connect($str_connect, $config['login'], $config['password'], $config['encoding']);
    return $this->_isConnected = (boolean) $this->connection;
	}

	/**
	 * Disconnects the adapter from the database.
	 *
	 * @return boolean True on success, else false.
	 */
	public function disconnect() {
		if ($this->_isConnected) {
			$this->_isConnected = @ibase_close;
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

      $sql = "select RDB" . "$" . "RELATION_NAME as name
              FROM RDB" ."$" . "RELATIONS
              Where RDB" . "$" . "SYSTEM_FLAG =0";
      
          $result = @ibase_query($this->connection,$sql);

			if (!$result = $self->invokeMethod('_execute', array("$sql;"))) {
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
   * Returns a really detailed description of the field
   */
  public function detailedTablefieldinfo() {
    $sql = "SELECT r.RDB\$FIELD_NAME AS field_name,
                      r.RDB\$DESCRIPTION AS field_description,
                      r.RDB\$DEFAULT_VALUE AS field_default_value,
                      r.RDB\$NULL_FLAG AS field_not_null_constraint,
                      f.RDB\$FIELD_LENGTH AS field_length,
                      f.RDB\$FIELD_PRECISION AS field_precision,
                      f.RDB\$FIELD_SCALE AS field_scale,
                      CASE f.RDB\$FIELD_TYPE
                        WHEN 261 THEN 'BLOB'
                        WHEN 14 THEN 'CHAR'
                        WHEN 40 THEN 'CSTRING'
                        WHEN 11 THEN 'D_FLOAT'
                        WHEN 27 THEN 'DOUBLE'
                        WHEN 10 THEN 'FLOAT'
                        WHEN 16 THEN 'INT64'
                        WHEN 8 THEN 'INTEGER'
                        WHEN 9 THEN 'QUAD'
                        WHEN 7 THEN 'SMALLINT'
                        WHEN 12 THEN 'DATE'
                        WHEN 13 THEN 'TIME'
                        WHEN 35 THEN 'TIMESTAMP'
                        WHEN 37 THEN 'VARCHAR'
                        ELSE 'UNKNOWN'
                      END AS field_type,
                      f.RDB\$FIELD_SUB_TYPE AS field_subtype,
                      coll.RDB\$COLLATION_NAME AS field_collation,
                      cset.RDB\$CHARACTER_SET_NAME AS field_charset
                 FROM RDB\$RELATION_FIELDS r
                 LEFT JOIN RDB\$FIELDS f ON r.RDB\$FIELD_SOURCE = f.RDB\$FIELD_NAME
                 LEFT JOIN RDB\$COLLATIONS coll ON f.RDB\$COLLATION_ID = coll.RDB\$COLLATION_ID
                 LEFT JOIN RDB\$CHARACTER_SETS cset ON f.RDB\$CHARACTER_SET_ID = cset.RDB\$CHARACTER_SET_ID
                WHERE r.RDB\$RELATION_NAME='TEST2'  -- table name
              ORDER BY r.RDB\$FIELD_POSITION";
    $data = $this->_execute($sql);
    return $data;
  }
  
  
  /**
   * Returns a list of the prepared procedures in the database.
   *
   * @return array
   */  
  public function listProcedurenames() {
    $sql = 'SELECT * FROM RDB$PROCEDURES';
    $result = $this->_execute($sql);
    return $result;
  }
  
  /**
   * Returns a list of the tables in the database.
   *
   * @return array
   */
  public function listTables() {
    $sql = 'SELECT RDB$RELATION_NAME FROM RDB$RELATIONS WHERE RDB$SYSTEM_FLAG = 0';
    $result = $this->_execute($sql);
    return $result;
  }

	/**
	 * Gets the column schema for a given Interbase table.
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
			$columns = $self->read("DESCRIBE {$name}", array('return' => 'array', 'schema' => array(
				'field', 'type', 'null', 'key', 'default', 'extra'
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
	  
    $sql = 'SELECT RDB$CHARACTER_SET_NAME FROM RDB$DATABASE';
    $data = $this->_execute($sql);
    //echo Debugger::export($data);    
    
    $config = $this->_config;
    if (!empty($encoding)) {
      $encoding = $config['encoding'];
    }
		$encodingMap = array('WIN1252' => 'win1252', 'UTF8' => 'utf-8');  
		if (empty($encoding)) {
			$encoding = ibase_db_info($this->connection);
			return ($key = array_search($encoding, $encodingMap)) ? $key : $encoding;
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
		return "'" . $value . $this->connection . "'";
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
		$count = ibase_num_fields($resource->resource());

		for ($i = 0; $i < $count; $i++) {
			$result[] = ibase_field_info($resource->resource(), $i);
		}
		return $result;
	}

	/**
	 * Retrieves database error message and error code.
	 *
	 * @return array
	 */
	public function error() {
		if (ibase_errmsg($this->connection)) {
			return array(ibase_errcode($this->connection), ibase_errmsg($this->connection));
		}
		return null;
	}

	public function alias($alias, $context) {
		if ($context->type() == 'update' || $context->type() == 'delete') {
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
	 * @param array $options
	 * @return resource Returns the result resource handle if the query is successful.
	 * @filter
	 */
	protected function _execute($sql) {
    return @ibase_query($this->connection,  $sql);
	}

	protected function _results($results) {
		$numFields = ibase_num_fields($results);
		$index = $j = 0;

		while ($j < $numFields) {
			$column = ibase_fetch_assoc($result);
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
	protected function _insertId($source = null, $field = 'id') {
    $query = "SELECT RDB\$TRIGGER_SOURCE
    FROM RDB\$TRIGGERS WHERE RDB\$RELATION_NAME = '".  strtoupper($source) .  "' AND
    RDB\$SYSTEM_FLAG IS NULL AND  RDB\$TRIGGER_TYPE = 1 ";
    $result = @ibase_query($this->connection,$query);
    $generator = "";

    while ($row = ibase_fetch_row($result, IBASE_TEXT)) {
      if (strpos($row[0], "NEW." . strtoupper($field))) {
        $pos = strpos($row[0], "GEN_ID(");

        if ($pos > 0) {
          $pos2 = strpos($row[0],",",$pos + 7);

          if ($pos2 > 0) {
            $generator = substr($row[0], $pos +7, $pos2 - $pos- 7);
          }
        }
        break;
      }
    }

    if (!empty($generator)) {
      $sql = "SELECT GEN_ID(". $generator  . ",0) AS maxi FROM RDB" . "$" . "DATABASE";
      $res = $this->rawQuery($sql);
      $data = $this->fetchRow($res);
      return $data['maxi'];
    } else {
      return false;
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
      $col = $real['name'];

      if (isset($real['limit'])) {
        $col .= '(' . $real['limit'] . ')';
      }
      return $col;
    }

    $col = str_replace(')', '', $real);
    $limit = null;
    if (strpos($col, '(') !== false) {
      list($col, $limit) = explode('(', $col);
    }

    if (in_array($col, array('DATE', 'TIME'))) {
      return strtolower($col);
    }
    if ($col == 'TIMESTAMP') {
      return 'datetime';
    }
    if ($col == 'SMALLINT') {
      return 'boolean';
    }
    if (strpos($col, 'int') !== false || $col == 'numeric' || $col == 'INTEGER') {
      return 'integer';
    }
    if (strpos($col, 'char') !== false) {
      return 'string';
    }
    if (strpos($col, 'text') !== false) {
      return 'text';
    }
    if (strpos($col, 'VARCHAR') !== false) {
      return 'string';
    }
    if (strpos($col, 'BLOB') !== false) {
      return 'text';
    }
    if (in_array($col, array('FLOAT', 'NUMERIC', 'DECIMAL'))) {
      return 'float';
    }
    return 'text';
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
  
  /**
   * Fetches the next row from the current result set
   *
   * @return unknown
   */
  protected function fetchResult() {
    if ($row = ibase_fetch_row($this->results, IBASE_TEXT)) {
      $resultRow = array();
      $i = 0;
  
      foreach ($row as $index => $field) {
        list($table, $column) = $this->map[$index];
  
        if (trim($table) == "") {
          $resultRow[0][$column] = $row[$index];
        } else {
          $resultRow[$table][$column] = $row[$index];
          $i++;
        }
      }
      return $resultRow;
    } else {
      return false;
    }
  }
  
}

?>