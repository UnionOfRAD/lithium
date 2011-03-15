<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapter;

use lithium\util\Set;
use lithium\util\Inflector;

/**
 * A minimal adapter to interface with HTTP cookies.
 *
 * This adapter provides basic support for `write`, `read` and `delete`
 * cookie handling, as well as allowing these three methods to be filtered as
 * per the Lithium filtering system.
 *
 */
class Cookie extends \lithium\core\Object {

	/**
	 * Default settings for this session adapter.
	 *
	 * @var array Keys are in direct correspondence with the parameters in the PHP-native
	 *      `setcookie()` method. The only difference is that the `expire` value is a
	 *		strtotime-compatible string instead of an epochal timestamp.
	 */
	protected $_defaults = array(
		'expire' => '+2 days', 'path' => '/', 'name' => null,
		'domain' => '', 'secure' => false, 'httponly' => false
	);

	/**
	 * Class constructor.
	 *
	 * Takes care of setting appropriate configurations for this object.
	 *
	 * @param array $config Optional configuration parameters.
	 * @return void
	 */
	public function __construct(array $config = array()) {
		parent::__construct($config + $this->_defaults);
	}

	/**
	 * Initialization of the cookie adapter.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		if (!$this->_config['name']) {
			$this->_config['name'] = Inflector::slug(basename(LITHIUM_APP_PATH)) . 'cookie';
		}
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
	 * return boolean True
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
	 * @return boolean True if the key exists, false otherwise.
	 */
	public function check($key) {
		$config = $this->_config;

		return function($self, $params) use (&$config) {
			return (isset($_COOKIE[$config['name']][$params['key']]));
		};
	}

	/**
	 * Read a value from the cookie.
	 *
	 * @param null|string $key Key of the entry to be read. If $key is null, returns
	 *        all cookie key/value pairs that have been set.
	 * @param array $options Options array. Not used in this adapter.
	 * @return mixed Data in the session if successful, null otherwise.
	 */
	public function read($key = null, array $options = array()) {
		$config = $this->_config;

		return function($self, $params) use (&$config) {
			$key = $params['key'];
			if (!$key) {
				if (isset($_COOKIE[$config['name']])) {
					return $_COOKIE[$config['name']];
				}
				return array();
			}
			if (strpos($key, '.') !== false) {
				$key = explode('.', $key);
				$result = (isset($_COOKIE[$config['name']])) ? $_COOKIE[$config['name']] : array();

				foreach ($key as $k) {
					if (isset($result[$k])) {
						$result = $result[$k];
					}
				}
				return ($result !== array()) ? $result : null;
			}
			if (isset($_COOKIE[$config['name']][$key])) {
				return $_COOKIE[$config['name']][$key];
			}
		};
	}

	/**
	 * Write a value to the cookie store.
	 *
	 * @param string $key Key of the item to be stored.
	 * @param mixed $value The value to be stored.
	 * @param array $options Options array.
	 * @return boolean True on successful write, false otherwise.
	 */
	public function write($key, $value = null, array $options = array()) {
		$expire = (!isset($options['expire']) && empty($this->_config['expire']));
		$config = $this->_config;
		$cookieClass = __CLASS__;

		if ($expire && $key != $config['name']) {
			return null;
		}
		$expires = (isset($options['expire'])) ? $options['expire'] : $config['expire'];

		return function($self, $params) use (&$config, &$expires, $cookieClass) {
			$key = $params['key'];
			$value = $params['value'];
			$key = array($key => $value);
			if (is_array($value)) {
				$key = Set::flatten($key);
			}

			foreach ($key as $name => $val) {
				$name = $cookieClass::keyFormat($name, $config);
				$result = setcookie($name, $val, strtotime($expires), $config['path'],
					$config['domain'], $config['secure'], $config['httponly']
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
	 * @return boolean True on successful delete, false otherwise.
	 */
	public function delete($key, array $options = array()) {
		$config = $this->_config;
		$cookieClass = __CLASS__;

		return function($self, $params) use (&$config, $cookieClass) {
			$key = $params['key'];
			$key = is_array($key) ? Set::flatten($key) : array($key);

			foreach ($key as $name) {
				$name = $cookieClass::keyFormat($name, $config);

				$result = setcookie($name, "", time() - 1, $config['path'],
					$config['domain'], $config['secure'], $config['httponly']
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
	public function clear(array $options = array()) {
		$options += array('destroySession' => true);
		$config = $this->_config;

		return function($self, $params) use (&$config, $options) {
			$cookies = array_keys($_COOKIE);

			foreach ($cookies as $cookie) {
				$result = setcookie($cookie, "", time()-1);

				if (!$result) {
					throw new RuntimeException("There was an error clearing {$cookie} cookie.");
				}
			}
			$_COOKIE = array();

			if ($options['destroySession'] && session_id()) {
				session_destroy();
			}

			return true;
		};
	}

	/**
	 * Formats the given `$name` argument for use in the cookie adapter.
	 *
	 * @param string $name The key to be formatted, e.g. `foo.bar.baz`.
	 * @return string The formatted key.
	 */
	public static function keyFormat($name, $config) {
		$name = explode('.', $name);
		$name = $config['name'] ? array_merge(array($config['name']), $name) : $name;

		if (count($name) == 1) {
			$name = current($name);
		} else {
			$name = (array_shift($name) . '[' . join('][', $name) . ']');
		}
		return $name;
	}
}

?>