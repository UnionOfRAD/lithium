<?php

namespace lithium\data\source;

use \Mongo;
use \MongoId;

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

	/**
	 * Configures a model class by overriding the default dependencies for `'recordSet'` and
	 * `'record'` , and sets the primary key to `'_id'`, in keeping with Mongo's conventions.
	 *
	 * @param string $class The fully-namespaced model class name to be configured.
	 * @return Returns an array containing keys `'classes'` and `'meta'`, which will be merged with
	 *         their respective properties in `Model`.
	 * @see lithium\data\Model::$_meta
	 * @see lithium\data\Model::$_classes
	 */
	public function configureClass($class) {
		return array('meta' => array('key' => '_id'), 'classes' => array(
			'record' => '\lithium\data\model\Document',
			'recordSet' => '\lithium\data\model\Document'
		));
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
	 * @todo Recursively normalize '_id' fields to compare input and output
	 */
	public function __call($method, $params) {
		return call_user_func_array(array(&$this->_connection, $method), $params);
	}

	public function create($query, $options = array()) {
		$params = compact('query', 'options');
		$conn =& $this->_connection;
		$db =& $this->_db;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, &$db) {
			extract($params);
			$params = $query->export($self);
			$data = $query->data();
			$result = $db->selectCollection($params['table'])->insert($data, true);

			if ($result['ok'] === 1.0) {
				$id = is_object($data['_id']) ? $data['_id']->__toString() : null;
				$query->record()->invokeMethod('_update', array($id));
				return true;
			}
			return false;
		});
	}

	public function read($query, $options = array()) {
		$defaults = array('return' => 'resource');
		$options += $defaults;
		$params = compact('query', 'options');
		$conn =& $this->_connection;
		$db =& $this->_db;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, &$db) {
			extract($params);
			$query = $query->export($self);
			extract($query, EXTR_OVERWRITE);

			$result = $db->selectCollection($table)->find($conditions, $fields);
			return $result->sort($order)->limit($limit)->skip($page * $limit);
		});
	}

	public function update($query, $options = array()) {
		$params = compact('query', 'options');
		$conn =& $this->_connection;
		$db =& $this->_db;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, &$db) {
			extract($params);
			$params = $query->export($self);
			$data = $query->data();

			if ($db->selectCollection($params['table'])->update($params['conditions'], $data)) {
				$query->record()->invokeMethod('_update');
				return true;
			}
			return false;
		});
	}

	public function delete($query, $options) {
		$query = $query->export($this);
		extract($query, EXTR_OVERWRITE);
		return $this->_db->selectCollection($table)->remove($conditions);
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

	public function conditions($conditions, $context) {
		if ($conditions && ($context->type() == 'create' || $context->type() == 'update')) {
			if (isset($conditions['_id']) && !is_object($conditions['_id'])) {
				$conditions['_id'] = new MongoId($conditions['_id']);
			}
			return $conditions;
		}
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