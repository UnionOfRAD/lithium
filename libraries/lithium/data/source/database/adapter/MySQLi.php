<?php
/**
 * Lithium: the most rad php framework
 *
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter;

use \Exception;

class MySQLi extends \lithium\data\source\Database\MySql {


	/**
	 * Constructs the MySQLi adapter and sets the default port to 3306.
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
		parent::__construct();
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
	}

	/**
	 * Connects to the database using options provided to the class constructor.
	 *
	 * @return boolean True if the database could be connected, else false
	 */
	public function connect() {
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
	}

	public function encoding($encoding = null) {
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
	}

	protected function _results($results) {
	}
}

?>
