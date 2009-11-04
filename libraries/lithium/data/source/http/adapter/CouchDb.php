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
		$this->_connection->request->headers('Content-Type', 'application/json');
		if (!empty($data)) {
			$data = json_encode($data);
		}
		$params = array_filter(array($path, $data));
		return json_decode($this->_connection->invokeMethod($method, $params));
	}

	public function entities($class = null) {
		return $this->get();
	}

	public function describe($entity, $meta = array()) {
		if (!$this->_db) {
			$result = $this->get($entity);
			if ($result->error == 'not_found') {
				$result = $this->put($entity);
				var_dump($result);
			}
			$this->_db = true;
		}
	}

	public function name($name) {
		return $name;
	}

	public function create($query, $options = array()) {
		$params = compact('query', 'options');
		$conn =& $this->_connection;

		return $this->_filter(__METHOD__, $params, function($self, $params) {
			extract($params);
			$params = $query->export($self);
			$data = $query->data();
			$id = null;
			if ($data['id']) {
				$id = '/' . $data['id'];
				$data += array('_id' => (string)$data['id']);
				unset($data['id']);
			}
			$result = $self->put($params['table'] . $id, $data);

			if ($result && $result->ok === true) {
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
			if ($conditions['id']) {
				$id = '/' . $conditions['id'];
				unset($conditions['id']);
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

			if ($self->put($params['table'], $params['conditions'] + $data)) {
				$query->record()->invokeMethod('_update');
				return true;
			}
			return false;
		});
	}

	public function delete($query, $options) {
		$query = $query->export($this);
		extract($query, EXTR_OVERWRITE);
		if ($conditions['id']) {
			$id = '/' . $conditions['id'];
			unset($conditions['id']);
		}
		return $this->_connection->delete($table, $conditions);
	}

	public function result($type, $resource, $context) {
		if (!is_object($resource)) {
			return null;
		}

		switch ($type) {
			case 'next':
				$result = $context->valid() ? $context->next() : null;
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