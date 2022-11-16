<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\net;

use lithium\core\Libraries;

use lithium\core\AutoConfigurable;

/**
 * Abstract class for connecting to sockets with various adapters.
 *
 * Currently, Curl, Stream and Context adapters are available.
 */
abstract class Socket {

	use AutoConfigurable;

	/**
	 * The resource for the current connection.
	 *
	 * @var object
	 */
	protected $_resource = null;

	/**
	 * The classes for the socket.
	 *
	 * @var array
	 */
	protected $_classes = [
		'request' => 'lithium\net\Message',
		'response' => 'lithium\net\Message'
	];

	/**
	 * Auto config.
	 *
	 * @var array
	 */
	protected $_autoConfig = ['classes' => 'merge'];

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'persistent'`: Use a persistent connection (defaults to `false`).
	 *        - `'protocol'`: Transfer protocol to use (defaults to `'tcp'`).
	 *        - `'host'`: Host name or address (defaults to `'localhost'`).
	 *        - `'login'`: Username for a login (defaults to `'root'`).
	 *        - `'password'`: Password for a login (defaults to `''`).
	 *        - `'port'`: Host port (defaults to `80`).
	 *        - `'timeout'`: Seconds after opening the socket times out (defaults to `30`).
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'persistent' => false,
			'scheme'     => 'tcp',
			'host'       => 'localhost',
			'port'       => 80,
			'timeout'    => 30
		];
		$this->_autoConfig($config + $defaults, $this->_autoConfig);
		$this->_autoInit($config);
	}

	/**
	 * Opens the socket and sets `Socket::$_resource`.
	 *
	 * @param array $options Update the config settings.
	 * @return mixed The open resource on success, `false` otherwise.
	 */
	public function open(array $options = []) {
		$this->_autoConfig($options + $this->_config, $this->_autoConfig);
		return false;
	}

	/**
	 * Closes the socket and unsets `Socket::$_resource`.
	 *
	 * @return boolean `true` on success, `false` otherwise.
	 */
	abstract public function close();

	/**
	 * Test for the end-of-file on the socket.
	 *
	 * @return boolean `true` if end has been reached, `false` otherwise.
	 */
	abstract public function eof();

	/**
	 * Reads from the socket.
	 *
	 * @return object `lithium\net\Message`
	 */
	abstract public function read();

	/**
	 * Writes data to the socket.
	 *
	 * @param mixed $data
	 * @return boolean `true` if data has been successfully written, `false` otherwise.
	 */
	abstract public function write($data);

	/**
	 * Sets the timeout on the socket *connection*.
	 *
	 * @param integer $time Seconds after the connection times out.
	 * @return Boolean `true` if timeout has been set, `false` otherwise.
	 */
	abstract public function timeout($time);

	/**
	 * Sets the encoding of the socket connection.
	 *
	 * @param string $charset The character set to use.
	 * @return boolean `true` if encoding has been set, `false` otherwise.
	 */
	abstract public function encoding($charset);

	/**
	 * Sets the options to be used in subsequent requests.
	 *
	 * @param array $flags If $values is an array, $flags will be used as the
	 *        keys to an associative array of curl options. If $values is not set,
	 *        then $flags will be used as the associative array.
	 * @param array $value If set, this array becomes the values for the
	 *        associative array of curl options.
	 * @return void
	 */
	public function set($flags, $value = null) {}

	/**
	 * Aggregates read and write methods into a coherent request response
	 *
	 * @param \lithium\net\Message $message
	 * @param array $options
	 *              - '`response`': a fully-namespaced string for the response object
	 * @return object a response object based on `\lithium\net\Message`
	 */
	public function send($message = null, array $options = []) {
		$defaults = ['response' => $this->_classes['response']];
		$options += $defaults;

		if ($this->write($message)) {
			$config = ['message' => $this->read()] + $this->_config;
			return Libraries::instance(null, $options['response'], $config, $this->_classes);
		}
	}

	/**
	 * Destructor.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->close();
	}

	/**
	 * Returns the resource.
	 *
	 * @return resource
	 */
	public function resource() {
		return $this->_resource;
	}
}

?>