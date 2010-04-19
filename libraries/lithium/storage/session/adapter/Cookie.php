<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapter;

use \lithium\util\Set;

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
		$this->_config['name'] = $this->_config['name'] ?: basename(LITHIUM_APP_PATH) . 'cookie';
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
		return function($self, $params, $chain) {
			return (isset($_COOKIE[$params['key']]));
		};
	}

	/**
	 * Read a value from the cookie.
	 *
	 * @param null|string $key Key of the entry to be read. If $key is null, returns
	 *        all cookie key/value pairs that have been set.
	 * @return mixed Data in the session if successful, null otherwise.
	 */
	public function read($key = null) {
		$config = $this->_config;

		return function($self, $params, $chain) use (&$config) {
			$key = $params['key'];
			if (!$key) {
				return $_COOKIE;
			}
			if (strpos($key, '.') !== false) {
				$key = explode('.', $key);
				$result = $_COOKIE[$config['name']];

				foreach ($key as $k) {
					$result = $result[$k];
				}
				return ($result !== array()) ? $result : null;
			}
			return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : null;
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
		$expire = !isset($options['expire']) && empty($this->_config['expire']);

		if ($expire && $key != $this->_config['name']) {
			return null;
		}
		$config = $options + $this->_config;
		$expires = (isset($options['expire'])) ? $options['expire'] : $config['expire'];

		return function($self, $params, $chain) use (&$config, &$expires) {
			$key = $params['key'];
			$value = $params['value'];
			$key = is_array($key) ? Set::flatten($key) : array($key => $value);

			foreach ($key as $name => $val) {
				$name = explode('.', $name);
				$name = $config['name'] ? array_merge(array($config['name']), $name) : $name;

				if (count($name) == 1) {
					$name = current($name);
				} else {
					$name = (array_shift($name) . '[' . join('][', $name) . ']');
				}
				if (is_array($val)) {
					foreach ($val as $key => $v) {
						setcookie($name . "[$key]", $v, strtotime($expires), $config['path'],
							$config['domain'], $config['secure'], $config['httponly']
						);
					}
					return true;
				}
				setcookie($name, $val, strtotime($expires), $config['path'],
					$config['domain'], $config['secure'], $config['httponly']
				);
			}
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
		$config = $options + $this->_config;

		return function($self, $params, $chain) use (&$config) {
			$key = $params['key'];
			$key = is_array($key) ? Set::flatten($key) : array($key);

			foreach ($key as $name) {
				$name = explode('.', $name);
				$name = $config['name'] ? array_merge(array($config['name']), $name) : $name;

				if (count($name) == 1) {
					$name = current($name);
				} else {
					$name = (array_shift($name) . '[' . join('][', $name) . ']');
				}
				setcookie($name, "", time() - 1, $config['path'],
					$config['domain'], $config['secure'], $config['httponly']
				);
			}
		};
	}
}

?>