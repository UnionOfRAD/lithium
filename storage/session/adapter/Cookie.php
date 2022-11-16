<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\session\adapter;

use lithium\core\AutoConfigurable;
use RuntimeException;
use lithium\util\Set;
use lithium\core\Libraries;

/**
 * A minimal adapter to interface with HTTP cookies.
 *
 * This adapter provides basic support for `write`, `read` and `delete`
 * cookie handling, as well as allowing these three methods to be filtered as
 * per the Lithium filtering system.
 *
 */
class Cookie {

	use AutoConfigurable;

	/**
	 * Default settings for this session adapter.
	 *
	 * @link http://php.net/setcookie
	 * @var array Keys are in direct correspondence with the parameters in the PHP-native
	 *      `setcookie()` method. The only difference is that the `expire` value is a
	 *      strtotime-compatible string instead of an epochal timestamp.
	 */
	protected $_defaults = [
		'expire' => '+2 days',
		'path' => '/',
		'domain' => '',
		'secure' => false,
		'httponly' => false,
		'name' => null
	];

	/**
	 * Constructor.
	 *
	 * Takes care of setting appropriate configurations for this object.
	 *
	 * @param array $config Configuration for this adapter. These settings are queryable
	 *        through `Session::config('name')`. The available options are as follows:
	 *        - `'expire'` _string_: Defaults to `'+2 days'`.
	 *        - `'path'` _string_: Defaults to `'/'` and does not further restrict path access.
	 *        - `'domain'` _string_: Defaults to `''` and does not further restrict domain access.
	 *        - `'secure'` _boolean_: Defaults to `false`.
	 *        - `'httponly'` _boolean_: Defaults to `false`.
	 *        - `'name'` _string_: Defaults to the basename of the applications path.
	 * @return void
	 */
	public function __construct(array $config = []) {
		if (empty($config['name'])) {
			$config['name'] = basename(Libraries::get(true, 'path')) . 'cookie';
		}
		$this->_autoConfig($config + $this->_defaults, []);
		$this->_autoInit($config);
	}

	/**
	 * Obtain the top-level cookie key.
	 *
	 * @return string The configured cookie 'name' parameter
	 */
	public function key() {
		return $this->_config['name'];
	}

	/**
	 * Determines if cookies are enabled.
	 *
	 * @return boolean True
	 * @todo Implement
	 */
	public function isEnabled() {
		return true;
	}

	/**
	 * Obtain the status of the cookie storage.
	 *
	 * @return boolean True if $_COOKIE has been initialized, false otherwise.
	 */
	public function isStarted() {
		return (isset($_COOKIE));
	}

	/**
	 * Checks if a value has been set in the cookie.
	 *
	 * @param string $key Key of the entry to be checked.
	 * @return \Closure Function returning boolean `true` if the key exists, `false` otherwise.
	 */
	public function check($key) {
		return function($params) {
			return (isset($_COOKIE[$this->_config['name']][$params['key']]));
		};
	}

	/**
	 * Read a value from the cookie.
	 *
	 * @param null|string $key Key of the entry to be read. If $key is null, returns
	 *        all cookie key/value pairs that have been set.
	 * @param array $options Options array. Not used in this adapter.
	 * @return \Closure Function returning data in the session if successful, `null` otherwise.
	 */
	public function read($key = null, array $options = []) {
		return function($params) {
			$key = $params['key'];
			if (!$key) {
				if (isset($_COOKIE[$this->_config['name']])) {
					return $_COOKIE[$this->_config['name']];
				}
				return [];
			}
			if (strpos($key, '.') !== false) {
				$key = explode('.', $key);

				if (isset($_COOKIE[$this->_config['name']])) {
					$result = $_COOKIE[$this->_config['name']];
				} else {
					$result = [];
				}

				foreach ($key as $k) {
					if (!isset($result[$k])) {
						return null;
					}
					$result = $result[$k];
				}
				return $result;
			}
			if (isset($_COOKIE[$this->_config['name']][$key])) {
				return $_COOKIE[$this->_config['name']][$key];
			}
		};
	}

	/**
	 * Write a value to the cookie store.
	 *
	 * @param string $key Key of the item to be stored.
	 * @param mixed $value The value to be stored.
	 * @param array $options Options array.
	 * @return \Closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
	public function write($key, $value = null, array $options = []) {
		$expire = (!isset($options['expire']) && empty($this->_config['expire']));
		$cookieClass = __CLASS__;

		if ($expire && $key !== $this->_config['name']) {
			return null;
		}
		$expires = (isset($options['expire'])) ? $options['expire'] : $this->_config['expire'];

		return function($params) use (&$expires, $cookieClass) {
			$key = $params['key'];
			$value = $params['value'];
			$key = [$key => $value];
			if (is_array($value)) {
				$key = Set::flatten($key);
			}

			foreach ($key as $name => $val) {
				$result = setcookie(
					$cookieClass::keyFormat($name, $this->_config),
					$val,
					strtotime($expires),
					$this->_config['path'],
					$this->_config['domain'],
					$this->_config['secure'],
					$this->_config['httponly']
				);
				if (!$result) {
					throw new RuntimeException("There was an error setting {$name} cookie.");
				}
			}
			return true;
		};
	}

	/**
	 * Delete a value from the cookie store.
	 *
	 * @param string $key The key to be deleted from the cookie store.
	 * @param array $options Options array.
	 * @return \Closure Function returning boolean `true` on successful delete, `false` otherwise.
	 */
	public function delete($key, array $options = []) {
		$cookieClass = get_called_class();

		return function($params) use ($cookieClass) {
			$key = $params['key'];
			$path = '/' . str_replace('.', '/', $this->_config['name'] . '.' . $key) . '/.';
			$cookies = current(Set::extract($_COOKIE, $path));
			if (is_array($cookies)) {
				$cookies = array_keys(Set::flatten($cookies));
				foreach ($cookies as &$name) {
					$name = $key . '.' . $name;
				}
			} else {
				$cookies = [$key];
			}
			foreach ($cookies as &$name) {
				$result = setcookie(
					$cookieClass::keyFormat($name, $this->_config),
					"",
					1,
					$this->_config['path'],
					$this->_config['domain'],
					$this->_config['secure'],
					$this->_config['httponly']
				);
				if (!$result) {
					throw new RuntimeException("There was an error deleting {$name} cookie.");
				}
			}
			return true;
		};
	}

	/**
	 * Clears all cookies.
	 *
	 * @param array $options Options array. Not used fro this adapter method.
	 * @return boolean True on successful clear, false otherwise.
	 */
	public function clear(array $options = []) {
		$options += ['destroySession' => true];
		$cookieClass = get_called_class();

		return function($params) use ($options, $cookieClass) {
			if ($options['destroySession'] && session_id()) {
				session_destroy();
			}
			if (!isset($_COOKIE[$this->_config['name']])) {
				return true;
			}
			$cookies = array_keys(Set::flatten($_COOKIE[$this->_config['name']]));
			foreach ($cookies as $name) {
				$result = setcookie(
					$cookieClass::keyFormat($name, $this->_config),
					"",
					1,
					$this->_config['path'],
					$this->_config['domain'],
					$this->_config['secure'],
					$this->_config['httponly']
				);
				if (!$result) {
					throw new RuntimeException("There was an error clearing {$name} cookie.");
				}
			}
			unset($_COOKIE[$this->_config['name']]);
			return true;
		};
	}

	/**
	 * Formats the given `$name` argument for use in the cookie adapter.
	 *
	 * @param string $name The key to be formatted, e.g. `foo.bar.baz`.
	 * @param array $config
	 * @return string The formatted key.
	 */
	public static function keyFormat($name, $config) {
		return $config['name'] . '[' . str_replace('.', '][', $name) . ']';
	}
}

?>