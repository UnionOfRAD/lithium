<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\session\adapter;

use lithium\util\Text;

/**
 * Simple memory session storage engine. Used for testing.
 */
class Memory {

	/**
	 * Holds the array that corresponds to session keys & values.
	 *
	 * @var array "Session" data.
	 */
	protected $_session = [];

	/**
	 * Obtain the session key.
	 *
	 * For this adapter, it is a UUID.
	 *
	 * @return string UUID.
	 */
	public static function key() {
		return Text::uuid();
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
	 * @return \Closure Function returning boolean `true` if the key exists, `false` otherwise.
	 */
	public function check($key, array $options = []) {
		return function($params) {
			return isset($this->_session[$params['key']]);
		};
	}

	/**
	 * Read a value from the session.
	 *
	 * @param null|string $key Key of the entry to be read. If no key is passed, all
	 *        current session data is returned.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return \Closure Function returning data in the session if successful, `false` otherwise.
	 */
	public function read($key = null, array $options = []) {
		return function($params) {
			if (!$params['key']) {
				return $this->_session;
			}
			return isset($this->_session[$params['key']]) ? $this->_session[$params['key']] : null;
		};
	}

	/**
	 * Write a value to the session.
	 *
	 * @param string $key Key of the item to be stored.
	 * @param mixed $value The value to be stored.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return \Closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
	public function write($key, $value, array $options = []) {
		return function($params) {
			return (boolean) ($this->_session[$params['key']] = $params['value']);
		};
	}

	/**
	 * Delete value from the session
	 *
	 * @param string $key The key to be deleted
	 * @param array $options Options array. Not used for this adapter method.
	 * @return \Closure Function returning boolean `true` on successful delete, `false` otherwise
	 */
	public function delete($key, array $options = []) {
		return function($params) {
			unset($this->_session[$params['key']]);
			return !isset($this->_session[$params['key']]);
		};
	}

	/**
	 * Clears all keys from the session.
	 *
	 * @param array $options Options array. Not used for this adapter method.
	 * @return \Closure Function that clears the session
	 */
	public function clear(array $options = []) {
		return function($params) {
			$this->_session = [];
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