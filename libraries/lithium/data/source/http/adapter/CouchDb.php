<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\http\adapter;

/**
 * CouchDb adapter
 *
 */
class CouchDb extends \lithium\data\source\Http {

	/**
	 * True if Database exists
	 *
	 * @var boolean
	 */
	protected $_db = false;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('port' => 5984);
		$config = (array)$config + $defaults;
		parent::__construct($config);
	}

	/**
	 * Deconstruct
	 *
	 * @return void
	 */
	public function __destruct() {
		if ($this->_isConnected) {
			$this->disconnect();
			$this->_db = false;
			unset($this->_connection);
		}
	}

	/**
	 * Configures a model class by overriding the default dependencies for `'recordSet'` and
	 * `'record'` , and sets the primary key to `'_id'`, in keeping with CouchDb conventions.
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

	/**
	 * Magic for passing methods to http service
	 *
	 * @param string $method
	 * @param string $params
	 * @return void
	 */
	public function __call($method, $params = array()) {
		$path = array_shift($params);
		$data = array_shift($params);
		$path = $path ?: '/';
		if ($method == 'post' || $method == 'put') {
			$this->_connection->request->headers('Content-Type', 'application/json');
			$data = (!empty($data)) ? json_encode($data) : null;
		}
		$params = array_filter(array($path, $data));
		return json_decode($this->_connection->invokeMethod($method, $params));
	}

	/**
	 * entities
	 *
	 * @param object $class
	 * @return void
	 */
	public function entities($class = null) {

	}

	/**
	 * Describe database, create if it does not exist
	 *
	 * @param string $entity
	 * @param string $meta
	 * @return void
	 */
	public function describe($entity, $meta = array()) {
		if (!$this->_db) {
			$result = $this->get($entity);
			if (isset($result->db_name)) {
				$this->_db = true;
			}
			if (isset($result->error)) {
				if ($result->error == 'not_found') {
					$result = $this->put($entity);
				}
			}
			if (isset($result->ok)) {
				$this->_db = true;
			}
		}
	}

	/**
	 * name
	 *
	 * @param string $name
	 * @return string
	 */
	public function name($name) {
		return $name;
	}

	/**
	 * Create new document
	 *
	 * @param string $query
	 * @param string $options
	 * @return boolean
	 */
	public function create($query, $options = array()) {
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) {
			extract($params);
			$options = $query->export($self);
			extract($options, EXTR_OVERWRITE);
			$data = $query->data();

			$id = null;

			if (!empty($data['_id'])) {
				$id = '/' . $data['_id'];
				$data['_id'] = (string) $data['_id'];
			}
			$result = $self->put($table . $id, $data);

			if (isset($result->ok) && $result->ok === true) {
				$query->record()->invokeMethod('_update', array($result->id));
				return true;
			}
			return false;
		});
	}

	/**
	 * Read from document
	 *
	 * @param string $query
	 * @param string $options
	 * @return object
	 */
	public function read($query, $options = array()) {
		$defaults = array('return' => 'resource');
		$options += $defaults;
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) {
			extract($params);
			$options = $query->export($self);
			extract($options, EXTR_OVERWRITE);
			$id = null;
			if (!empty($conditions['_id'])) {
				$id = '/' . $conditions['_id'];
				unset($conditions['_id']);
			}
			$result = $self->get($table . $id, array_filter($conditions));
			return $result;
		});
	}

	/**
	 * Update document
	 *
	 * @param string $query
	 * @param string $options
	 * @return boolean
	 */
	public function update($query, $options = array()) {
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) {
			extract($params);
			$options = $query->export($self);
			extract($options, EXTR_OVERWRITE);
			$data = $query->data();
			$id = null;

			if (!empty($conditions['_id'])) {
				$id = '/' . $conditions['_id'];
				unset($conditions['_id']);
			}
			$result = $self->put($table . $id, $conditions + $data);

			if (isset($result->ok) && $result->ok === true) {
				$query->record()->invokeMethod('_update');
				return true;
			}
			return false;
		});
	}

	/**
	 * Delete document
	 *
	 * @param string $query
	 * @param string $options
	 * @return boolean
	 */
	public function delete($query, $options) {
		$params = compact('query', 'options');
		$conn =& $this->_connection;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn) {
			extract($params);
			$options = $query->export($self);
			extract($options, EXTR_OVERWRITE);
			$data = $query->data();
			$id = null;

			if (!empty($conditions['_id'])) {
				$id = '/' . $conditions['_id'];
				unset($conditions['_id']);
			}
			if (!empty($data['_rev'])) {
				$conditions['rev'] = $data['_rev'];
			}

			$result = json_decode($conn->delete($table . $id, $conditions));

			if (isset($result->ok) && $result->ok === true) {
				return true;
			}
			return false;

		});
	}

	/**
	 * get result
	 *
	 * @param string $type
	 * @param string $resource
	 * @param string $context
	 * @return array
	 */
	public function result($type, $resource, $context) {
		if (!is_object($resource)) {
			return null;
		}
		return (array)$resource;
	}

	/**
	 * handle conditions
	 *
	 * @param string $conditions
	 * @param string $context
	 * @return array
	 */
	public function conditions($conditions, $context) {
		if ($conditions && ($context->type() == 'create' || $context->type() == 'update')) {
			return $conditions;
		}
		return $conditions ?: array();
	}

	/**
	 * fields for query
	 *
	 * @param string $fields
	 * @param string $context
	 * @return array
	 */
	public function fields($fields, $context) {
		return $fields ?: array();
	}

	/**
	 * limit for query
	 *
	 * @param string $limit
	 * @param string $context
	 * @return array
	 */
	public function limit($limit, $context) {
		return $limit ?: array();
	}

	/**
	 * order for query
	 *
	 * @param string $order
	 * @param string $context
	 * @return array
	 */
	function order($order, $context) {
		return $order ?: array();
	}
}
?>