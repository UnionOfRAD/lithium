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

	/**
	 * Stream resource for this socket context.
	 *
	 * @var mixed
	 */
	public $connection = null;

	/**
	 * Connection timeout value.
	 *
	 * @var int
	 */
	protected $_timeout = null;

	/**
	 * Opens the socket and sets its timeout value.
	 *
	 * @return boolean Success.
	 */
	public function open() {
		$this->timeout($this->_config['timeout']);
		return true;
	}

	/**
	 * Closes the socket connection.
	 *
	 * @return boolean Success.
	 */
	public function close() {
		if (is_resource($this->connection)) {
			return fclose($this->connection);
		}
		return true;
	}

	/**
	 * End of file test for this socket connection. Does not apply to this implementation.
	 *
	 * @return boolean Success.
	 */
	public function eof() {
		return true;
	}

	/**
	 * Reads from the socket. Does not apply to this implementation.
	 *
	 * @return void
	 */
	public function read() {
		return null;
	}

	/**
	 * Writes to the socket. Does not apply to this implementation.
	 *
	 * @param string $data Data to write.
	 * @return boolean Success
	 */
	public function write($data) {
		return true;
	}
	
	/**
	 * Sets the timeout on the socket *connection*.
	 *
	 * @param integer $time Seconds after the connection times out.
	 * @return booelan `true` if timeout has been set, `false` otherwise.
	 */
	public function timeout($time = null) {
		if ($time !== null) {
			$this->_timeout = $time;
		}
		return $this->_timeout;
	}
	
	/**
	 * Sets the encoding of the socket connection. Does not apply to this implementation.
	 *
	 * @param string $charset The character set to use.
	 * @return boolean `true` if encoding has been set, `false` otherwise.
	 */
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
	public function send($message, array $options = array()) {
		$defaults = array(
			'path' => null, 'classes' => array('response' => null),
			'context' => array(
				'ignore_errors' => true, 'timeout' => $this->_timeout
			)
		);
		$options += $defaults;

		if ($this->open() === false) {
			return false;
		}
		$url = is_object($message) ? $message->to('url') : $options['path'];
		$message = is_object($message) ? $message->to('context', $options['context']) : $message;

		if ($this->connection = fopen($url, 'r', false, stream_context_create($message))) {
			$meta = stream_get_meta_data($this->connection);
			$headers = $meta['wrapper_data'] ?: array();
			$message = isset($headers[0]) ? $headers[0] : null;
			$body = stream_get_contents($this->connection);
			$this->close();

			if (!$options['classes']['response']) {
				return $body;
			}
			return new $options['classes']['response'](compact('headers', 'body', 'message'));
		}
	}
}

?>