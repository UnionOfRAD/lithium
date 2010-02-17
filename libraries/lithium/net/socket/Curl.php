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
 * @see http://www.php.net/manual/en/curl.installation.php
 * @see lithium\net\socket\Stream
 */
class Curl extends \lithium\net\Socket {

	/**
	 * Contains options that will be passed to curl_setopt_array before
	 * `read` and `write` operations. These options should be set by
	 * using the `set` method.
	 *
	 * @var array
	 * @see http://www.php.net/manual/en/function.curl-setopt.php
	 * @see lithium\net\socket\Curl::set()
	 */
	public $options = array();

	/**
	 * Opens a curl connection and initializes the internal resource handle
	 *
	 * @return mixed False if the Socket configuration does not contain the
	 *		   'protocol' or 'host' settings, curl resource otherwise.
	 */
	public function open() {
		$config = $this->_config;

		if (empty($config['protocol']) || empty($config['host'])) {
			return false;
		}

		$url = "{$config['protocol']}://{$config['host']}";
		$this->_resource = curl_init($url);
		curl_setopt($this->_resource, CURLOPT_PORT, $config['port']);
		curl_setopt($this->_resource, CURLOPT_HEADER, 0);
		curl_setopt($this->_resource, CURLOPT_RETURNTRANSFER, true);

		if (is_resource($this->_resource)) {
			$this->_isConnected = true;

			$this->timeout($config['timeout']);

			if (!empty($config['encoding'])) {
				$this->encoding($config['encoding']);
			}
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
	 * @return mixed Boolean false if the resource handle is unavailable, and the result
	 *         of `curl_exec` otherwise.
	 * @see http://php.net/manual/en/function.curl-exec.php
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
	 * @param array $data
	 * @return mixed Boolean false if the resource handle is unavailable, and the result
	 *         of `curl_exec` otherwise.
	 * @see http://php.net/manual/en/function.curl-exec.php
	 */
	public function write($data) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		curl_setopt_array($this->_resource, $this->options);
		return curl_exec($this->_resource);
	}

	/**
	 * A convenience method to set the curl CURLOPT_CONNECTTIMEOUT
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
	 * @param array $flags If $values is an array, $flags will be used as the
	 *        keys to an associative array of curl options. If $values is not set,
	 *        then $flags will be used as the associative array.
	 * @param array $value If set, this array becomes the values for the
	 *        associative array of curl options.
	 * @see http://www.php.net/manual/en/curl.constants.php for valid option constants
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