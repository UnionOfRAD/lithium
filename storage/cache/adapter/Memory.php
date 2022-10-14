<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\cache\adapter;

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
 * ```
 * Cache::config([
 *     'default' => ['adapter' => 'Memory']
 * ]);
 * ```
 */
class Memory extends \lithium\storage\cache\Adapter {

	public $Memory;

	/**
	 * Array used to store cached data by this adapter
	 *
	 * @var array
	 */
	protected $_cache = [];

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
	 * @return array Cached values keyed by cache keys on successful read,
	 *               keys which could not be read will not be included in
	 *               the results array.
	 */
	public function read(array $keys) {
		$results = [];

		foreach ($keys as $key) {
			if (array_key_exists($key, $this->_cache)) {
				$results[$key] = $this->_cache[$key];
			}
		}
		return $results;
	}

	/**
	 * Write values to the cache.
	 *
	 * @param array $keys Key/value pairs with keys to uniquely identify the to-be-cached item.
	 * @param mixed $data The value to be cached.
	 * @param null|string $expiry Unused.
	 * @return boolean `true` on successful write, `false` otherwise.
	 */
	public function write(array $keys, $expiry = null) {
		foreach ($keys as $key => &$value) {
			$this->_cache[$key] = $value;
		}
		return true;
	}

	/**
	 * Will attempt to remove specified keys from the user space cache.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return boolean `true` on successful delete, `false` otherwise.
	 */
	public function delete(array $keys) {
		foreach ($keys as $key) {
			if (!isset($this->_cache[$key])) {
				return false;
			}
			unset($this->_cache[$key]);
		}
		return true;
	}

	/**
	 * Performs a decrement operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to decrement.
	 * @param integer $offset Offset to decrement - defaults to `1`.
	 * @return integer|boolean The item's new value on successful decrement, else `false`.
	 */
	public function decrement($key, $offset = 1) {
		if (!array_key_exists($key, $this->_cache)) {
			return false;
		}
		return $this->_cache[$key] -= $offset;
	}

	/**
	 * Performs an increment operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to increment.
	 * @param integer $offset Offset to increment - defaults to `1`.
	 * @return integer|boolean The item's new value on successful increment, else `false`.
	 */
	public function increment($key, $offset = 1) {
		if (!array_key_exists($key, $this->_cache)) {
			return false;
		}
		return $this->_cache[$key] += $offset;
	}

	/**
	 * Clears entire cache by flushing it. All cache keys using the
	 * configuration but *without* honoring the scope are removed.
	 *
	 * The operation will continue to remove keys even if removing
	 * one single key fails, clearing thoroughly as possible. In any case
	 * this method will return `true`.
	 *
	 * @return boolean Always returns `true`.
	 */
	public function clear() {
		foreach ($this->_cache as $key => &$value) {
			unset($this->_cache[$key]);
		}
		return true;
	}
}

?>