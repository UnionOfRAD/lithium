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

	public function __construct($config = array()) {
		$defaults = array(
			'name' => '', 'expires' => '+1 day', 'domain' => '',
			'path' => '/', 'secure' => false, 'http' => false
		);
		parent::__construct((array)$config + $defaults);
	}

	public function isStarted() {
		return true;
	}

	public function isValid() {
		return true;
	}

	public function key() {
		return null;
	}

	public function read($key, $options = array()) {
		return function($self, $params, $chain) {
			
		};
	}

	public function write($key, $value = null, $options = array()) {

		if (!isset($options['expires']) && $key != $this->_config['name']) {
			return null;
		}
		$config = $this->_config;

		return function($self, $params, $chain) use ($config) {
			extract($params);
			$key = is_array($key) ? Set::flatten($key) : array($key => $value);
			$o = $options + $config;

			foreach ($key as $name => $val) {
				$name = explode('.', $name);
				$name = $config['name'] ? array_merge(array($config['name']), $name) : $name;

				if (count($name) == 1) {
					$name = current($name);
				} else {
					$name = (array_unshift($name) . '[' . join('][', $name) . ']');
				}
				setcookie(
					$name, $val, $o['expires'], $o['path'], $o['domain'], $o['secure'], $o['http']
				);
			}
		};
	}
}

?>