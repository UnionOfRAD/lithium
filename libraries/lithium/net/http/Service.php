<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace lithium\net\http;

use \lithium\core\Libraries;

/**
 * Basic Http Service.
 *
 */
class Service extends \lithium\core\Object {

	/**
	 * The `Socket` instance used to send `Service` calls.
	 *
	 * @var \lithium\net\Socket
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
	protected $_autoConfig = array('classes' => 'merge');

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
	protected $_classes = array(
		'media'    => '\lithium\net\http\Media',
		'request'  => '\lithium\net\http\Request',
		'response' => '\lithium\net\http\Response',
		'socket'   => '\lithium\net\socket\Context'
	);

	/**
	 * Initializes a new `Service` instance with the default HTTP request settings and
	 * transport- and format-handling classes.
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'persistent' => false,
			'scheme'     => 'http',
			'host'       => 'localhost',
			'port'       => null,
			'timeout'    => 30,
			'auth'       => null,
			'username'   => null,
			'password'   => null,
			'encoding'   => 'UTF-8',
		);
		$config = (array) $config + $defaults;
		parent::__construct($config);
	}

	protected function _init() {
		parent::_init();
		$this->_classes['socket'] = Libraries::locate('socket.util', $this->_classes['socket']);
	}

	/**
	 * Send GET request.
	 *
	 * @param string $path
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function get($path = null, $data = array(), array $options = array()) {
		return $this->send(__FUNCTION__, $path, $data, $options);
	}

	/**
	 * Send POST request.
	 *
	 * @param string $path
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function post($path = null, $data = array(), array $options = array()) {
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
	public function put($path = null, $data = array(), array $options = array()) {
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
	public function delete($path = null, $data = array(), array $options = array()) {
		return $this->send(__FUNCTION__, $path, $data, $options);
	}

	/**
	 * Send request and return response data.
	 *
	 * @param string $method
	 * @param string $path
	 * @param array $data the parameters for the request. For GET/DELETE this is the query string
	 *        for POST/PUT this is the body
	 * @param array $options passed to request and socket
	 * @return string
	 */
	public function send($method, $path = null, $data = array(), array $options = array()) {
		$defaults = array('return' => 'body', 'classes' => $this->_classes);
		$options += $defaults + $this->_config;
		$request = $this->_request($method, $path, $data, $options);
		$options += array('message' => $request);
		$this->connection = $this->_instance('socket', $options);

		if (!$this->connection || !$this->connection->open()) {
			return;
		}
		$response = $this->connection->send($request, $options);
		$this->connection->close();
		$this->last = (object) compact('request', 'response');
		return ($options['return'] == 'body') ? $response->body() : $response;;
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
	 *         string or POST/PUT data, and URL.
	 */
	protected function _request($method, $path, $data, $options) {
		$defaults = array('type' => 'form', 'scheme' => $this->_config['scheme']);
		$options += $defaults;
		$request = $this->_instance('request', $this->_config + $options);
		$request->path = str_replace('//', '/', "{$request->path}{$path}");
		$request->method = $method = strtoupper($method);
		$media = $this->_classes['media'];
		$type = null;

		if (in_array($options['type'], $media::types()) && $data && !is_string($data)) {
			$type = $media::type($options['type']);
			$contentType = (array) $type['content'];
			$request->headers(array('Content-Type' => current($contentType)));
			$data = Media::encode($options['type'], $data, $options);
		}
		in_array($method, array('POST', 'PUT'))
			? $request->body($data) : $request->params = $data;
		return $request;
	}
}

?>