<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\net\socket;

use lithium\core\NetworkException;

/**
 * A PHP stream-based socket adapter.
 *
 * This stream adapter provides the required method implementations of the abstract `Socket` class
 * for the `open()`, `close()`, `read()`, `write()`, `timeout()` `eof()` and `encoding()` methods.
 *
 * @link http://php.net/book.stream.php
 * @see lithium\net\socket\Stream
 */
class Stream extends \lithium\net\Socket {

	/**
	 * Opens a socket and initializes the internal resource handle.
	 *
	 * @param array $options update the config settings
	 * @return mixed Returns `false` if the socket configuration does not contain the
	 *         `'scheme'` or `'host'` settings, or if configuration fails, otherwise returns a
	 *         resource stream. Throws exception if there is a network error.
	 */
	public function open(array $options = []) {
		parent::open($options);
		$config = $this->_config;

		if (!$config['scheme'] || !$config['host']) {
			return false;
		}
		$scheme = ($config['scheme'] !== 'udp') ? 'tcp' : 'udp';
		$port = $config['port'] ?: 80;
		$host = "{$scheme}://{$config['host']}:{$port}";
		$flags = STREAM_CLIENT_CONNECT;

		if ($config['persistent']) {
			$flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
		}
		$errorCode = $errorMessage = null;
		$this->_resource = stream_socket_client(
			$host, $errorCode, $errorMessage, $config['timeout'], $flags
		);
		if ($errorCode || $errorMessage) {
			throw new NetworkException($errorMessage);
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
		return !is_resource($this->_resource) || fclose($this->_resource);
	}

	/**
	 * Determines if the socket resource is at EOF.
	 *
	 * @return boolean Returns `true` if resource pointer is at its EOF, `false` otherwise.
	 */
	public function eof() {
		return is_resource($this->_resource) ? feof($this->_resource) : true;
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
		if (!$length) {
			return stream_get_contents($this->_resource);
		}
		return stream_get_contents($this->_resource, $length, $offset);
	}

	/**
	 * writes data to the stream resource
	 *
	 * @param string $data The string to be written.
	 * @return mixed False on error, number of bytes written otherwise.
	 */
	public function write($data = null) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		if (!is_object($data)) {
			$data = $this->_instance($this->_classes['request'], (array) $data + $this->_config);
		}
		return fwrite($this->_resource, (string) $data, strlen((string) $data));
	}

	/**
	 * Set timeout period on a stream.
	 *
	 * @link http://php.net/function.stream-set-timeout.php
	 *       PHP Manual: stream_set_timeout()
	 * @param integer $time The timeout value in seconds.
	 * @return boolean
	 */
	public function timeout($time) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		return stream_set_timeout($this->_resource, $time);
	}

	/**
	 * Sets the character set for stream encoding if possible. The `stream_encoding`
	 * function is not guaranteed to be available as it is seems as if it's experimental
	 * or just not officially documented. If the function is not available returns `false`.
	 *
	 * @link http://php.net/function.stream-encoding.php stream_encoding()
	 * @param string $charset
	 * @return boolean Returns `false` if `stream_encoding()` function does not exist, boolean
	 *         result of `stream_encoding()` otherwise.
	 */
	public function encoding($charset) {
		if (!function_exists('stream_encoding')) {
			return false;
		}
		return is_resource($this->_resource) ? stream_encoding($this->_resource, $charset) : false;
	}
}

?>