<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\net\socket;

use lithium\core\Libraries;
use lithium\net\http\Message;

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
 * @link http://php.net/curl.installation.php
 * @see lithium\net\socket\Stream
 */
class Curl extends \lithium\net\Socket {

	/**
	 * Contains options that will be passed to `curl_setopt_array` before
	 * `read` and `write` operations. These options should be set by
	 * using the `set` method.
	 *
	 * @link http://php.net/function.curl-setopt.php PHP Manual: curl_setopt()
	 * @see lithium\net\socket\Curl::set()
	 * @var array
	 */
	public $options = [];

	protected $_isConnected = false;

	/**
	 * Constructor.
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['ignoreExpect' => true];
		parent::__construct($config + $defaults);
	}

	/**
	 * Opens a curl connection and initializes the internal resource handle.
	 *
	 * @param array $options update the config settings
	 *         if $options['options'] exists, will be passed to $this->set()
	 * @return mixed Returns `false` if the socket configuration does not contain the
	 *         `'scheme'` or `'host'` settings, or if configuration fails, otherwise returns a
	 *         resource stream.
	 */
	public function open(array $options = []) {
		$this->options = [];
		parent::open($options);
		$config = $this->_config;

		if (empty($config['scheme']) || empty($config['host'])) {
			return false;
		}
		if (!empty($config['options'])) {
			$this->set($config['options']);
		}

		$url = "{$config['scheme']}://{$config['host']}";
		$this->_resource = curl_init($url);
		$this->set([
			CURLOPT_PORT => $config['port'],
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true
		]);

		if (!is_object($this->_resource)) {
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
		unset($this->_resource);
		$this->_resource = null;
		return true;
	}

	/**
	 * EOF is unimplemented for this socket adapter.
	 *
	 * @return null
	 */
	public function eof() {
		return null;
	}

	/**
	 * Reads data from the curl connection.
	 * The `read` method will utilize the curl options that have been set.
	 *
	 * @link http://php.net/function.curl-exec.php PHP Manual: curl_exec()
	 * @return mixed Boolean false if the resource handle is unavailable, and the result
	 *         of `curl_exec` otherwise.
	 */
	public function read() {
		if (!is_object($this->_resource)) {
			return false;
		}
		return curl_exec($this->_resource);
	}

	/**
	 * Writes data to curl options
	 *
	 * @param array|\lithium\net\Message $data
	 * @return boolean
	 */
	public function write($data = null) {
		if (!is_object($this->_resource)) {
			return false;
		}
		if (!is_object($data)) {
			$data = Libraries::instance(
				null, 'request', (array) $data + $this->_config, $this->_classes
			);
		}
		$this->set(CURLOPT_URL, $data->to('url'));

		if ($data instanceof Message) {
			if (!empty($this->_config['ignoreExpect'])) {
				$data->headers('Expect', ' ');
			}
			if (isset($data->headers)) {
				$this->set(CURLOPT_HTTPHEADER, $data->headers());
			}
			if (isset($data->method) && $data->method === 'POST') {
				$this->set([CURLOPT_POST => true, CURLOPT_POSTFIELDS => $data->body()]);
			}
			if (isset($data->method) && in_array($data->method,['PUT','PATCH','DELETE'])) {
				$this->set([
					CURLOPT_CUSTOMREQUEST => $data->method,
					CURLOPT_POSTFIELDS => $data->body()
				]);
			}
		}
		return (boolean) curl_setopt_array($this->_resource, $this->options);
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
		if (!is_object($this->_resource)) {
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
	public function encoding($charset) {}

	/**
	 * Sets the options to be used in subsequent curl requests.
	 *
	 * @link http://php.net/curl.constants.php PHP Manual: cURL Constants
	 * @param array $flags If $values is an array, $flags will be used as the
	 *        keys to an associative array of curl options. If $values is not set,
	 *        then $flags will be used as the associative array.
	 * @param array $value If set, this array becomes the values for the
	 *        associative array of curl options.
	 * @return void
	 */
	public function set($flags, $value = null) {
		if ($value !== null) {
			$flags = [$flags => $value];
		}
		$this->options = $flags + $this->options;
	}
}

?>