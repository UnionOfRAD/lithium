<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\http\adapter;

use \Exception;

/**
 * CouchDb adapter
 *
 */
class CouchDb extends \lithium\data\source\Http {

	/**
	 * increment value of current result set loop
	 * used by `result` to handle rows of json responses
	 *
	 * @var string
	 */
	protected $_iterator = 0;
	/**
	 * True if Database exists
	 *
	 * @var boolean
	 */
	protected $_db = false;

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('port' => 5984);
		$config = (array) $config + $defaults;
		parent::__construct($config);
	}

	/**
	 * Ensures that the server connection is closed and resources are freed when the adapter
	 * instance is destroyed.
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
		return array('meta' => array('key' => 'id'), 'classes' => array(
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
		list($path, $data, $options) = ($params + array('/', array(), array()));
		return json_decode($this->_connection->{$method}($path, $data, $options));
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
			if (!$this->_db) {
				if (isset($result->error)) {
					if ($result->error == 'not_found') {
						$result = $this->put($entity);
					}
				}
				if (isset($result->ok) || isset($result->db_name)) {
					$this->_db = true;
				}
			}
		}
		if (!$this->_db) {
			throw new Exception("{$entity} is not available.");
		}

		return array('id' => array(), 'rev' => array());
	}

	/**
	 * Quotes identifiers.
	 *
	 * CouchDb does not need identifiers quoted, so this method simply returns the identifier.
	 *
	 * @param string $name The identifier to quote.
	 * @return string The quoted identifier.
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
		$conn =& $this->_connection;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn) {
			extract($params);
			$options = $query->export($self);
			extract($options, EXTR_OVERWRITE);
			$data = $query->data();

			$id = null;

			if (!empty($data['id'])) {
				$id = '/' . $data['id'];
				$data['_id'] = (string) $data['id'];
				$result = $conn->put($table . $id, $data, array('type' => 'json'));
			} else {
				$result = $conn->post($table, $data, array('type' => 'json'));
			}
			$result = is_string($result) ? json_decode($result) : $result;
			$result = (object) $self->result('next', $result, $query);

			if ($success = (isset($result->id) || (isset($result->ok) && $result->ok === true))) {
				$query->data($data + (array) $result);
				$query->record()->invokeMethod('_update', array($result->id));
			}
			return $success;
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
		$conn =& $this->_connection;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn) {
			extract($params);
			$options = $query->export($self);

			extract($options, EXTR_OVERWRITE);
			extract($conditions, EXTR_OVERWRITE);

			if (empty($path) && empty($conditions)) {
				$path = '/_all_docs';
			}
			return json_decode($conn->get($table . $path, $conditions + $limit + $order));
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
		$conn =& $this->_connection;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn) {
			extract($params);
			$options = $query->export($self);
			extract($options, EXTR_OVERWRITE);
			extract($conditions, EXTR_OVERWRITE);
			$data = $query->data();
			if (empty($data['_id']) && !empty($data['id'])) {
				$data['_id'] = $data['id'];
				$data['_rev'] = $data['rev'];
				unset($data['id'], $data['rev']);
			}
			$result = $conn->put($table . $path, $conditions + $data, array('type' => 'json'));
			$result = is_string($result) ? json_decode($result) : $result;

			if ($success = (isset($result->_id) || (isset($result->ok) && $result->ok === true))) {
				$query->record()->invokeMethod('_update');
				return true;
			}

			if (isset($result->error) && $result->error === 'conflict') {
				return $self->read($query, $options);
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
	public function delete($query, $options = array()) {
		$params = compact('query', 'options');
		$conn =& $this->_connection;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn) {
			extract($params);
			$options = $query->export($self);
			extract($options, EXTR_OVERWRITE);
			extract($conditions, EXTR_OVERWRITE);
			$data = $query->data();

			if (!empty($data['rev'])) {
				$conditions['rev'] = $data['rev'];
			}
			$result = json_decode($conn->delete($table . $path, $conditions));
			return (isset($result->ok) && $result->ok === true);
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
		$result = null;

		switch ($type) {
			case 'next':
				if (!isset($resource->rows)) {
					$result = (array) $resource;
				} elseif (isset($resource->rows[$this->_iterator])) {
					$result = (array) $resource->rows[$this->_iterator]->value;
					$result['id'] = $resource->rows[$this->_iterator]->id;
					if (isset($resource->rows[$this->_iterator]->key)) {
						$result['key'] = $resource->rows[$this->_iterator]->key;
					}
					$this->_iterator++;
				} else {
					$this->_iterator = 0;
				}
				if (isset($result['_id'])) {
					$result['id'] = $result['_id'];
					unset($result['_id']);
					if (isset($result['_rev'])) {
						$result['rev'] = $result['_rev'];
						unset($result['_rev']);
					}
				}
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

	/**
	 * handle conditions
	 *
	 * @param string $conditions
	 * @param string $context
	 * @return array
	 */
	public function conditions($conditions, $context) {
		$path = null;
		$paths = array('design', 'view');
		foreach ($paths as $element) {
			if (isset($conditions[$element])) {
				$path .= "/_{$element}/$conditions[$element]";
				unset($conditions[$element]);
			}
		}
		if (isset($conditions['id'])) {
			$path = "/{$conditions['id']}";
			unset($conditions['id']);
		} elseif (isset($conditions['_id'])) {
			$path = "/{$conditions['_id']}";
			unset($conditions['_id']);
		}
		$conditions = array_filter((array) $conditions);
		return compact('path', 'conditions');
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
		return compact('limit') ?: array();
	}

	/**
	 * order for query
	 *
	 * @param string $order
	 * @param string $context
	 * @return array
	 */
	function order($order, $context) {
		return (array) $order ?: array();
	}
}

?>