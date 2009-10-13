<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapters;

/**
 * Simple memory session storage engine. Used for testing.
 */
class Memory extends \lithium\core\Object {

	public $_session = array();

	public function key() {
		return $_SERVER['UNIQUE_ID'];
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
			return (bool)($session[$key] = $value);
		};
	}

	public function delete($key, $options = array()) {
		unset($this->_session[$key]);
	}
}

?>