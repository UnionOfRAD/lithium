<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace lithium\http\service\adapter;

use \lithium\core\Libraries;

/**
 * Basic Http Service
 *
 */
class Socket extends \lithium\http\Service {

	/**
	 * Fully-namespaced class references to `Service` class dependencies.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'media'    => 'lithium\http\Media',
		'request'  => '\lithium\http\Request',
		'response' => '\lithium\http\Response',
		'socket'   => 'lithium\util\socket\Stream'
	);

	/**
	 * Connect to datasource
	 *
	 * @return boolean
	 */
	public function connect() {
		if (!$this->_connection) {
			$socket = Libraries::locate('sockets.util', $this->_classes['socket']);
			$this->_connection = new $socket($this->_config);
		}
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
			$this->_isConnected = !$this->_connection->close();
		}
		return !$this->_isConnected;
	}

	/**
	 * Send request and return response data
	 *
	 * @param string path
	 * @return string
	 */
	public function send($method, $path = null, $data = null, $options = array()) {
		$defaults = array('type' => 'form', 'return' => 'body');
		$options += $defaults;

		if ($this->connect() === false) {
			return false;
		}
		$request = $this->_request($method, $path, $data, $options);

		if ($this->_connection->write((string) $request)) {
			$message = $this->_connection->read();
			$response = new $this->_classes['response'](compact('message'));

			$this->last = (object)compact('request', 'response');
			$this->disconnect();

			return ($options['return'] == 'body') ? $response->body() : $response;
		}
	}
}

?>