<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapter;

use \lithium\util\String;

/**
 * Simple memory session storage engine. Used for testing.
 */
class Memory extends \lithium\core\Object {

	public $_session = array();

	public function key() {
		$context = function ($value) use (&$config) {
			return (isset($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
		};
		return String::uuid($context);
	}

	public function isStarted() {
		return true;
	}

	public function check($key, $options = array()) {
		return isset($this->_session[$key]);
	}

	public function read($key, $options = array()) {
		$session = $this->_session;

		return function($self, $params, $chain) use ($session) {
			extract($params);
			return isset($session[$key]) ? $session[$key] : null;
		};
	}

	public function write($key, $value, $options = array()) {
		$session =& $this->_session;

		return function($self, $params, $chain) use (&$session) {
			extract($params);
			return (boolean) ($session[$key] = $value);
		};
	}

	public function delete($key, $options = array()) {
		$session =& $this->_session;

		return function($self, $params, $chain) use (&$session) {
			extract($params);
			unset($session[$key]);
		};
	}

	/**
	 * This adapter is always enabled, as it has no external dependencies.
	 *
	 * @return boolean True
	 */
	public static function enabled() {
		return true;
	}
}

?>