<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

use Closure;
use lithium\storage\Cache;

/**
 * A minimal in-memory cache.
 *
 * This cache adapter is best suited for generic memoization of data, and should not be used
 * for for anything that must persist longer than the current request cycle.
 *
 * This adapter has no external dependencies. Operations in read/write/delete are atomic
 * for single-keys only. Operations increment/decrement are atomic and clearing the cache
 * is supported.
 *
 * Real persistence of cached items is *not* provided. Mulit-key operations and serialization
 * are not natively supported. However serialization will seldomly be needed. This cache adapter
 * does not implement any expiry-based cache invalidation logic, as the cached data will only
 * persist for the lifetime of the current request.
  *
 * A simple configuration can be accomplished as follows:
 *
 * {{{
 * Cache::config(array(
 *     'default' => array('adapter' => 'Memory')
 * ));
 * }}}
 */
class Memory extends \lithium\storage\cache\Adapter {

	/**
	 * Array used to store cached data by this adapter
	 *
	 * @var array
	 */
	protected $_cache = array();

	/**
	 * Magic method to provide an accessor (getter) to protected class variables.
	 *
	 * @param string $variable The variable requested.
	 * @return mixed Variable if it exists, null otherwise.
	 */
	public function __get($variable) {
		if (isset($this->{"_$variable"})) {
			return $this->{"_$variable"};
		}
	}

	/**
	 * Read values from the cache. Will attempt to return an array of data
	 * containing key/value pairs of the requested data.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return Closure Function returning cached values keyed by cache keys
	 *                 on successful read, keys which could not be read will
	 *                 not be included in the results array.
	 */
	public function read(array $keys) {
		$cache =& $this->_cache;

		return function($self, $params) use (&$cache) {
			$results = array();

			foreach ($params['keys'] as $key) {
				if (array_key_exists($key, $cache)) {
					$results[$key] = $cache[$key];
				}
			}
			return $results;
		};
	}

	/**
	 * Write values to the cache.
	 *
	 * @param array $keys Key/value pairs with keys to uniquely identify the to-be-cached item.
	 * @param mixed $data The value to be cached.
	 * @param null|string $expiry Unused.
	 * @return Closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
	public function write(array $keys, $expiry = null) {
		$cache =& $this->_cache;

		return function($self, $params) use (&$cache) {
			foreach ($params['keys'] as $key => &$value) {
				$cache[$key] = $value;
			}
			return true;
		};
	}

	/**
	 * Will attempt to remove specified keys from the user space cache.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return Closure Function returning `true` on successful delete, `false` otherwise.
	 */
	public function delete(array $keys) {
		$cache =& $this->_cache;

		return function($self, $params) use (&$cache) {
			foreach ($params['keys'] as $key) {
				if (!isset($cache[$key])) {
					return false;
				}
				unset($cache[$key]);
			}
			return true;
		};
	}

	/**
	 * Performs a decrement operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to decrement.
	 * @param integer $offset Offset to decrement - defaults to 1.
	 * @return Closure Function returning item's new value on successful decrement,
	 *         `false` otherwise.
	 */
	public function decrement($key, $offset = 1) {
		$cache =& $this->_cache;

		return function($self, $params) use (&$cache, $offset) {
			extract($params);
			return $cache[$key] -= 1;
		};
	}

	/**
	 * Performs an increment operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to increment.
	 * @param integer $offset Offset to increment - defaults to 1.
	 * @return Closure Function returning item's new value on successful increment,
	 *         `false` otherwise.
	 */
	public function increment($key, $offset = 1) {
		$cache =& $this->_cache;

		return function($self, $params) use (&$cache, $offset) {
			extract($params);
			return $cache[$key] += 1;
		};
	}

	/**
	 * Clears user-space cache
	 *
	 * @return mixed True on successful clear, false otherwise.
	 */
	public function clear() {
		foreach ($this->_cache as $key => &$value) {
			unset($this->_cache[$key]);
		}
		return true;
	}
}

?>