<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\analysis\logger\adapter;

use lithium\core\AutoConfigurable;

trait HackilyExposeConfig {

	public function hackilyExposeConfig() {
		return $this->_config;
	}
}

/**
 * The Syslog adapter facilitates logging messages to a `syslogd` backend. See the constructor for
 * information on configuring this adapter.
 *
 * @see lithium\analysis\logger\adapter\Syslog::__construct()
 */
class Syslog {

	use AutoConfigurable;
	use HackilyExposeConfig;

	/**
	 * Flag indicating whether or not the connection to `syslogd` has been opened yet.
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	/**
	 * Array that maps `Logger` message priority names to `syslog`-compatible priority constants.
	 *
	 * @var array
	 */
	protected $_priorities = [
		'emergency' => LOG_EMERG,
		'alert'     => LOG_ALERT,
		'critical'  => LOG_CRIT,
		'error'     => LOG_ERR,
		'warning'   => LOG_WARNING,
		'notice'    => LOG_NOTICE,
		'info'      => LOG_INFO,
		'debug'     => LOG_DEBUG
	];

	/**
	 * Constructor. Configures the `Syslog` adapter instance with the default settings. For
	 * more information on these settings, see the documentation for tthe `openlog()` function.
	 *
	 * @link http://php.net/openlog
	 * @param array $config Available configuration settings for this adapter:
	 *        - `'identity'` _string_: The identity string to be attached to each message in
	 *          the system log. This is usually a string that meaningfully identifies your
	 *          application. Defaults to `false`.
	 *        - `'options'` _integer_: The flags to use when opening the log. Defaults to
	 *          `LOG_ODELAY`.
	 *        - `'facility'` _integer_: A flag specifying the program to use to log the
	 *          messages. See the `openlog()` documentation for more information. Defaults to
	 *          `LOG_USER`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['identity' => false, 'options'  => LOG_ODELAY, 'facility' => LOG_USER];
		$this->_autoConfig($config + $defaults, []);
		$this->_autoInit($config);
	}

	/**
	 * Appends `$message` to the system log.
	 *
	 * @param string $priority The message priority string. Maps to a `syslogd` priority constant.
	 * @param string $message The message to write.
	 * @return \Closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
	public function write($priority, $message) {
		$config = $this->_config;

		if (!$this->_isConnected) {
			closelog();
			openlog($config['identity'], $config['options'], $config['facility']);
			$this->_isConnected = true;
		}

		return function($params) {
			$priority = $this->_priorities[$params['priority']];
			return syslog($priority, $params['message']);
		};
	}
}

?>