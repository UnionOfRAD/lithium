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

	/**
	 * Holds the array that corresponds to session keys & values.
	 *
	 * @var array "Session" data.
	 */
	public $_session = array();

	/**
	 * Obtain the session key.
	 *
	 * For this adapter, it is a UUID based on the SERVER_ADDR variable.
	 *
	 * @return string UUID.
	 */
	public static function key() {
		$context = function ($value) use (&$config) {
			return (isset($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
		};
		return String::uuid($context);
	}

	/**
	 * The memory adapter session is always "on".
	 *
	 * @return boolean True.
	 */
	public function isStarted() {
		return true;
	}

	/**
	 * Checks if a value has been set in the session.
	 *
	 * @param string $key Key of the entry to be checked.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return boolean True if the key exists, false otherwise.
	 */
	public function check($key, array $options = array()) {
		$session =& $this->_session;
		return function($self, $params, $chain) use (&$session) {
			return isset($session[$params['key']]);
		};
	}

	/**
	 * Read a value from the session.
	 *
	 * @param null|string $key Key of the entry to be read. If no key is passed, all
	 *        current session data is returned.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return mixed Data in the session if successful, false otherwise.
	 */
	public function read($key = null, array $options = array()) {
		$session = $this->_session;

		return function($self, $params, $chain) use ($session) {
			extract($params);

			if (!$key) {
				return $session;
			}
			return isset($session[$key]) ? $session[$key] : null;
		};
	}

	/**
	 * Write a value to the session.
	 *
	 * @param string $key Key of the item to be stored.
	 * @param mixed $value The value to be stored.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($key, $value, array $options = array()) {
		$session =& $this->_session;

		return function($self, $params, $chain) use (&$session) {
			extract($params);
			return (boolean) ($session[$key] = $value);
		};
	}

	/**
	 * Delete value from the session
	 *
	 * @param string $key The key to be deleted
	 * @param array $options Options array. Not used for this adapter method.
	 * @return boolean True on successful delete, false otherwise
	 */
	public function delete($key, array $options = array()) {
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