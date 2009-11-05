<?php
namespace lithium\data\source\http\adapter;

class CouchDb extends \lithium\data\source\Http {

	protected $_db = false;
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('port' => 35984);
		$config = (array)$config + $defaults;
		parent::__construct($config);
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

	public function __call($method, $params = array()) {
		$path = array_shift($params);
		$data = array_shift($params);
		$path = $path ?: '/';
		$data = (!empty($data)) ? json_encode($data) : null;
		$params = array_filter(array($path, $data));
		$this->_connection->request->headers('Content-Type', 'application/json');
		return json_decode($this->_connection->invokeMethod($method, $params));
	}

	public function entities($class = null) {
		//return $this->get();
	}

	public function describe($entity, $meta = array()) {
		if (!$this->_db) {
			$result = $this->get($entity);
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

	public function name($name) {
		return $name;
	}

	public function create($query, $options = array()) {
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) {
			extract($params);
			$params = $query->export($self);
			$data = $query->data();
			$id = null;
			if (!empty($data['_id'])) {
				$id = '/' . $data['_id'];
				$data['_id'] = (string) $data['_id'];
			}

			$result = $self->put($params['table'] . $id, $data);

			if (isset($result->ok) && $result->ok === true) {
				$query->record()->invokeMethod('_update', array($result->id));
				return true;
			}
			return false;
		});
	}

	public function read($query, $options = array()) {
		$defaults = array('return' => 'resource');
		$options += $defaults;
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) {
			extract($params);
			$query = $query->export($self);
			extract($query, EXTR_OVERWRITE);
			$id = null;
			if (!empty($conditions['_id'])) {
				$id = '/' . $conditions['_id'];
				unset($conditions['_id']);
			}
			$result = $self->get($table . $id, array_filter($conditions));
			return $result;
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

			$id = null;
			if (!empty($conditions['_id'])) {
				$id = '/' . $conditions['_id'];
				unset($conditions['_id']);
			}
			$self->put($params['table'] . $id, $params['conditions'] + $data);

			if (isset($result->ok) && $result->ok === true) {
				$query->record()->invokeMethod('_update');
				return true;
			}
			return false;
		});
	}

	public function delete($query, $options) {
		$query = $query->export($this);
		extract($query, EXTR_OVERWRITE);
		if (!empty($conditions['_id'])) {
			$id = '/' . $conditions['_id'];
			unset($conditions['_id']);
		}
		return $this->_connection->delete($table, $conditions);
	}

	public function result($type, $resource, $context) {
		if (!is_object($resource)) {
			return null;
		}
		return (array)$resource;
	}

	public function conditions($conditions, $context) {
		if ($conditions && ($context->type() == 'create' || $context->type() == 'update')) {
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