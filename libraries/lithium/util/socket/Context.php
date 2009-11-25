<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util\socket;

/**
 * A socket adapter that uses PHP stream contexts.
 */
class Context extends \lithium\util\Socket {

	protected $_connection = null;

	public function open() {
		return true;
	}

	public function close() {
		if (is_resource($this->_connection)) {
			fclose($this->_connection);
		}
		return true;
	}

	public function eof() {
		return true;
	}

	public function read() {
		return null;
	}

	public function write($data) {
		return true;
	}

	public function timeout($time = null) {
		return true;
	}

	public function encoding($encoding = null) {
		return false;
	}

	/**
	 * Connect to datasource
	 *
	 * @return boolean
	 */
	public function connect() {
		return true;
	}

	/**
	 * Disconnect from socket
	 *
	 * @return boolean
	 */
	public function disconnect() {
		return $this->close();
	}

	/**
	 * Send request and return response data
	 *
	 * @param string path
	 * @return string
	 */
	public function send($message, $options = array()) {
		$defaults = array('path' => null, 'responseClass' => null);
		$options += $defaults;

		if ($this->connect() === false) {
			return false;
		}
		$path = is_object($message) ? $message->to('link') : $options['path'];
		$message = is_object($message) ? $message->to('context') : $message;

		if ($this->_connection = fopen($path, 'r', false, stream_context_create($message))) {
			$meta = stream_get_meta_data($this->_connection);
			$headers = $meta['wrapper_data'] ?: array();
			$message = $headers[0] ?: null;
			$body = stream_get_contents($this->_connection);
			$this->close();

			if (!$options['responseClass']) {
				return $body;
			}
			return new $options['responseClass'](compact('headers', 'body', 'message'));
		}
	}
}

?>