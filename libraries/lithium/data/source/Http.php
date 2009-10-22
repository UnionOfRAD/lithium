<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use \lithium\core\Libraries;

/**
 * Http class to access data sources using Socket classes
 */
class Http extends \lithium\data\Source {

	/**
	 * Fully-namespaced class references
	 *
	 * @var array
	 */
	protected $_classes = array(
		'request' => '\lithium\http\Request',
		'response' => '\lithium\http\Response'
	);

	/**
	 * Socket connection
	 *
	 * @var object lithium\util\Socket
	 */
	protected $_connection = null;

	/**
	 * Is Connected?
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	/**
	 * Request Object
	 *
	 * @var object
	 */
	public $request =  null;

	/**
	 * Holds all parameters of the request
	 * Cast to object in the constructor
	 *
	 * @var object
	 */
	public $response = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'socket'    => 'Stream',
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
		$socket = $this->_config['socket'];
		if (!class_exists($socket)) {
			$socket = Libraries::locate('sockets.util', $this->_config['socket']);
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
	 * Disconnect from datasource
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

	public function entities($class = null) {
		return array();
	}

	public function describe($entity, $meta = array()) {
	}

	/**
	 * Send GET request
	 *
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
	 * @return string
	 */
	public function del($path = null, $params = array()) {
		if ($this->connect() === false) {
			return false;
		}
		$this->request->method = 'DELETE';
		return $this->_send($path, $params);
	}

	/**
	 * Create used by model to POST
	 *
	 * @return string
	 */
	public function create($record, $options = array()) {
		return $this->post();
	}

	/**
	 * Read used by model to GET
	 *
	 * @return string
	 */
	public function read($query = array(), $options = array()) {
		return $this->get();
	}

	/**
	 * Update used by model to PUT
	 *
	 * @return string
	 */
	public function update($query, $options = array()) {
		return $this->put();
	}

	/**
	 * Used by model to DELETE
	 *
	 * @return string
	 */
	public function delete($query, $options = array()) {
		return $this->del();
	}

	/**
	 * Prepares data for sending
	 *
	 * @return string
	 *
	 **/

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
	 * @return string
	 */
	protected function _send($path = null) {
		$this->request->path .= $path;
		$request = (string) $this->request;
		if ($this->_connection->write($request)) {
			$message = $this->_connection->read();
			$this->response = new $this->_classes['response'](compact('message'));
			$this->request = new $this->_classes['request']($this->_config);
			return $this->response->body();
		}
		return null;
	}
}

?>