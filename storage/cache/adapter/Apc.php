<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\cache\adapter;

if (!class_exists('\lithium\storage\cache\Adapter')) {
	$message  = 'The file (`' . __FILE__ . '`) has probably been included directly within ';
	$message .= 'the bootstrap process. Due to its dependencies it should be (auto-)loaded ';
	$message .= 'i.e. through the `Libraries` class exclusively. Please update your app\'s ';
	$message .= 'bootstrap directory to the most recent version or remove the line where ';
	$message .= 'this file was originally included. ';
	$message .= 'See https://git.io/voxgh for the most recent version.';
	trigger_error($message, E_USER_DEPRECATED);

	require_once LITHIUM_LIBRARY_PATH . '/lithium/storage/cache/Adapter.php';
}

use lithium\storage\Cache;

/**
 * An Alternative PHP Cache (APC) cache adapter implementation leveraging the user-
 * space caching features (not the opcode caching features) of the cache.
 *
 * This adapter requires `pecl/apc` or `pecl/apcu` to be installed. The extension
 * and user-space caching must be enabled according to the extension documention.
 *
 * This adapter natively handles multi-key reads/writes/deletes, natively
 * provides serialization features and supports atomic increment/decrement
 * operations as well as clearing the entire user-space cache.
 *
 * Cached item persistence is not guaranteed. Infrequently used items will
 * be evicted from the cache when there is no room to store new ones. Scope
 * is available but not natively.
 *
 * A simple configuration can be accomplished as follows:
 *
 * ```
 * Cache::config([
 *     'default' => ['adapter' => 'Apc']
 * ]);
 * ```
 *
 * @link http://pecl.php.net/package/APCu
 * @link http://pecl.php.net/package/APC
 * @link http://php.net/book.apc.php
 * @see lithium\storage\Cache::key()
 */
class Apc extends \lithium\storage\cache\Adapter {

	/**
	 * Constructor.
	 *
	 * @see lithium\storage\Cache::config()
	 * @param array $config Configuration for this cache adapter. These settings are queryable
	 *        through `Cache::config('name')`. The available options are as follows:
	 *        - `'scope'` _string_: Scope which will prefix keys; per default not set.
	 *        - `'expiry'` _mixed_: The default expiration time for cache values, if no value
	 *          is otherwise set. Can be either a `strtotime()` compatible tring or TTL in
	 *          seconds. To indicate items should not expire use `Cache::PERSIST`. Defaults
	 *          to `+1 hour`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'scope' => null,
			'expiry' => '+1 hour'
		];
		parent::__construct($config + $defaults);
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
	public function write(array $keys, $expiry = null) {
		$expiry = $expiry || $expiry === Cache::PERSIST ? $expiry : $this->_config['expiry'];

		if (!$expiry || $expiry === Cache::PERSIST) {
			$ttl = 0;
		} elseif (is_int($expiry)) {
			$ttl = $expiry;
		} else {
			$ttl = strtotime($expiry) - time();
		}
		if ($this->_config['scope']) {
			$keys = $this->_addScopePrefix($this->_config['scope'], $keys);
		}
		return apc_store($keys, null, $ttl) === [];
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
		if ($this->_config['scope']) {
			$keys = $this->_addScopePrefix($this->_config['scope'], $keys);
		}
		$results = apc_fetch($keys);

		if ($this->_config['scope']) {
			$results = $this->_removeScopePrefix($this->_config['scope'], $results);
		}
		return $results;
	}

	/**
	 * Will attempt to remove specified keys from the user space cache.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return boolean `true` on successful delete, `false` otherwise.
	 */
	public function delete(array $keys) {
		if ($this->_config['scope']) {
			$keys = $this->_addScopePrefix($this->_config['scope'], $keys);
		}
		return apc_delete($keys) === [];
	}

	/**
	 * Performs an atomic decrement operation on specified numeric cache item.
	 *
	 * Note that, as per the APC specification:
	 * If the item's value is not numeric, the decrement operation has no effect
	 * on the key - it retains its original non-integer value.
	 *
	 * @param string $key Key of numeric cache item to decrement.
	 * @param integer $offset Offset to decrement - defaults to `1`.
	 * @return integer|boolean The item's new value on successful decrement, else `false`.
	 */
	public function decrement($key, $offset = 1) {
		return apc_dec(
			$this->_config['scope'] ? "{$this->_config['scope']}:{$key}" : $key, $offset
		);
	}

	/**
	 * Performs an atomic increment operation on specified numeric cache item.
	 *
	 * Note that, as per the APC specification:
	 * If the item's value is not numeric, the increment operation has no effect
	 * on the key - it retains its original non-integer value.
	 *
	 * @param string $key Key of numeric cache item to increment.
	 * @param integer $offset Offset to increment - defaults to `1`.
	 * @return integer|boolean The item's new value on successful increment, else `false`.
	 */
	public function increment($key, $offset = 1) {
		return apc_inc(
			$this->_config['scope'] ? "{$this->_config['scope']}:{$key}" : $key, $offset
		);
	}

	/**
	 * Clears entire user-space cache by flushing it. All cache keys
	 * using the configuration but *without* honoring the scope are removed.
	 *
	 * The behavior and result when removing a single key
	 * during this process fails is unknown.
	 *
	 * @return boolean `true` on successful clearing, `false` otherwise.
	 */
	public function clear() {
		return apc_clear_cache('user');
	}

	/**
	 * Determines if the APC extension has been installed and
	 * if the userspace cache is available.
	 *
	 * @return boolean `true` if enabled, `false` otherwise.
	 */
	public static function enabled() {
		$loaded = extension_loaded('apc');
		$isCli = (php_sapi_name() === 'cli');
		$enabled = (!$isCli && ini_get('apc.enabled')) || ($isCli && ini_get('apc.enable_cli'));
		return ($loaded && $enabled);
	}
}

?>