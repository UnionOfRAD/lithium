<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\http\adapter;

use lithium\core\ConfigException;

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
	 * Classes used by `CouchDb`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'service' => 'lithium\net\http\Service',
		'entity' => 'lithium\data\entity\Document',
		'set' => 'lithium\data\collection\DocumentSet',
	);

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array('port' => 5984, 'version' => 1);
		parent::__construct($config + $defaults);
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
			unset($this->connection);
		}
	}

	/**
	 * Configures a model class by setting the primary key to `'id'`, in keeping with CouchDb
	 * conventions.
	 *
	 * @see lithium\data\Model::$_meta
	 * @see lithium\data\Model::$_classes
	 * @param string $class The fully-namespaced model class name to be configured.
	 * @return Returns an array containing keys `'classes'` and `'meta'`, which will be merged with
	 *         their respective properties in `Model`.
	 */
	public function configureClass($class) {
		return array(
			'meta' => array('key' => 'id', 'locked' => false),
			'schema' => array(
				'id' => array('type' => 'integer'),
				'rev' => array('type' => 'string')
			)
		);
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
		return json_decode($this->connection->{$method}($path, $data, $options));
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
	public function describe($entity, array $meta = array()) {
		$database = $this->_config['database'];

		if (!$this->_db) {
			$result = $this->get($database);

			if (isset($result->db_name)) {
				$this->_db = true;
			}
			if (!$this->_db) {
				if (isset($result->error)) {
					if ($result->error == 'not_found') {
						$result = $this->put($database);
					}
				}
				if (isset($result->ok) || isset($result->db_name)) {
					$this->_db = true;
				}
			}
		}
		if (!$this->_db) {
			throw new ConfigException("{$entity} is not available.");
		}
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
	public function create($query, array $options = array()) {
		$defaults = array('model' => $query->model());
		$options += $defaults;
		$params = compact('query', 'options');
		$conn =& $this->connection;
		$config = $this->_config;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, $config) {
			$request = array('type' => 'json');
			$query = $params['query'];
			$options = $params['options'];
			$data = $query->data();
			$data += array('type' => $options['model']::meta('source'));

			if (isset($data['id'])) {
				return $self->update($query, $options);
			}
			$result = $conn->post($config['database'], $data, $request);
			$result = is_string($result) ? json_decode($result, true) : $result;

			if (isset($result['_id']) || (isset($result['ok']) && $result['ok'] === true)) {
				$result = $self->invokeMethod('_format', array($result, $options));
				$query->entity()->update($result['id'], $result);
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
	public function read($query, array $options = array()) {
		$defaults = array('return' => 'resource', 'model' => $query->model());
		$options += $defaults;
		$params = compact('query', 'options');
		$conn =& $this->connection;
		$config = $this->_config;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, $config) {
			$query = $params['query'];
			$options = $params['options'];
			$params = $query->export($self);
			extract($params, EXTR_OVERWRITE);
			list($_path, $conditions) = (array) $conditions;

			if (empty($_path)) {
				$_path = '_all_docs';
				$conditions['include_docs'] = 'true';
			}
			$data = (array) $conditions + (array) $limit + (array) $order;
			$result = json_decode($conn->get("{$config['database']}/{$_path}", $data));

			if (isset($result->error) && $result->error == 'not_found') {
				$result = array();
			}
			$stats['total_rows'] = isset($result->total_rows) ? $result->total_rows : null;
			$stats['offset'] = isset($result->offset) ? $result->offset : null;

			$options += compact('result', 'stats');
			return $self->invokeMethod('_result', array('set', $query, $options));
		});
	}

	/**
	 * Update document
	 *
	 * @param string $query
	 * @param string $options
	 * @return boolean
	 */
	public function update($query, array $options = array()) {
		$params = compact('query', 'options');
		$conn =& $this->connection;
		$config = $this->_config;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, $config) {
			$query = $params['query'];
			$options = $params['options'];
			$params = $query->export($self);
			extract($params, EXTR_OVERWRITE);
			list($_path, $conditions) = (array) $conditions;

			$data = $query->data();

			foreach (array('id', 'rev') as $key) {
				$data["_{$key}"] = isset($data[$key]) ? (string) $data[$key] : null;
				unset($data[$key]);
			}
			$data = (array) $conditions + array_filter((array) $data);
			$result = $conn->put("{$config['database']}/{$_path}", $data, array('type' => 'json'));
			$result = is_string($result) ? json_decode($result, true) : $result;

			if (isset($result['_id']) || (isset($result['ok']) && $result['ok'] === true)) {
				$result = $self->invokeMethod('_format', array($result, $options));
				$query->entity()->update($result['id'], array('rev' => $result['rev']));
				return true;
			}
			if (isset($result['error'])) {
				$query->entity()->errors(array($result['error']));
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
	public function delete($query, array $options = array()) {
		$params = compact('query', 'options');
		$conn =& $this->connection;
		$config = $this->_config;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, $config) {
			$query = $params['query'];
			$params = $query->export($self);
			list($_path, $conditions) = $params['conditions'];
			$data = $query->data();

			if (!empty($data['rev'])) {
				$conditions['rev'] = $data['rev'];
			}
			$result = json_decode($conn->delete("{$config['database']}/{$_path}", $conditions));
			return (isset($result->ok) && $result->ok === true);
		});
	}

	/**
	 * Executes calculation-related queries, such as those required for `count`.
	 *
	 * @param string $type Only accepts `count`.
	 * @param mixed $query The query to be executed.
	 * @param array $options Optional arguments for the `read()` query that will be executed
	 *        to obtain the calculation result.
	 * @return integer Result of the calculation.
	 */
	public function calculation($type, $query, array $options = array()) {
		return $this->read($query, $options)->stats('total_rows');
	}

	/**
	 * Returns a newly-created `Document` object, bound to a model and populated with default data
	 * and options.
	 *
	 * @param string $model A fully-namespaced class name representing the model class to which the
	 *               `Document` object will be bound.
	 * @param array $data The default data with which the new `Document` should be populated.
	 * @param array $options Any additional options to pass to the `Document`'s constructor
	 * @return object Returns a new, un-saved `Document` object bound to the model class specified
	 *         in `$model`.
	 */
	public function item($model, array $data = array(), array $options = array()) {
		return parent::item($model, $this->_format($data), $options);
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
		if (!is_object($resource) || isset($resource->error)) {
			return;
		}
		switch ($type) {
			case 'next':
				if (!isset($resource->rows)) {
					return $this->_format((array) $resource);
				}
				if (isset($resource->rows[$this->_iterator]->doc)) {
					return $this->_format((array) $resource->rows[$this->_iterator++]->doc);
				}
				if (isset($resource->rows[$this->_iterator]->value)) {
					$data = (array) $resource->rows[$this->_iterator]->value;
					$data['id'] = $resource->rows[$this->_iterator++]->id;
					return $this->_format($data);
				}
			break;
			case 'close':
				unset($resource);
				$this->_iterator = 0;
			break;
		}
		return;
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
		if (isset($conditions['design'])) {
			$paths = array('design', 'view');
			foreach ($paths as $element) {
				if (isset($conditions[$element])) {
					$path .= "_{$element}/{$conditions[$element]}/";
					unset($conditions[$element]);
				}
			}
		}
		if (isset($conditions['id'])) {
			$path = "{$conditions['id']}";
			unset($conditions['id']);
		}
		if (isset($conditions['path'])) {
			$path = "{$conditions['path']}";
			unset($conditions['path']);
		}
		return array($path, $conditions);
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

	/**
	 * With no parameter, always returns `true`, since CouchDB only depends on HTTP. With a
	 * parameter, queries for a specific supported feature.
	 *
	 * @param string $feature Test for support for a specific feature, i.e. `"transactions"` or
	 *               `"arrays"`.
	 * @return boolean Returns `true` if the particular feature support is enabled, otherwise
	 *         `false`.
	 */
	public static function enabled($feature = null) {
		if (!$feature) {
			return true;
		}
		$features = array(
			'arrays' => true,
			'transactions' => false,
			'booleans' => true,
			'relationships' => false,
		);
		return isset($features[$feature]) ? $features[$feature] : null;
	}

	/**
	 * Formats a CouchDb result set into a standard result to be passed to item
	 *
	 * @param string $data data returned from query
	 * @param string $options
	 * @return void
	 */
	protected function _format(array $data) {
		if (isset($data['_id'])) {
			$data['id'] = $data['_id'];
		}
		if (isset($data['_rev'])) {
			$data['rev'] = $data['_rev'];
		}
		unset($data['_id'], $data['_rev']);
		return $data;
	}

	/**
	 * Handle the result from read
	 *
	 * @param string $type
	 * @param string $query
	 * @param string $config
	 * @return void
	 */
	protected function _result($type, $query, $config = array()) {
		$defaults = array('exists' => true);
		$config = compact('query') + $config + $defaults;
		$class = $this->_classes[$type];
		return new $class($config);
	}
}

?>