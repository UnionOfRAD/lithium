<?php

namespace lithium\data\source;

use \Mongo;

class MongoDb extends \lithium\data\Source {

	protected $_db = null;

	public function __construct($config = array()) {
		$defaults = array(
			'persistent' => true,
			'host'       => 'localhost',
			'database'   => 'lithium',
			'port'       => '27017',
		);
		parent::__construct((array)$config + $defaults);
	}

	public function __destruct() {
		if ($this->_isConnected) {
			$this->disconnect();
			unset($this->_db);
			unset($this->_connection);
		}
	}

	public function connect() {
		$config = $this->_config;
		$this->_isConnected = false;
		$host = $config['host'] . ':' . $config['port'];

		$this->_connection = new Mongo($host, true, $config['persistent']);

		if ($this->_db = $this->_connection->selectDB($config['database'])) {
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

	public function entities($class = null) {
		return $this->_db->listCollections();
	}

	public function describe($entity, $meta = array()) {
	}

	public function name($name) {
		return $name;
	}

	public function create($record, $options = array()) {
		return true;
	}

	/**
	 * A method dispatcher that allows direct calls to native methods in PHP's `Mongo` object. Read
	 * more here: http://php.net/manual/class.mongo.php
	 *
	 * For example (assuming this instance is stored in `Connections` as `'mongo'`):
	 * {{{// Manually repairs a MongoDB instance
	 * Connections::get('mongo')->repairDB($db); // returns null
	 * }}}
	 *
	 * @param string $method The name of native method to call. See the link above for available
	 *        class methods.
	 * @param array $params A list of parameters to be passed to the native method.
	 * @return mixed Returns the value of the native method specified in `$method`.
	 */
	public function __call($method, $params) {
		return call_user_func_array(array(&$this->_connection, $method), $params);
	}

	public function read($query, $options = array()) {
		$defaults = array('return' => 'resource');
		$options += $defaults;
		$params = compact('query', 'options');
		$conn =& $this->_connection;
		$db =& $this->_db;

		$filter = function($self, $params, $chain) use (&$conn, &$db) {
			extract($params);
			extract($query->export($self), EXTR_OVERWRITE);

			$result = $db->selectCollection($table)->find($conditions, $fields);
			return $result->sort($order)->limit($limit)->skip($page * $limit);
		};
		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function result($type, $resource, $context) {
		if (!is_object($resource)) {
			return null;
		}

		switch ($type) {
			case 'next':
				$result = $resource->hasNext() ? $resource->getNext() : null;
			break;
			case 'close':
				unset($resource);
				$result = null;
			break;
			default:
				$result = parent::result($type, $resource, $context);
			break;
		}
		return $result;
	}

	public function update($query, $options) {
		return true;
	}

	public function delete($query, $options) {
		return true;
	}

	public function conditions($conditions, $context) {
		return $conditions ?: array();
	}

	public function fields($fields, $context) {
		return $fields ?: array();
	}

	public function limit($limit, $context) {
		return $limit ?: array();
	}

	function order($order, $context) {
		return $order ?: array();
	}
}

?>