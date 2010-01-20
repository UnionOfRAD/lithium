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
		'name' => 'li3', 'expire' => '+2 days', 'path' => '/',
		'domain' => '', 'secure' => false, 'httponly' => false
	);

	/**
	 * Class constructor.
	 *
	 * Takes care of setting appropriate configurations for
	 * this object.
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array()) {
		parent::__construct((array) $config + $this->_defaults);
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
	 * Obtain the status of the session
	 *
	 * @return boolean True if $_COOKIE has been initialized, false otherwise
	 */
	public function isStarted() {
		return (isset($_COOKIE));
	}

	/**
	 * Read value from the session
	 *
	 * @param string $key Key of the entry to be read
	 * @param array $options Options array
	 * @return mixed Data in the session if successful, false otherwise
	 */
	public function read($key, $options = array()) {
		$config = $options + $this->_config;

		return function($self, $params, $chain) use (&$config) {
			extract($params);
			if (!isset($_COOKIE[$key])) {
				return null;
			}
			return $_COOKIE[$key];
		};
	}

	/**
	 * Write value to the session
	 *
	 * @param string $key Key of the item to be stored
	 * @param mixed $value The value to be stored
	 * @param array $options Options array
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($key, $value = null, $options = array()) {
		if (!isset($options['expire']) && empty($this->_config['expire'])
				&& $key != $this->_config['name']) {
			return null;
		}
		$config = $options + $this->_config;
		$expires = (isset($options['expire'])) ? $options['expire'] : $config['expire'];

		return function($self, $params, $chain) use (&$config, &$expires) {
			extract($params);
			$key = is_array($key) ? Set::flatten($key) : array($key => $value);

			foreach ($key as $name => $val) {
				$name = explode('.', $name);
				$name = $config['name'] ? array_merge(array($config['name']), $name) : $name;

				if (count($name) == 1) {
					$name = current($name);
				} else {
					$name = (array_shift($name) . '[' . join('][', $name) . ']');
				}
				setcookie($name, $val, strtotime($expires), $config['path'],
					$config['domain'], $config['secure'], $config['httponly']
				);
			}
		};
	}

	/**
	 * Delete value from the cookie
	 *
	 * @param string $key The key to be deleted
	 * @param array $options Options array
	 * @return boolean True on successful delete, false otherwise
	 */
	public function delete($key, $options = array()) {
		$config = $options + $this->_config;

		return function($self, $params, $chain) use (&$config) {
			extract($params);
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