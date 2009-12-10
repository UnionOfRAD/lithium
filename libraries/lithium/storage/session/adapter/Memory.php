<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
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
		return String::uuid(function($value) { return $_SERVER[$value]; });
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
			return (boolean)($session[$key] = $value);
		};
	}

	public function delete($key, $options = array()) {
		unset($this->_session[$key]);
	}

	/**
	 * This adapter is always enabled, as it has no external dependencies.
	 *
	 * @return boolean True
	 */
	public function enabled() {
		return true;
	}
}

?>