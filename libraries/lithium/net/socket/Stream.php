<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\socket;

use \Exception;

/**
 * A PHP stream-based socket adapter
 *
 * This stream adapter provides the required method implementations of the abstract Socket class
 * for `open`, `close`, `read`, `write`, `timeout` `eof` and `encoding`.
 *
 * @see http://www.php.net/manual/en/book.stream.php
 * @see lithium\net\socket\Stream
 */
class Stream extends \lithium\net\Socket {

	/**
	 * Opens a socket and initializes the internal resource handle.
	 *
	 * @return mixed False if the Socket configuration does not contain the
	 *		   'protocol' or 'host' settings,  socket resource otherwise.
	 */
	public function open() {
		$config = $this->_config;

		if (empty($config['protocol']) || empty($config['host'])) {
			return false;
		}

		$host = "{$config['protocol']}://{$config['host']}:{$config['port']}";
		$flags = STREAM_CLIENT_CONNECT;

		if ($config['persistent']) {
			$flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
		}
		$this->_resource = stream_socket_client(
			$host, $errorCode, $errorMessage, $config['timeout'], $flags
		);

		if (!empty($errorCode) || !empty($errorMessage)) {
			throw new Exception($errorMessage, $errorCode);
		}

		$this->timeout($config['timeout']);

		if (!empty($config['encoding'])) {
			$this->encoding($config['encoding']);
		}

		return $this->_resource;
	}

	/**
	 * Closes the stream
	 *
	 * @return boolean True on closed connection
	 */
	public function close() {
		if (!is_resource($this->_resource)) {
			return true;
		}
		fclose($this->_resource);
		if (is_resource($this->_resource)) {
			$this->close();
		}
		return true;
	}

	/**
	 * Determines if the socket resource is at EOF.
	 *
	 * @return boolean True if resource pointer is at EOF, false otherwise.
	 */
	public function eof() {
		if (!is_resource($this->_resource)) {
			return true;
		}
		return feof($this->_resource);
	}

	/**
	 * Reads data from the stream resource
	 *
	 * @param integer $length If specified, will read up to $length bytes from the stream.
	 *        If no value is specified, all remaining bytes in the buffer will be read.
	 * @param integer $offset Seek to the specified byte offset before reading.
	 * @return string Returns string read from stream resource on success, false otherwise.
	 */
	public function read($length = null, $offset = null) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		return is_null($length) ? stream_get_contents($this->_resource) : stream_get_contents(
			$this->_resource, $length, $offset
		);
	}

	/**
	 * writes data to the stream resource
	 *
	 * @param string $data The string to be written.
	 * @return mixed False on error, number of bytes written otherwise.
	 */
	public function write($data) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		return fwrite($this->_resource, $data, strlen($data));
	}

	/**
	 * Set timeout period on a stream
	 *
	 * @param integer $time The timeout value in seconds.
	 * @return void
	 * @see http://www.php.net/manual/en/function.stream-set-timeout.php
	 */
	public function timeout($time) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		return stream_set_timeout($this->_resource, $time);
	}

	/**
	 * Sets the character set for stream encoding
	 *
	 * Note: This function only exists in PHP 6. For PHP < 6, this method will return void.
	 * @param string $charset
	 * @return mixed Returns void if `stream_encoding` method does not exist, boolean
	 *         result of `stream_encoding` otherwise.
	 * @see http://www.php.net/manual/en/function.stream-encoding.php
	 */
	public function encoding($charset) {
		if (!function_exists('stream_encoding')) {
			return false;
		}
		return is_resource($this->_resource) ? stream_encoding($this->_resource, $charset) : false;
	}

	/**
	 * Aggregates read and write methods into a coherent request response
	 *
	 * @param mixed $message array or object like `\lithium\net\http\Request`
	 * @param array $options
	 *                - path: path for the current request
	 *                - classes: array of classes to use
	 *                    - response: a class to use for the response
	 * @return boolean response string or object like `\lithium\net\http\Response`
	 */
	public function send($message, array $options = array()) {
		if ($this->write((string) $message)) {
			$message = $this->read();
			$response = new $options['classes']['response'](compact('message'));
			return $response;
		}
	}
}

?>