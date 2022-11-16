<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\net\http;

use lithium\core\Libraries;
use lithium\core\AutoConfigurable;
use lithium\core\ClassNotFoundException;

/**
 * Basic Http Service.
 *
 */
class Service {

	use AutoConfigurable;

	/**
	 * The `Socket` instance used to send `Service` calls.
	 *
	 * @var lithium\net\Socket
	 */
	public $connection = null;

	/**
	 * Holds the last request and response object
	 *
	 * @var object
	 */
	public $last = null;

	/**
	 * Auto config
	 *
	 * @var array
	 */
	protected $_autoConfig = ['classes' => 'merge', 'responseTypes'];

	/**
	 * Array of closures that return various pieces of information about an HTTP response.
	 *
	 * @var array
	 */
	protected $_responseTypes = [];

	/**
	 * Indicates whether `Service` can connect to the HTTP endpoint for which it is configured.
	 * Defaults to true until a connection attempt fails.
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	/**
	 * Fully-name-spaced class references to `Service` class dependencies.
	 *
	 * @var array
	 */
	protected $_classes = [
		'media'    => 'lithium\net\http\Media',
		'request'  => 'lithium\net\http\Request',
		'response' => 'lithium\net\http\Response'
	];

	/**
	 * Constructor. Initializes a new `Service` instance with the default HTTP request settings
	 * and transport- and format-handling classes.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'persistent'` _boolean_
	 *        - `'scheme'` _string_
	 *        - `'host'` _string_
	 *        - `'port'` _integer_
	 *        - `'timeout'` _integer_
	 *        - `'auth'` _boolean_
	 *        - `'username'` _string_
	 *        - `'password'` _string_
	 *        - `'encoding'` _string_
	 *        - `'socket'` _string_
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'persistent' => false,
			'scheme'     => 'http',
			'host'       => 'localhost',
			'port'       => null,
			'timeout'    => 30,
			'auth'       => null,
			'username'   => null,
			'password'   => null,
			'encoding'   => 'UTF-8',
			'socket'     => 'Context'
		];
		$this->_autoConfig($config + $defaults, $this->_autoConfig);
		$this->_autoInit($config);
	}

	/**
	 * Initialize connection.
	 *
	 * @return void
	 */
	protected function _init() {
		$config = ['classes' => $this->_classes] + $this->_config;

		try {
			$this->connection = Libraries::instance('socket', $config['socket'], $config);
		} catch(ClassNotFoundException $e) {
			$this->connection = null;
		}
		$this->_responseTypes += [
			'headers' => function($response) { return $response->headers; },
			'body' => function($response) { return $response->body(); },
			'code' => function($response) { return $response->status['code']; }
		];
	}

	/**
	 * Magic method to handle other HTTP methods.
	 *
	 * @param string $method
	 * @param string $params
	 * @return mixed
	 */
	public function __call($method, $params = []) {
		array_unshift($params, $method);
		return call_user_func_array([$this, 'send'], $params);
	}

	/**
	 * Send HEAD request.
	 *
	 * @param string $path
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function head($path = null, $data = [], array $options = []) {
		$defaults = ['return' => 'headers', 'type' => false];
		return $this->send(__FUNCTION__, $path, $data, $options + $defaults);
	}

	/**
	 * Send GET request.
	 *
	 * @param string $path
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function get($path = null, $data = [], array $options = []) {
		$defaults = ['type' => false];
		return $this->send(__FUNCTION__, $path, $data, $options + $defaults);
	}

	/**
	 * Send POST request.
	 *
	 * @param string $path
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function post($path = null, $data = [], array $options = []) {
		return $this->send(__FUNCTION__, $path, $data, $options);
	}

	/**
	 * Send PUT request.
	 *
	 * @param string $path
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function put($path = null, $data = [], array $options = []) {
		return $this->send(__FUNCTION__, $path, $data, $options);
	}

	/**
	 * Send PATCH request.
	 *
	 * @param string $path
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function patch($path = null, $data = [], array $options = []) {
		return $this->send(__FUNCTION__, $path, $data, $options);
	}

	/**
	 * Send DELETE request.
	 *
	 * @param string $path
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function delete($path = null, $data = [], array $options = []) {
		$defaults = ['type' => false];
		return $this->send(__FUNCTION__, $path, $data, $options + $defaults);
	}

	/**
	 * Send request and return response data. Will open the connection if
	 * needed and always close it after sending the request.
	 *
	 * Will automatically authenticate when receiving a `401` HTTP status code
	 * then continue retrying sending initial request.
	 *
	 * @param string $method
	 * @param string $path
	 * @param array $data the parameters for the request. For GET/DELETE this is the query string
	 *        for POST/PUT this is the body
	 * @param array $options passed to request and socket
	 * @return string
	 */
	public function send($method, $path = null, $data = [], array $options = []) {
		$defaults = ['return' => 'body'];
		$options += $defaults;
		$request = $this->_request($method, $path, $data, $options);
		$options += ['message' => $request];

		if (!$this->connection || !$this->connection->open($options)) {
			return;
		}
		$response = $this->connection->send($request, $options);
		$this->connection->close();

		if ($response->status['code'] == 401 && ($auth = $response->digest())) {
			$request->auth = $auth;
			$this->connection->open(['message' => $request] + $options);
			$response = $this->connection->send($request, $options);
			$this->connection->close();
		}
		$this->last = (object) compact('request', 'response');

		$handlers = $this->_responseTypes;
		$handler = isset($handlers[$options['return']]) ? $handlers[$options['return']] : null;

		return $handler ? $handler($response) : $response;
	}

	/**
	 * Instantiates a request object (usually an instance of `http\Request`) and tests its
	 * properties based on the request type and data to be sent.
	 *
	 * @param string $method The HTTP method of the request, i.e. `'GET'`, `'HEAD'`, `'OPTIONS'`,
	 *        etc. Can be passed in upper- or lower-case.
	 * @param string $path The
	 * @param string $data
	 * @param string $options
	 * @return object Returns an instance of `http\Request`, configured with an HTTP method, query
	 *         string or POST/PUT/PATCH data, and URL.
	 */
	protected function _request($method, $path, $data, $options) {
		$defaults = ['type' => 'form'];
		$options += $defaults + $this->_config;

		$request = Libraries::instance(null, 'request', $options, $this->_classes);
		$request->path = str_replace('//', '/', "{$request->path}{$path}");
		$request->method = $method = strtoupper($method);
		$hasBody = in_array($method, ['POST', 'PUT', 'PATCH']);
		$hasBody ? $request->body($data) : $request->query = $data;
		return $request;
	}
}

?>