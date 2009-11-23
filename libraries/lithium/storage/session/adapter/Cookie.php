<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapter;

use \lithium\util\Set;

class Cookie extends \lithium\core\Object {

	/**
	 * Default settings for this session adapter
	 *
	 * @var array Keys are in direct correspondence with the parameters in the PHP-native `setcookie()`
	 *      method. The only difference is that the `expire` value is a strtotime-compatible string
	 *      instead of an epochal timestamp.
	 */
	protected $_defaults = array(
		'name' => 'li3', 'expire' => '+2 days', 'path' => '/',
		'domain' => '', 'secure' => false, 'httponly' => false
	);

	public function __construct($config = array()) {
		parent::__construct((array) $config + $this->_defaults);
	}

	public function isStarted() {
		return true;
	}

	public function isValid() {
		return true;
	}

	public function key() {
		return $this->_config['name'];
	}

	public function isEnabled() {
		return true;
	}

	public function read($key, $options = array()) {
		$config = $options + $this->_config;

		return function($self, $params, $chain) use (&$config) {
			extract($params);
			return (isset($_COOKIE[$key])) ?: null;
		};
	}

	public function write($key, $value = null, $options = array()) {
		//if (!isset($options['expires']) && $key != $this->_config['name']) {
			//return null;
		//}
		$config = $options + $this->_config;

		return function($self, $params, $chain) use (&$config) {
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
				setcookie($name, $val, strtotime($config['expire']), $config['path'],
					$config['domain'], $config['secure'], $config['httponly']
				);
			}
		};
	}
}

?>