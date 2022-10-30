<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\cache\adapter;

use Memcached;
use lithium\util\Set;
use lithium\storage\Cache;
use lithium\net\HostString;

/**
 * Memcache (libmemcached) cache adapter implementation using `pecl/memcached`.
 *
 * This adapter requires `pecl/memcached` to be installed. The extension
 * must be enabled according to the extension documention and a running
 * memcached server instance must be available.
 *
 * This adapter natively handles multi-key reads/writes/deletes, natively
 * provides serialization and key scoping features and supports atomic
 * increment/decrement operations as well as clearing the entire cache.
 * Delegation of method calls to the connection object is available.
 *
 * Cached item persistence is not guaranteed. Infrequently used items will
 * be evicted from the cache when there is no room to store new ones.
 *
 * A simple configuration can be accomplished as follows:
 *
 * ```
 * Cache::config([
 *     'default' => [
 *         'adapter' => 'Memcached',
 *         'host' => '127.0.0.1:11211'
 *     ]
 * ]);
 * ```
 *
 * The `'host'` key accepts entries in multiple formats, depending on the number of
 * Memcache servers you are connecting to. See the `__construct()` method for more
 * information.
 *
 * @link http://php.net/class.memcached.php
 * @link http://pecl.php.net/package/memcached
 * @see lithium\storage\cache\adapter\Memcache::__construct()
 * @see lithium\storage\Cache::key()
 * @see lithium\storage\Cache::adapter()
 */
class Memcache extends \lithium\storage\cache\Adapter {

	/**
	 * The default host used to connect to the server.
	 */
	const DEFAULT_HOST = '127.0.0.1';

	/**
	 * The default port used to connect to the server.
	 */
	const DEFAULT_PORT = 11211;

	/**
	 * `Memcached` object instance used by this adapter.
	 *
	 * @var object
	 */
	public $connection = null;

	/**
	 * Constructor. Instantiates the `Memcached` object, adds appropriate servers to the pool,
	 * and configures any optional settings passed (see the `_init()` method).
	 *
	 * @see lithium\storage\Cache::config()
	 * @param array $config Configuration for this cache adapter. These settings are queryable
	 *        through `Cache::config('name')`. The available options are as follows:
	 *        - `'scope'` _string_: Scope which will prefix keys; per default not set.
	 *        - `'expiry'` _mixed_: The default expiration time for cache values, if no value
	 *          is otherwise set. Can be either a `strtotime()` compatible tring or TTL in
	 *          seconds. To indicate items should not expire use `Cache::PERSIST`. Defaults
	 *          to `+1 hour`.
	 *        - `'host'` _string|array_: A string in the form of `'<host>'`, `'<host>:<port>'` or
	 *          `':<port>'` indicating the host and/or port to connect to. When one or both are
	 *          not provided uses general server defaults.
	 *          Use the array format for multiple hosts (optionally with server selection weights):
	 *          `['167.221.1.5:11222', '167.221.1.6']`
	 *          `['167.221.1.5:11222' => 200, '167.221.1.6']`
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'scope' => null,
			'expiry' => '+1 hour',
			'host' => static::DEFAULT_HOST . ':' . static::DEFAULT_PORT
		];
		parent::__construct(Set::merge($defaults, $config));
	}

	/**
	 * Generates safe cache keys.
	 *
	 * As per the protocol no control characters or whitespace is allowed
	 * in the key name. There's also a limit of max. 250 characters which is
	 * checked and enforced here. The limit is actually lowered to 250 minus
	 * the length of an crc32b hash minus separator (241) minus scope length
	 * minus separator (241 - x).
	 *
	 * @param array $keys The original keys.
	 * @return array Keys modified and safe to use with adapter.
	 */
	public function key(array $keys) {
		$length = 241 - ($this->_config['scope'] ? strlen($this->_config['scope']) + 1 : 0);

		return array_map(
			function($key) use ($length) {
				$result = substr(preg_replace('/[[:cntrl:]\s]/u', '_', $key), 0, $length);
				return $key !== $result ? $result . '_' . hash('crc32b', $key) : $result;
			},
			$keys
		);
	}

	/**
	 * Handles the actual `Memcached` connection and server connection
	 * adding for the adapter constructor and sets prefix using the scope
	 * if provided.
	 *
	 * @return void
	 */
	protected function _init() {
		$this->connection = $this->connection ?: new Memcached();
		$this->connection->addServers($this->_formatHostList($this->_config['host']));

		if ($this->_config['scope']) {
			$this->connection->setOption(Memcached::OPT_PREFIX_KEY, "{$this->_config['scope']}:");
		}
	}

	/**
	 * Dispatches a not-found method to the connection object. That way, one can
	 * easily use a custom method on the adapter. If you want to know, what methods
	 * are available, have a look at the documentation of memcached.
	 *
	 * ```
	 * Cache::adapter('memcache')->methodName($argument);
	 * ```
	 *
	 * @link http://php.net/class.memcached.php
	 * @param string $method Name of the method to call.
	 * @param array $params Parameter list to use when calling $method.
	 * @return mixed Returns the result of the method call.
	 */
	public function __call($method, $params = []) {
		return call_user_func_array([&$this->connection, $method], $params);
	}

	/**
	 * Formats standard `'host:port'` strings into arrays used by `Memcached`.
	 *
	 * @param mixed $host A host string in `'host:port'` format, or an array of host strings
	 *              optionally paired with relative selection weight values.
	 * @return array Returns an array of `Memcached` server definitions.
	 */
	protected function _formatHostList($host) {
		$hosts = [];

		foreach ((array) $this->_config['host'] as $host => $weight) {
			$host = HostString::parse(($hasWeight = is_integer($weight)) ? $host : $weight) + [
				'host' => static::DEFAULT_HOST,
				'port' => static::DEFAULT_PORT
			];
			$host = [$host['host'], $host['port']];

			if ($hasWeight) {
				$host[] = $weight;
			}
			$hosts[] = $host;
		}
		return $hosts;
	}

	/**
	 * Write values to the cache. All items to be cached will receive an
	 * expiration time of `$expiry`.
	 *
	 * Expiration is always based off the current unix time in order to gurantee we never
	 * exceed the TTL limit of 30 days when specifying the TTL directly.
	 *
	 * @param array $keys Key/value pairs with keys to uniquely identify the to-be-cached item.
	 * @param string|integer $expiry A `strtotime()` compatible cache time or TTL in seconds.
	 *                       To persist an item use `\lithium\storage\Cache::PERSIST`.
	 * @return boolean `true` on successful write, `false` otherwise.
	 */
	public function write(array $keys, $expiry = null) {
		$expiry = $expiry || $expiry === Cache::PERSIST ? $expiry : $this->_config['expiry'];

		if (!$expiry || $expiry === Cache::PERSIST) {
			$expires = 0;
		} elseif (is_int($expiry)) {
			$expires = $expiry + time();
		} else {
			$expires = strtotime($expiry);
		}
		if (count($keys) > 1) {
			return $this->connection->setMulti($keys, $expires);
		}
		return $this->connection->set(key($keys), current($keys), $expires);
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
		if (count($keys) > 1) {
			if (!$results = $this->connection->getMulti($keys)) {
				return [];
			}
		} else {
			$result = $this->connection->get($key = current($keys));

			if ($result === false && $this->connection->getResultCode() === Memcached::RES_NOTFOUND) {
				return [];
			}
			$results = [$key => $result];
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
		if (count($keys) > 1) {
			return $this->connection->deleteMulti($keys);
		}
		return $this->connection->delete(current($keys));
	}

	/**
	 * Performs an atomic decrement operation on specified numeric cache item.
	 *
	 * Note that, as per the Memcached specification:
	 * "If the item's value is not numeric, it is treated as if the value were 0.
	 * If the operation would decrease the value below 0, the new value will be 0."
	 *
	 * @link http://php.net/manual/memcached.decrement.php
	 * @param string $key Key of numeric cache item to decrement.
	 * @param integer $offset Offset to decrement - defaults to `1`.
	 * @return integer|boolean The item's new value on successful decrement, else `false`.
	 */
	public function decrement($key, $offset = 1) {
		return $this->connection->decrement($key, $offset);
	}

	/**
	 * Performs an atomic increment operation on specified numeric cache item.
	 *
	 * Note that, as per the Memcached specification:
	 * "If the item's value is not numeric, it is treated as if the value were 0."
	 *
	 * @link http://php.net/manual/memcached.decrement.php
	 * @param string $key Key of numeric cache item to increment.
	 * @param integer $offset Offset to increment - defaults to `1`.
	 * @return integer|boolean The item's new value on successful increment, else `false`.
	 */
	public function increment($key, $offset = 1) {
		return $this->connection->increment($key, $offset);
	}

	/**
	 * Clears entire cache by flushing it. All cache keys using the
	 * configuration but *without* honoring the scope are removed.
	 *
	 * Internally keys are not removed but invalidated. Thus this
	 * operation doesn't actually free memory on the instance.
	 *
	 * The behavior and result when removing a single key
	 * during this process fails is unknown.
	 *
	 * @return boolean `true` on successful clearing, `false` otherwise.
	 */
	public function clear() {
		return $this->connection->flush();
	}

	/**
	 * Determines if the `Memcached` extension has been installed.
	 *
	 * @return boolean Returns `true` if the `Memcached` extension is installed and enabled, `false`
	 *         otherwise.
	 */
	public static function enabled() {
		return extension_loaded('memcached');
	}
}

?>