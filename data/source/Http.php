<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\source;

use lithium\aop\Filters;
use lithium\core\Libraries;
use lithium\data\model\Query;
use lithium\util\Text;

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
	protected $_autoConfig = ['classes' => 'merge', 'methods' => 'merge'];

	/**
	 * Fully-namespaced class references
	 *
	 * @var array
	 */
	protected $_classes = [
		'schema'  => 'lithium\data\Schema',
		'service' => 'lithium\net\http\Service',
		'relationship' => 'lithium\data\model\Relationship'
	];

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
	protected $_methods = [
		'create' => ['method' => 'post', 'path' => "/{:source}"],
		'read'   => ['method' => 'get', 'path' => "/{:source}"],
		'update' => ['method' => 'put', 'path' => "/{:source}/{:id}"],
		'delete' => ['method' => 'delete', 'path' => "/{:source}/{:id}"]
	];

	/**
	 * Constructor.
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'adapter'    => null,
			'persistent' => false,
			'scheme'     => 'http',
			'host'       => 'localhost',
			'version'    => '1.1',
			'auth'       => null,
			'login'      => '',
			'password'   => '',
			'port'       => 80,
			'timeout'    => 30,
			'encoding'   => 'UTF-8',
			'methods'    => []
		];
		$config = $config + $defaults;
		$config['username'] = $config['login'];
		parent::__construct($config);
	}

	protected function _init() {
		$config = $this->_config;
		unset($config['type']);
		$this->connection = Libraries::instance(null, 'service', $config, $this->_classes);
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
	 * Pass methods to service connection. Path and method are determined from Http::$_method. If
	 * not set, a GET request with the $method as the path will be used.
	 *
	 * @see lithium\data\source\Http::$_method
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 * @filter
	 */
	public function __call($method, $params) {
		if (!isset($this->_methods[$method])) {
			if (method_exists($this->connection, $method)) {
				return call_user_func_array([$this->connection, $method], $params);
			}
			$this->_methods[$method] = ['path' => "/{$method}"];
		}
		$params += [[], []];

		if (!is_object($params[0])) {
			$config = (array) $params[0];

			if (count($config) === count($config, COUNT_RECURSIVE)) {
				$config = ['data' => $config];
			}
			$params[0] = new Query($this->_methods[$method] + $config);
		}
		$params[0] = new Query($params[0]->export($this) + $this->_methods[$method]);

		return Filters::run($this, $method, $params, function($params) {
			list($query, $options) = $params;
			return $this->send($query, $options);
		});
	}

	/**
	 * Method to send to a specific resource.
	 *
	 * @param array $query a query object
	 * @param array $options array.
	 * @return mixed
	 */
	public function send($query = null, array $options = []) {
		$query = !is_object($query) ? new Query((array) $query) : $query;
		$method = $query->method() ?: "get";
		$path = $query->path();
		$data = $query->data();
		$insert = (array) $options + $data + $query->export($this);

		if (preg_match_all('/\{:(\w+)\}/', $path, $matches)) {
			$data = array_diff_key($data,  array_flip($matches[1]));
		}
		return $this->connection->{$method}(
			Text::insert($path, $insert, ['clean' => true]),
			$data + (array) $query->conditions() + ['limit' => $query->limit()],
			(array) $options
		);
	}

	/**
	 * Fake the connection since service is called for every method.
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
		return [];
	}

	/**
	 * Describe data source.
	 *
	 * @param string $entity
	 * @param array $fields
	 * @param array $meta
	 * @return array - returns an empty array
	 */
	public function describe($entity, $fields = [], array $meta = []) {
		return Libraries::instance(null, 'schema', compact('fields', 'meta'), $this->_classes);
	}

	/**
	 * Create function used to POST.
	 *
	 * @param object $query
	 * @param array $options
	 * @return mixed
	 * @filter
	 */
	public function create($query, array $options = []) {
		$query = !is_object($query) ? new Query() : $query;
		$query->method() ?: $query->method("post");
		$query->path() ?: $query->path("/{:source}");

		$params = [$query, $options];

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			list($query, $options) = $params;
			return $this->send($query, $options);
		});
	}

	/**
	 * Read used by model to GET.
	 *
	 * @param object $query
	 * @param array $options
	 * @return string
	 * @filter
	 */
	public function read($query, array $options = []) {
		$query = !is_object($query) ? new Query() : $query;
		$query->method() ?: $query->method("get");
		$query->path() ?: $query->path("/{:source}");

		$params = [$query, $options];

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			list($query, $options) = $params;
			return $this->send($query, $options);
		});
	}

	/**
	 * Update used by model to PUT.
	 *
	 * @param object $query
	 * @param array $options
	 * @return string
	 * @filter
	 */
	public function update($query, array $options = []) {
		$query = !is_object($query) ? new Query() : $query;
		$query->method() ?: $query->method("put");
		$query->path() ?: $query->path("/{:source}/{:id}");

		$params = [$query, $options];

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			list($query, $options) = $params;
			return $this->send($query, $options);
		});
	}

	/**
	 * Used by model to DELETE.
	 *
	 * @param object $query
	 * @param array $options
	 * @return string
	 * @filter
	 */
	public function delete($query, array $options = []) {
		$query = !is_object($query) ? new Query() : $query;
		$query->method() ?: $query->method("delete");
		$query->path() ?: $query->path("/{:source}/{:id}");

		$params = [$query, $options];

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			list($query, $options) = $params;
			return $this->send($query, $options);
		});
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
	public function relationship($class, $type, $name, array $options = []) {
		if (isset($this->_classes['relationship'])) {
			return Libraries::instance(
				null, 'relationship', compact('type', 'name') + $options, $this->_classes
			);
		}
		return null;
	}
}

?>