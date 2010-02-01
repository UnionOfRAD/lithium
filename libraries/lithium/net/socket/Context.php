<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\socket;

/**
 * A socket adapter that uses PHP stream contexts.
 */
class Context extends \lithium\net\Socket {

	protected $_connection = null;

	public function open() {
		return true;
	}

	public function close() {
		if (is_resource($this->_connection)) {
			return fclose($this->_connection);
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
	 * Send request and return response data
	 *
	 * @param string $message
	 * @param array $options
	 * @return string
	 */
	public function send($message, $options = array()) {
		$defaults = array('path' => null, 'classes' => array('response' => null));
		$options += $defaults;

		if ($this->open() === false) {
			return false;
		}
		$url = is_object($message) ? $message->to('url') : $options['path'];
		$message = is_object($message) ? $message->to('context') : $message;

		if ($this->_connection = fopen($url, 'r', false, stream_context_create($message))) {
			$meta = stream_get_meta_data($this->_connection);
			$headers = $meta['wrapper_data'] ?: array();
			$message = isset($headers[0]) ? $headers[0] : null;
			$body = stream_get_contents($this->_connection);
			$this->close();

			if (!$options['classes']['response']) {
				return $body;
			}
			return new $options['classes']['response'](compact('headers', 'body', 'message'));
		}
	}
}

?>