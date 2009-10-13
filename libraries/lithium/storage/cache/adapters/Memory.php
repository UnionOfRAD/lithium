<?php

namespace lithium\storage\cache\adapters;

class Memory extends \lithium\core\Adaptable {

	protected $_cache = array();

	/**
	 * Reads data from $key
	 *
	 * @param string $key
	 * @param mixed  $conditions
	 * @return mixed
	 */
	public function read($key, $conditions = null) {
		$cache =& $this->_cache;

		return function($self, $params, $chain) use (&$cache) {
			extract($params);
			return isset($cache[$key]) ? $cache[$key] : null;
		};
	}

	/**
	 * Writes $data to $key
	 *
	 * @param string $key
	 * @param mixed  $data
	 * @param mixed  $conditions
	 * @return boolean
	 */
	public function write($key, $data, $conditions = null) {
		$cache =& $this->_cache;

		return function($self, $params, $chain) use (&$cache) {
			extract($params);
			return (bool)($cache[$key] = $data);
		};
	}

	/**
	 * Delete a key
	 *
	 * @param string $key
	 * @param mixed  $conditions
	 * @return boolean
	 */
	public function delete($key, $conditions = null) {
		$cache =& $this->_cache;

		return function($self, $params, $chain) use (&$cache) {
			extract($params);
			if (isset($cache[$key])) {
				unset($cache[$key]);
				return true;
			} else {
				return false;
			}
		};
	}

	/**
	 * Clear all keys
	 *
	 * @return boolean
	 */
	public function clear() {
		unset($this->_cache);
		return true;
	}

	/**
	 * GC is not enabled for this adapter
	 *
	 * @return boolean
	 */
	public function clean() {
		return false;
	}

}
?>