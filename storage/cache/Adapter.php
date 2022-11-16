<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2014, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\cache;

use lithium\core\AutoConfigurable;

/**
 * This is the foundation class for all cache adapters.
 *
 * Each adapter provides a consistent interface for the basic cache operations of `write`, `read`,
 * `delete`, `increment` and `decrement`  which can always be _used interchangeably_ between and
 * must be implemented by all adapters.
 *
 * Functionality for `clear`, `clean`  may or may not be implemented by an adapter. Calling a
 * method that is not implemented will simply return `false`.
 *
 * An adapter may provide access to additional methods. It's always possible to call them directly.
 * This allows a very wide range of flexibility, at the cost of portability.
 *
 * ```
 * Cache::adapter('default')->methodName($argument);
 * ```
 *
 * It is not guaranteed that all operations are atomic, but adapters will try to perform atomic
 * operations wherever possible. If you rely on atomicity of operations you must choose
 * an appropriate adapter that explitcly supports these.
 *
 * Adapters may handle serialization and/or multi-keys natively others only synthetically.
 */
abstract class Adapter {

	use AutoConfigurable;

	/**
	 * Generates safe cache keys.
	 *
	 * @param array $keys The original keys.
	 * @return array Keys modified and safe to use with adapter.
	 */
	public function key(array $keys) {
		return $keys;
	}

	/**
	 * Write values to the cache. All items to be cached will receive an
	 * expiration time of `$expiry`.
	 *
	 * @param array $keys Key/value pairs with keys to uniquely identify the to-be-cached item.
	 * @param string|integer $expiry A `strtotime()` compatible cache time or TTL in seconds.
	 *                       To persist an item use `\lithium\storage\Cache::PERSIST`.
	 * @return boolean `true` on successful write, `false` otherwise.
	 */
	abstract public function write(array $keys, $expiry = null);

	/**
	 * Read values from the cache. Will attempt to return an array of data
	 * containing key/value pairs of the requested data.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return array Cached values keyed by cache keys on successful read,
	 *               keys which could not be read will not be included in
	 *               the results array.
	 */
	abstract public function read(array $keys);

	/**
	 * Will attempt to remove specified keys from the user space cache.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return boolean `true` on successful delete, `false` otherwise.
	 */
	abstract public function delete(array $keys);

	/**
	 * Performs a decrement operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to decrement.
	 * @param integer $offset Offset to decrement - defaults to `1`.
	 * @return integer|boolean The item's new value on successful decrement, else `false`.
	 */
	abstract public function decrement($key, $offset = 1);

	/**
	 * Performs an increment operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to increment.
	 * @param integer $offset Offset to increment - defaults to `1`.
	 * @return integer|boolean The item's new value on successful increment, else `false`.
	 */
	abstract public function increment($key, $offset = 1);

	/**
	 * Clears entire cache by flushing it. All cache keys using the
	 * configuration but *without* honoring the scope are removed.
	 *
	 * @return boolean `true` on successful clearing, `false` if failed partially or entirely.
	 */
	public function clear() {
		return false;
	}

	/**
	 * Perform garbage collection.
	 *
	 * @return boolean `true` on successful clean, `false` otherwise.
	 */
	public function clean() {
		return false;
	}

	/**
	 * Determines if an adapter is available for usage and all
	 * preconditions are met (i.e. extension installed).
	 *
	 * Override to check for preconditions.
	 *
	 * @return boolean `true` if enabled, `false` otherwise.
	 */
	public static function enabled() {
		return true;
	}

	/**
	 * Adds scope prefix to keys using separator.
	 *
	 * @param string $scope Scope to use when prefixing.
	 * @param array $keys Array of keys either with or without mapping to values.
	 * @param string $separator String to use when separating scope from key.
	 * @return array Prefixed keys array.
	 */
	protected function _addScopePrefix($scope, array $keys, $separator = ':') {
		$results = [];
		$isMapped = !is_int(key($keys));

		foreach ($keys as $key => $value) {
			if ($isMapped) {
				$results["{$scope}{$separator}{$key}"] = $value;
			} else {
				$results[$key] = "{$scope}{$separator}{$value}";
			}
		}
		return $results;
	}

	/**
	 * Removes scope prefix from keys.
	 *
	 * @param string $scope Scope initially used when prefixing.
	 * @param array $keys Array of keys mapping to values.
	 * @param string $separator Separator used when prefix keys initially.
	 * @return array Keys array with prefix removed from each key.
	 */
	protected function _removeScopePrefix($scope, array $data, $separator = ':') {
		$results = [];
		$prefix = strlen("{$scope}{$separator}");

		foreach ($data as $key => $value) {
			$results[substr($key, $prefix)] = $value;
		}
		return $results;
	}
}

?>