<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache;

/**
 * This is the foundation class for all cache adapters.
 */
abstract class Adapter extends \lithium\core\Object {

	/**
	 * Write values to the cache. All items to be cached will receive an
	 * expiration time of `$expiry`.
	 *
	 * Note that this is not an atomic operation when using multiple keys.
	 *
	 * @param array $keys Key/value pairs with keys to uniquely identify the to-be-cached item.
	 * @param string|integer $expiry A `strtotime()` compatible cache time or TTL in seconds.
	 *                       To persist an item use `\lithium\storage\Cache::PERSIST`.
	 * @return Closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
	abstract public function write(array $keys, $expiry = null);

	/**
	 * Read values from the cache. Will attempt to return an array of data
	 * containing key/value pairs of the requested data.
	 *
	 * Note that this is not an atomic operation when using multiple keys.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return Closure Function returning cached values keyed by cache keys
	 *                 on successful read, keys which could not be read will
	 *                 not be included in the results array.
	 */
	abstract public function read(array $keys);

	/**
	 * Will attempt to remove specified keys from the user space cache.
	 *
	 * Note that this is not an atomic operation when using multiple keys.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return Closure Function returning `true` on successful delete, `false` otherwise.
	 */
	abstract public function delete(array $keys);

	/**
	 * Performs an atomic decrement operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to decrement
	 * @param integer $offset Offset to decrement - defaults to 1.
	 * @return Closure Function returning item's new value on successful decrement, else `false`
	 */
	public function decrement($key, $offset = 1) {
		return false;
	}

	/**
	 * Performs an atomic increment operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to increment
	 * @param integer $offset Offset to increment - defaults to 1.
	 * @return Closure Function returning item's new value on successful increment, else `false`
	 */
	public function increment($key, $offset = 1) {
		return false;
	}

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
}

?>