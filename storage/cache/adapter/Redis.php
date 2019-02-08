<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\cache\adapter;

use lithium\storage\Cache;
use lithium\net\HostString;
use Redis as RedisCore;

/**
 * A Redis cache adapter implementation using `phpredis`.
 *
 * This adapter does not aim to provide a full implementation of the Redis API, but rather
 * only a subset of its features that are useful in the context of a semi-persistent cache.
 *
 * This adapter depends on the `phpredis` PHP extension and on a running
 * instance of Redis server being available.
 *
 * This adapter natively handles atomic multi-key reads/writes/deletes and supports atomic
 * increment/decrement operations as well as clearing the entire cache. Scope support is
 * natively available. Delegation of method calls to the connection object is available.
 *
 * Serialization of values is not handled natively, the `Serializer` strategy must be used
 * if you plan to store non-scalar values or need to keep type on values. Full cached item
 * persistence is not guaranteed it depends on the how the Redis server is actually configured
 * and accessed.
 *
 * A simple configuration can be accomplished as follows:
 *
 * ```
 * Cache::config([
 *     'cache-config-name' => [
 *         'adapter' => 'Redis',
 *         'host' => '127.0.0.1:6379',
 *         'strategies => ['Serializer']
 *     ]
 * ]);
 * ```
 *
 * The `'host'` key accepts a string argument in the format of ip:port where
 * the Redis server can be found.
 *
 * @link https://github.com/nicolasff/phpredis
 * @see lithium\storage\Cache::key()
 * @see lithium\storage\Cache::adapter()
 */
class Redis extends \lithium\storage\cache\Adapter {

	/**
	 * The default host used to connect to the server.
	 */
	const DEFAULT_HOST = '127.0.0.1';

	/**
	 * The default port used to connect to the server.
	 */
	const DEFAULT_PORT = 6379;

	/**
	 * Redis object instance used by this adapter.
	 *
	 * @var object Redis object
	 */
	public $connection;

	/**
	 * Constructor.
	 *
	 * @todo Implement configurable & optional authentication
	 * @see lithium\storage\Cache::config()
	 * @see lithium\storage\cache\adapter\Redis::write()
	 * @param array $config Configuration for this cache adapter. These settings are queryable
	 *        through `Cache::config('name')`. The available options are as follows:
	 *        - `'scope'` _string_: Scope which will prefix keys; per default not set.
	 *        - `'expiry'` _mixed_: The default expiration time for cache values, if no value
	 *          is otherwise set. Can be either a `strtotime()` compatible tring or TTL in
	 *          seconds. To indicate items should not expire use `Cache::PERSIST`. Defaults
	 *          to `+1 hour`.
	 *        - `'host'` _string_: A string in the form of `'<host>'`, `'<host>:<port>'` or
	 *          `':<port>'` indicating the host and/or port to connect to. When one or both are
	 *          not provided uses general server defaults.
	 *          To use Unix sockets specify the path to the socket (i.e. `'/path/to/socket'`).
	 *        - `'persistent'` _boolean_: Indicates whether the adapter should use a persistent
	 *          connection when attempting to connect to the Redis server. If `true`, it will
	 *          attempt to reuse an existing connection when connecting, and the connection will
	 *          not close when the request is terminated. Defaults to `false`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'scope' => null,
			'expiry' => '+1 hour',
			'host' => static::DEFAULT_HOST . ':' . static::DEFAULT_PORT,
			'persistent' => false
		];
		parent::__construct($config + $defaults);
	}

	/**
	 * Initialize the Redis connection object, connect to the Redis server and sets
	 * prefix using the scope if provided.
	 *
	 * @return void
	 */
	protected function _init() {
		if (!$this->connection) {
			$this->connection = new RedisCore();
		}
		$method = $this->_config['persistent'] ? 'pconnect' : 'connect';

		if (HostString::isSocket($this->_config['host'])) {
			$this->connection->{$method}($this->_config['host']);
		} else {
			$host = HostString::parse($this->_config['host']) + [
				'host' => static::DEFAULT_HOST,
				'port' => static::DEFAULT_PORT
			];
			$this->connection->{$method}($host['host'], $host['port']);
		}

		if ($this->_config['scope']) {
			$this->connection->setOption(RedisCore::OPT_PREFIX, "{$this->_config['scope']}:");
		}
	}

	/**
	 * Dispatches a not-found method to the connection object. That way, one can
	 * easily use a custom method on the adapter. If you want to know, what methods
	 * are available, have a look at the documentation of phpredis.
	 *
	 * ```
	 * Cache::adapter('redis')->methodName($argument);
	 * ```
	 *
	 * One use-case might be to query possible keys, e.g.
	 *
	 * ```
	 * Cache::adapter('redis')->keys('*');
	 * ```
	 *
	 * @link https://github.com/nicolasff/phpredis
	 * @param string $method Name of the method to call.
	 * @param array $params Parameter list to use when calling $method.
	 * @return mixed Returns the result of the method call.
	 */
	public function __call($method, $params = []) {
		return call_user_func_array([&$this->connection, $method], $params);
	}

	/**
	 * Determines if a given method can be called.
	 *
	 * @param string $method Name of the method.
	 * @param boolean $internal Provide `true` to perform check from inside the
	 *                class/object. When `false` checks also for public visibility;
	 *                defaults to `false`.
	 * @return boolean Returns `true` if the method can be called, `false` otherwise.
	 */
	public function respondsTo($method, $internal = false) {
		if (parent::respondsTo($method, $internal)) {
			return true;
		}
		return is_callable([$this->connection, $method]);
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
			$ttl = null;
		} elseif (is_int($expiry)) {
			$ttl = $expiry;
		} else {
			$ttl = strtotime($expiry) - time();
		}

		if (count($keys) > 1) {
			if (!$ttl) {
				return $this->connection->mset($keys);
			}
			$transaction = $this->connection->multi();

			foreach ($keys as $key => $value) {
				if (!$this->connection->setex($key, $ttl, $value)) {
					$transaction->discard();
					return false;
				}
			}
			return $transaction->exec() === array_fill(0, count($keys), true);
		}
		$key = key($keys);
		$value = current($keys);

		if (!$ttl) {
			return $this->connection->set($key, $value);
		}
		return $this->connection->setex($key, $ttl, $value);
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
			$results = [];
			$data = $this->connection->mGet($keys);

			foreach ($data as $key => $item) {
				$key = $keys[$key];

				if ($item === false && !$connection->exists($key)) {
					continue;
				}
				$results[$key] = $item;
			}
			return $results;
		}
		$result = $this->connection->get($key = current($keys));
		return $result === false ? [] : [$key => $result];
	}

	/**
	 * Will attempt to remove specified keys from the user space cache.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return boolean `true` on successful delete, `false` otherwise.
	 */
	public function delete(array $keys) {
		return (boolean) $this->connection->delete($keys);
	}

	/**
	 * Performs an atomic decrement operation on specified numeric cache item.
	 *
	 * Note that if the value of the specified key is *not* an integer, the decrement
	 * operation will have no effect whatsoever. Redis chooses to not typecast values
	 * to integers when performing an atomic decrement operation.
	 *
	 * @param string $key Key of numeric cache item to decrement.
	 * @param integer $offset Offset to decrement - defaults to `1`.
	 * @return integer|boolean The item's new value on successful decrement, else `false`.
	 */
	public function decrement($key, $offset = 1) {
		return $this->connection->decr($key, $offset);
	}

	/**
	 * Performs an atomic increment operation on specified numeric cache item.
	 *
	 * Note that if the value of the specified key is *not* an integer, the increment
	 * operation will have no effect whatsoever. Redis chooses to not typecast values
	 * to integers when performing an atomic increment operation.
	 *
	 * @param string $key Key of numeric cache item to increment.
	 * @param integer $offset Offset to increment - defaults to `1`.
	 * @return integer|boolean The item's new value on successful increment, else `false`.
	 */
	public function increment($key, $offset = 1) {
		return $this->connection->incr($key, $offset);
	}

	/**
	 * Clears entire database by flushing it. All cache keys using the
	 * configuration but *without* honoring the scope are removed.
	 *
	 * The behavior and result when removing a single key
	 * during this process fails is unknown.
	 *
	 * @return boolean `true` on successful clearing, `false` otherwise.
	 */
	public function clear() {
		return $this->connection->flushdb();
	}

	/**
	 * Determines if the Redis extension has been installed and
	 * that there is a Redis server available.
	 *
	 * @return boolean Returns `true` if the Redis extension is enabled, `false` otherwise.
	 */
	public static function enabled() {
		return extension_loaded('redis');
	}
}

?>