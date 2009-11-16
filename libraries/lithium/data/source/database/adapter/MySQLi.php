<?php
/**
 * Lithium: the most rad php framework
 *
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter;

use \Exception;

class MySQLi extends \lithium\data\source\Database {

	/**
	 * MySQLi column type definitions.
	 *
	 * @var array
	 */
	protected $_columns = array(
		'primary_key'	=> array('name' => 'NOT NULL AUTO_INCREMENT'),
		'string' 		=> array('name' => 'varchar', 'length' => 255),
		'text' 			=> array('name' => 'text'),
		'integer' 		=> array('name' => 'int', 'length' => 11, 'formatter' => 'intval'),
		'float' 		=> array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' 		=> array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' 	=> array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'
		),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'tinyint', 'length' => 1)
	);

	/**
	 * MySQLi-specific value denoting whether or not table aliases should be used in DELETE and
	 * UPDATE queries.
	 *
	 * @var boolean
	 */
	protected $_useAlias = true;


	/**
	 * Constructs the MySQLi adapter and default the port to 3306.
	 *
	 * @param array $config Configuration options for this class. For additional configuration,
	 *        see `lithium\data\source\Database` and `lithium\data\Source`. Available options
	 *        defined by this class:
	 *
	 *	- `'port'`: Accepts a port number or Unix socket name to use when connecting to the
	 *     database.  Defaults to `'3306'`.
	 */
	public function __construct($config = array()) {
		$defaults = array('port' => '3306');
		parent::__construct((array)$config + $defaults);
	}

	/**
	 * In cases where the query is a raw string (as opposed to a `Query` object), the database must
	 * determine the correct column names from the result resource.
	 *
	 * @param mixed $query
	 * @param mysqli_result object $mysqliResult
	 * @param object $context
	 * @return array Field names
	 */
	public function columns($query, $mysqliResult = null, $context = null) {
		if (is_object($query)) {
			return parent::columns($query, $mysqliResult, $context);
		}

		$result = array();
		while ($fieldInfo = $mysqliResult->fetch_field()) {
			$result[] = $finfo->name;
		}
		return $result;
	}

	/**
	 * Connects to the database and sets the connection encoding,
	 * using options provided to the class constructor.
	 *
	 * @return boolean True if the database could be connected, else false
	 */
	public function connect() {
		$config = $this->_config;
		$this->_isConnected = false;

		$host = $config['persistent'] ? 'p:' : '';
		$host .= $config['host'];

		$this->_connection = new mysqli($host, $config['login'], $config['password'], $config['database'], $config['port']);
		if ($this->_connection !== false) {
			$this->_isConnected = true;

			$this->_encoding($config['encoding']);

			$this->_useAlias = (bool)version_compare(
				$this->_connection->server_info(), "4.1", ">="
			);
		}

		return $this->_isConnected;
	}

	/**
	 * Gets the column schema for a given MySQL table.
	 *
	 * @param mixed $entity Specifies the table name for which the schema should be returned, or the
	 *              class name of the model object requesting the schema, in which case the model
	 *              class will be queried for the correct table name.
	 * @param array $meta
	 * @return array Returns an associative array describing the given table's schema, where the
	 *               array keys are the available fields, and the values are arrays describing each
	 *               field, containing the following keys:
	 *               -`'type'`: The field type name
	 * @filter This method can be filtered.
	 */
	public function describe($entity, $meta = array()) {
	}

	public function disconnect() {
		if ($this->_isConnected) {
			;
		}
	}

	/**
	 * Gets/sets the encoding for the connection
	 * @param $encoding
	 * @return mixed
	 */
	public function encoding($encoding = null) {
		$encodingMap = array('UTF-8' => 'utf8');

		if (empty($encoding)) {
			$encoding = $this->_connection->get_charset();
			return ($key = array_search($encoding, $encodingMap)) ? $key : $encoding;
		}
		$encoding = isset($encodingMap[$encoding]) ? $encodingMap[$encoding] : $encoding;
		return $this->_connection->set_charset($encoding);
	}

	/**
	 * Returns the list of tables in the currently-connected database.
	 *
	 * @param string $model The fully-namespaced class name of the model object making the request.
	 * @return array Returns an array of objects to which models can connect.
	 * @filter This method can be filtered.
	 */
	public function entities($model = null) {
	}

	/**
	 * Retrieves database error message and error code
	 *
	 * @return array
	 */
	public function error() {
	}

	public function result($type, $resource, $context) {
	}

	public function value($value) {
	}

	/**
	 * Converts database-layer column types to basic types
	 *
	 * @param string $real Real database-layer column type (i.e. "varchar(255)")
	 * @return string Abstract column type (i.e. "string")
	 */
	protected function _column($real) {
	}

	protected function _entityName($entity) {
	}

	protected function _execute($sql, $options = array()) {
		$defaults = array('buffered' => true);
		$options += $defaults;

		$params = compact('sql', 'options');
		$conn =& $this->_connection;

		return $this->_filter(__METHOD__, $params, function($self, $params, $chain) use (&$conn) {
			extract($params);
			$mode = ($options['buffered']) ? MYSQLI_STORE_RESULT : MYSQLI_STORE_RESULT;
			$result = mysqli_query($sql, $conn);

			if (mysqli_error() > 0) {
				list($code, $error) = $self->error();
				throw new Exception("$sql: $error", $code);
			}
			return $resource;
		});
	}

	protected function _results($results) {
	}
}

?>
