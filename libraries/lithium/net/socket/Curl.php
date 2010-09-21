<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\socket;

/**
 * A Curl-based socket adapter
 *
 * This curl adapter provides the required method implementations of the abstract Socket class
 * for `open`, `close`, `read`, `write`, `timeout` `eof` and `encoding`.
 *
 * Your PHP installation must have been compiled with the `--with-curl[=DIR]` directive. If this
 * is not the case, you must either recompile PHP with the proper configuration flags to enable
 * curl, or you may use the `Stream` adapter that is also included with the Lithium core.
 *
 * @link http://www.php.net/manual/en/curl.installation.php
 * @see lithium\net\socket\Stream
 */
class Curl extends \lithium\net\Socket {

	/**
	 * Contains options that will be passed to `curl_setopt_array` before
	 * `read` and `write` operations. These options should be set by
	 * using the `set` method.
	 *
	 * @link http://www.php.net/manual/en/function.curl-setopt.php PHP Manual: curl_setopt()
	 * @see lithium\net\socket\Curl::set()
	 * @var array
	 */
	public $options = array();

	/**
	 * Opens a curl connection and initializes the internal resource handle.
	 *
	 * @return mixed Returns `false` if the socket configuration does not contain the
	 *         `'scheme'` or `'host'` settings, or if configuration fails, otherwise returns a
	 *         curl resource.
	 */
	public function open() {
		$config = $this->_config;

		if (empty($config['scheme']) || empty($config['host'])) {
			return false;
		}

		$url = "{$config['scheme']}://{$config['host']}";
		$this->_resource = curl_init($url);
		curl_setopt($this->_resource, CURLOPT_PORT, $config['port']);
		curl_setopt($this->_resource, CURLOPT_HEADER, true);
		curl_setopt($this->_resource, CURLOPT_RETURNTRANSFER, true);

		if (!is_resource($this->_resource)) {
			return false;
		}
		$this->_isConnected = true;
		$this->timeout($config['timeout']);

		if (isset($config['encoding'])) {
			$this->encoding($config['encoding']);
		}
		return $this->_resource;
	}

	/**
	 * Closes the curl connection.
	 *
	 * @return boolean True on closed connection
	 */
	public function close() {
		if (!is_resource($this->_resource)) {
			return true;
		}
		curl_close($this->_resource);

		if (is_resource($this->_resource)) {
			$this->close();
		}
		return true;
	}

	/**
	 * EOF is unimplemented for this socket adapter
	 *
	 */
	public function eof() {
		return null;
	}

	/**
	 * Reads data from the curl connection.
	 * The `read` method will utilize the curl options that have been set.
	 *
	 * @link http://php.net/manual/en/function.curl-exec.php PHP Manual: curl_exec()
	 * @return mixed Boolean false if the resource handle is unavailable, and the result
	 *         of `curl_exec` otherwise.
	 */
	public function read() {
		if (!is_resource($this->_resource)) {
			return false;
		}
		curl_setopt_array($this->_resource, $this->options);
		return curl_exec($this->_resource);
	}

	/**
	 * Reads data from the curl connection.
	 * The `read` method will utilize the curl options that have been set.
	 *
	 * @link http://php.net/manual/en/function.curl-exec.php PHP Manual: curl_exec()
	 * @param array $data
	 * @return mixed Boolean `false` if the resource handle is unavailable, and the result
	 *         of `curl_exec()` otherwise.
	 */
	public function write($data) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		curl_setopt_array($this->_resource, $this->options);
		return curl_exec($this->_resource);
	}

	/**
	 * A convenience method to set the curl `CURLOPT_CONNECTTIMEOUT`
	 * setting for the current connection. This determines the number
	 * of seconds to wait while trying to connect.
	 *
	 * Note: A value of 0 may be used to specify an indefinite wait time.
	 *
	 * @param integer $time The timeout value in seconds
	 * @return boolean False if the resource handle is unavailable or the
	 *         option could not be set, true otherwise.
	 */
	public function timeout($time) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		return curl_setopt($this->_resource, CURLOPT_CONNECTTIMEOUT, $time);
	}

	/**
	 * encoding() is currently unimplemented for this socket adapter
	 *
	 * @todo implement Curl::encoding($charset)
	 * @param string $charset
	 */
	public function encoding($charset) {
	}

	/**
	 * Sets the options to be used in subsequent curl requests.
	 *
	 * @link http://www.php.net/manual/en/curl.constants.php PHP Manual: cURL Constants
	 * @param array $flags If $values is an array, $flags will be used as the
	 *        keys to an associative array of curl options. If $values is not set,
	 *        then $flags will be used as the associative array.
	 * @param array $value If set, this array becomes the values for the
	 *        associative array of curl options.
	 * @return void
	 */
	public function set($flags, $value = null) {
		if ($value !== null) {
			$flags = array($flags => $value);
		}
		$this->options += $flags;
	}

	/**
	 * Aggregates read and write methods into a coherent request response
	 *
	 * @param mixed $message a request object based on `\lithium\net\Message`
	 * @param array $options
	 *              - '`response`': a fully-namespaced string for the response object
	 * @return object a response object based on `\lithium\net\Message`
	 */
	public function send($message, array $options = array()) {
		$defaults = array('response' => $this->_classes['response']);
		$options += $defaults;
		$this->set(CURLOPT_URL, $message->to('url'));

		if (isset($message->headers)) {
			$this->set(CURLOPT_HTTPHEADER, $message->headers());
		}
		if (isset($message->method) && $message->method == 'POST') {
			$this->set(array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $message->body()));
		}
		if ($message = $this->write($message)) {
			$message = $message ?: $this->read();
			return $this->_instance($options['response'], compact('message'));
		}
	}
}

?>