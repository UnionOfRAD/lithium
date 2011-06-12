<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use lithium\util\String;

/**
 * Http class to access data sources using `lithium\net\http\Service`.
 */
class Http extends \lithium\data\Source {

	/**
	 * Service connection
	 *
	 * @var object lithium\net\http\Service
	 */
	public $connection = null;

	/**
	 * The set of array keys which will be auto-populated in the object's protected properties from
	 * constructor parameters.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('classes' => 'merge');

	/**
	 * Fully-namespaced class references
	 *
	 * @var array
	 */
	protected $_classes = array(
		'service' => 'lithium\net\http\Service',
		'relationship' => 'lithium\data\model\Relationship'
	);

	/**
	 * Is Connected?
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	/**
	 * List of methods and their corresponding HTTP method and path.
	 *
	 * @var array
	 */
	protected $_methods = array(
		'read'	 => array('method' => 'get', 'path' => "/{:source}"),
		'create' => array('method' => 'post', 'path' => "/{:source}"),
		'update' => array('method' => 'put', 'path' => "/{:source}/{:id}"),
		'delete' => array('method' => 'delete', 'path' => "/{:source}/{:id}")
	);

	/**
	 * Constructor
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'adapter'	 => null,
			'persistent' => false,
			'scheme'     => 'http',
			'host'       => 'localhost',
			'version'    => '1.1',
			'auth'       => null,
			'login'      => '',
			'password'   => '',
			'port'       => 80,
			'timeout'    => 30,
			'encoding'   => 'UTF-8'
		);
		$config = $config + $defaults;
		$config['username'] = $config['login'];
		parent::__construct($config);
	}

	protected function _init() {
		$config = $this->_config;
		unset($config['type']);
		$this->connection = $this->_instance('service', $config);
		parent::_init();
	}

	/**
	 * Pass properties to service connection
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		return $this->connection->{$property};
	}

	/**
	 * Pass methods to service connection.
	 *
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 * @filter
	 */
	public function __call($method, $params) {
		if (isset($this->_config['methods'][$method])) {
			$params += array(array(), array());
			$string = $this->_config['methods'][$method];

			if (!isset($string['path'])) {
				$string['path'] = $method;
			}
			$conn =& $this->connection;
			$filter = function($self, $params) use (&$conn, $string) {
				$data = in_array($string['method'], array('post', 'put'))
					? (array) $params[0] : array();
				$path = String::insert($string['path'], $data, array('clean' => true));
				return $conn->{$string['method']}($path, $data, $params[1]);
			};
			return $this->_filter(__METHOD__, $params, $filter);
		}
		return $this->connection->invokeMethod($method, $params);
	}

	/**
	 * Connect to the data-source.
	 *
	 * @return boolean
	 */
	public function connect() {
		if (!$this->_isConnected) {
			$this->_isConnected = true;
		}
		return $this->_isConnected;
	}

	/**
	 * Disconnect from socket.
	 *
	 * @return boolean
	 */
	public function disconnect() {
		if ($this->_isConnected && $this->connection !== null) {
			$this->_isConnected = false;
		}
		return !$this->_isConnected;
	}

	/**
	 * Returns available data sources (typically a list of REST resources collections).
	 *
	 * @param object $class
	 * @return array
	 */
	public function sources($class = null) {
		return array();
	}

	/**
	 * Describe data source.
	 *
	 * @param string $entity
	 * @param array $meta
	 * @return array - returns an empty array
	 */
	public function describe($entity, array $meta = array()) {
		return array();
	}

	/**
	 * undocumented function
	 *
	 * @param object $query
	 * @param array $options
	 * @return void
	 * @filter
	 */
	public function create($query, array $options = array()) {
		$params = compact('query', 'options');
		$config = $this->_config;

		if (!isset($this->_methods[__FUNCTION__])) {
			return null;
		}
		$method = $this->_methods[__FUNCTION__];
		$filter = function($self, $params) use ($config, $method) {
			$query = $params['query'];
			$options = $params['options'];
			$data = array();

			if ($query) {
				$options += array_filter($query->export($self), function($v) {
					return $v !== null;
				});
				$data = $query->data();
			}
			$path = String::insert($method['path'], $options, array('clean' => true));
			return $self->connection->{$method['method']}($path, $data, $options);
		};
		return $this->_filter(__METHOD__, $params, $filter);
	}

	/**
	 * Read used by model to GET.
	 *
	 * @param object $query
	 * @param array $options
	 * @return string
	 * @filter
	 */
	public function read($query, array $options = array()) {
		$params = compact('query', 'options');
		$conn =& $this->connection;

		if (!isset($this->_methods[__FUNCTION__])) {
			return null;
		}
		$method = $this->_methods[__FUNCTION__];
		$filter = function($self, $params) use (&$conn, $method) {
			$query = $params['query'];
			$options = $params['options'];
			$data = array();
			$defaults = array('conditions' => null, 'limit' => null);

			if ($query) {
				$options += array_filter($query->export($self), function($v) {
					return $v !== null;
				});
				$options += $defaults;
				$data = (array) $options['conditions'] + (array) $options['limit'];
			}
			$path = String::insert($method['path'], $options, array('clean' => true));
			return $conn->{$method['method']}($path, $data, $options);
		};
		return $this->_filter(__METHOD__, $params, $filter);
	}

	/**
	 * Update used by model to PUT.
	 *
	 * @param object $query
	 * @param array $options
	 * @return string
	 * @filter
	 */
	public function update($query, array $options = array()) {
		$params = compact('query', 'options');
		$conn =& $this->connection;

		if (!isset($this->_methods[__FUNCTION__])) {
			return null;
		}
		$method = $this->_methods[__FUNCTION__];
		$filter = function($self, $params) use (&$conn, $method) {
			$query = $params['query'];
			$options = $params['options'];
			$data = array();

			if ($query) {
				$options += array_filter($query->export($self), function($v) {
					return $v !== null;
				});
				$data = $query->data();
			}
			$path = String::insert($method['path'], $options + $data, array('clean' => true));
			return $conn->{$method['method']}($path, $data, $options);
		};
		return $this->_filter(__METHOD__, $params, $filter);
	}

	/**
	 * Used by model to DELETE.
	 *
	 * @param object $query
	 * @param array $options
	 * @return string
	 * @filter
	 */
	public function delete($query, array $options = array()) {
		$params = compact('query', 'options');
		$conn =& $this->connection;

		if (!isset($this->_methods[__FUNCTION__])) {
			return null;
		}
		$method = $this->_methods[__FUNCTION__];
		$filter = function($self, $params) use (&$conn, $method) {
			$query = $params['query'];
			$options = $params['options'];
			$data = array();

			if ($query) {
				$options += $query->export($self);
				$data = $query->data();
			}
			$path = String::insert($method['path'], $options + $data, array('clean' => true));
			return $conn->{$method['method']}($path, array(), $options);
		};
		return $this->_filter(__METHOD__, $params, $filter);
	}

	/**
	 * Defines or modifies the default settings of a relationship between two models.
	 *
	 * @param string $class
	 * @param string $type
	 * @param string $name
	 * @param array $options
	 * @return array Returns an array containing the configuration for a model relationship.
	 */
	public function relationship($class, $type, $name, array $options = array()) {
		if (isset($this->_classes['relationship'])) {
			$class = $this->_classes['relationship'];
			return ($class) ? new $class() : null;
		}
		return null;
	}

	public function name($name) {
		return $name;
	}
}

?>