<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace lithium\http;

use \lithium\core\Libraries;

/**
 * Basic Http Service
 *
 */
class Service extends \lithium\core\Object {

	protected $_autoConfig = array('classes' => 'merge');

	protected $_isConnected = false;

	/**
	 * Fully-namespaced class references
	 *
	 * @var array
	 */
	protected $_classes = array(
		'media'   => 'lithium\http\Media',
		'request'  => '\lithium\http\Request',
		'response' => '\lithium\http\Response',
		'socket'   => 'lithium\util\socket\Stream',
	);

	/**
	 * Holds the request and response used by _send
	 *
	 * @var object
	 */
	public $last = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'adapter'    => null,
			'persistent' => false,
			'protocol'   => 'tcp',
			'host'       => 'localhost',
			'version'    => '1.1',
			'auth'       => 'Basic',
			'login'      => 'root',
			'password'   => '',
			'port'       => 80,
			'timeout'    => 1,
			'encoding'   => 'UTF-8'
		);
		$config = (array)$config + $defaults;

		$config['auth'] = array(
			'method' => $config['auth'],
			'username' => $config['login'],
			'password' => $config['password']
		);
		parent::__construct($config);
	}

	protected function _init() {
		parent::_init();
		$socket = $this->_classes['socket'];
		if (!class_exists($socket)) {
			$socket = Libraries::locate('sockets.util', $this->_classes['socket']);
		}
		$this->_connection = new $socket($this->_config);
		$this->request = new $this->_classes['request']($this->_config);
	}

	/**
	 * Connect to datasource
	 *
	 * @return boolean
	 */
	public function connect() {
		if (!$this->_isConnected && $this->_connection->open()) {
			$this->_isConnected = true;
		}
		return $this->_isConnected;
	}

	/**
	 * Disconnect from socket
	 *
	 * @return boolean
	 */
	public function disconnect() {
		if ($this->_isConnected) {
			if ($this->_connection->close()) {
				$this->_isConnected = false;
			}
		}
		return !$this->_isConnected;
	}

	/**
	 * Send GET request
	 *
	 * @param string path
	 * @param array params
	 * @return string
	 */
	public function get($path = null, $params = array()) {
		if ($this->connect() === false) {
			return false;
		}
		$this->request->method = 'GET';
		$this->request->params = $params;
		return $this->_send($path);
	}

	/**
	 * Send POST request
	 *
	 * @param string path
	 * @param array data
	 * @return string
	 */
	public function post($path = null, $data = array()) {
		if ($this->connect() === false) {
			return false;
		}
		$this->request->method = 'POST';
		$this->_prepare($data);
		return $this->_send($path);
	}

	/**
	 * Send PUT request
	 *
	 * @param string path
	 * @param array data
	 * @return string
	 */
	public function put($path = null, $data = array()) {
		if ($this->connect() === false) {
			return false;
		}
		$this->request->method = 'PUT';
		$this->_prepare($data);
		return $this->_send($path);
	}

	/**
	 * Send DELETE request
	 *
	 * @param string path
	 * @param array params
	 * @return string
	 */
	public function delete($path = null, $params = array()) {
		if ($this->connect() === false) {
			return false;
		}
		$this->request->method = 'DELETE';
		return $this->_send($path, $params);
	}

	/**
	 * Reset request and disconnect
	 *
	 * @return object
	 */
	public function reset() {
		$this->last = (object)array('request' => $this->request, 'response' => $this->response);
		$this->request->build($this->_config);
		$this->disconnect();
		return $this->last;
	}

	/**
	 * Prepares data for sending
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _prepare($data = array()) {
		if (empty($data)) {
			return null;
		}
		if (is_array($data)) {
			$this->request->headers(array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			));
			$data = substr($this->request->queryString($data), 1);
		}
		return $this->request->body($data);
	}

	/**
	 * Send request and return response data
	 *
	 * @param string path
	 * @return string
	 */
	protected function _send($path = null) {
		$this->request->path .= $path;
		$request = (string) $this->request;
		if ($this->_connection->write($request)) {
			$message = $this->_connection->read();
			$this->response = new $this->_classes['response'](compact('message'));
			$this->reset();
			return $this->response->body();
		}
		return null;
	}
}
?>