<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\session\adapter;

use lithium\util\Set;
use RuntimeException;
use lithium\core\ConfigException;
use lithium\core\AutoConfigurable;
use lithium\core\Libraries;

/**
 * A minimal adapter to interface with native PHP sessions.
 *
 * This adapter provides basic support for `write`, `read` and `delete`
 * session handling, as well as allowing these three methods to be filtered as
 * per the Lithium filtering system.
 */
class Php {

	use AutoConfigurable;

	/**
	 * Default ini settings for this session adapter. Will disabl cookie lifetime,
	 * set cookies to HTTP only and the cache_limiter to `'nocache'`.
	 *
	 * @link http://php.net/session.configuration.php
	 * @link http://php.net/session.configuration.php#ini.session.cookie-lifetime
	 * @link http://php.net/session.configuration.php#ini.session.cookie-httponly
	 * @link http://php.net/session.configuration.php#ini.session.cache-limiter
	 * @var array Configuration options matching the pattern `'session.*'` are session
	 *      ini settings. Please consult the PHP documentation for further information.
	 */
	protected $_defaults = [
		'session.cookie_lifetime' => '0',
		'session.cookie_httponly' => true,
		'session.cache_limiter' => 'nocache'
	];

	/**
	 * Constructor. Takes care of setting appropriate configurations for this object. Also sets
	 * session ini settings.
	 *
	 * @see lithium\storage\session\adapter\Php::$_defaults
	 * @param array $config Configuration options matching the pattern `'session.*'` are interpreted
	 *              as session ini settings. Please consult the PHP documentation for further
	 *              information.
	 *              A few ini settings are set by default here and will overwrite those from
	 *              your php.ini. To disable sending a cache limiter set `'session.cache_limiter'`
	 *              to `false`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		if (empty($config['session.name'])) {
			$config['session.name'] = basename(Libraries::get(true, 'path'));
		}
		$this->_autoConfig($config + $this->_defaults, []);
		$this->_autoInit($config);
	}

	/**
	 * Initialization of the session.
	 *
	 * @todo Split up into an _initialize() and a _start().
	 */
	protected function _init() {
		if ($this->isStarted()) {
			return true;
		}
		$config = $this->_config;
		unset($config['adapter'], $config['strategies'], $config['filters'], $config['init']);

		foreach ($config as $key => $value) {
			if (strpos($key, 'session.') === false) {
				continue;
			}
			if (ini_set($key, $value) === false) {
				throw new ConfigException('Could not initialize the session.');
			}
		}
	}

	/**
	 * Starts the session.
	 *
	 * @return boolean `true` if session successfully started
	 *         (or has already been started), `false` otherwise.
	 */
	protected function _start() {
		if ($this->isStarted()) {
			return true;
		}
		session_cache_limiter();
		return session_start();
	}

	/**
	 * Obtain the status of the session.
	 *
	 * @return boolean True if a session is currently started, False otherwise. If PHP 5.4
	 *                 then we know, if PHP 5.3 then we cannot tell for sure if a session
	 *                 has been closed.
	 */
	public function isStarted() {
		if (function_exists('session_status')) {
			return session_status() === PHP_SESSION_ACTIVE;
		}
		return isset($_SESSION) && session_id();
	}

	/**
	 * Sets or obtains the session ID.
	 *
	 * @param string $key Optional. If specified, sets the session ID to the value of `$key`.
	 * @return mixed Session ID, or `null` if the session has not been started.
	 */
	public function key($key = null) {
		if ($key !== null) {
			return session_id($key);
		}
		return session_id() ?: null;
	}

	/**
	 * Checks if a value has been set in the session.
	 *
	 * @param string $key Key of the entry to be checked.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return \Closure Function returning boolean `true` if the key exists, `false` otherwise.
	 */
	public function check($key, array $options = []) {
		if (!$this->isStarted() && !$this->_start()) {
			throw new RuntimeException('Could not start session.');
		}
		return function($params) {
			return Set::check($_SESSION, $params['key']);
		};
	}

	/**
	 * Read a value from the session.
	 *
	 * @param null|string $key Key of the entry to be read. If no key is passed, all
	 *        current session data is returned.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return \Closure Function returning data in the session if successful, `false` otherwise.
	 */
	public function read($key = null, array $options = []) {
		if (!$this->isStarted() && !$this->_start()) {
			throw new RuntimeException('Could not start session.');
		}
		return function($params) {
			$key = $params['key'];

			if (!$key) {
				return $_SESSION;
			}
			if (strpos($key, '.') === false) {
				return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
			}
			$filter  = function($keys, $data) use (&$filter) {
				$key = array_shift($keys);
				if (isset($data[$key])) {
					return (empty($keys)) ? $data[$key] : $filter($keys, $data[$key]);
				}
			};
			return $filter(explode('.', $key), $_SESSION);
		};
	}

	/**
	 * Write a value to the session.
	 *
	 * @param string $key Key of the item to be stored.
	 * @param mixed $value The value to be stored.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return \Closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
	public function write($key, $value, array $options = []) {
		if (!$this->isStarted() && !$this->_start()) {
			throw new RuntimeException('Could not start session.');
		}
		return function($params) {
			return $this->overwrite(
				$_SESSION, Set::insert($_SESSION, $params['key'], $params['value'])
			);
		};
	}

	/**
	 * Delete value from the session
	 *
	 * @param string $key The key to be deleted.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return \Closure Function returning boolean `true` if the key no longer
	 *         exists in the session, `false` otherwise
	 */
	public function delete($key, array $options = []) {
		if (!$this->isStarted() && !$this->_start()) {
			throw new RuntimeException('Could not start session.');
		}
		return function($params) {
			$key = $params['key'];
			$this->overwrite($_SESSION, Set::remove($_SESSION, $key));
			return !Set::check($_SESSION, $key);
		};
	}

	/**
	 * Clears all keys from the session.
	 *
	 * @param array $options Options array. Not used for this adapter method.
	 * @return \Closure Function returning boolean `true` on successful clear, `false` otherwise.
	 */
	public function clear(array $options = []) {
		if (!$this->isStarted() && !$this->_start()) {
			throw new RuntimeException('Could not start session.');
		}
		return function($params) {
			return session_destroy();
		};
	}

	/**
	 * Determines if PHP sessions are enabled.
	 *
	 * @return boolean Returns `true` if enabled (PHP session functionality
	 *         can be disabled completely), `false` otherwise.
	 */
	public static function enabled() {
		if (function_exists('session_status')) {
			return session_status() !== PHP_SESSION_DISABLED;
		}
		return in_array('session', get_loaded_extensions());
	}

	/**
	 * Overwrites session keys and values.
	 *
	 * @param array $old Reference to the array that needs to be
	 *              overwritten. Will usually be `$_SESSION`.
	 * @param array $new The data that should overwrite the keys/values in `$old`.
	 * @return boolean Always `true`.
	 */
	public function overwrite(&$old, $new) {
		if (!empty($old)) {
			foreach ($old as $key => $value) {
				if (!isset($new[$key])) {
					unset($old[$key]);
				}
			}
		}
		foreach ($new as $key => $value) {
			$old[$key] = $value;
		}
		return true;
	}
}

?>