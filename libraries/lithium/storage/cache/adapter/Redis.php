<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

/**
 * A Redis (phpredis) cache adapter implementation.
 *
 * This adapter uses the `phpredis` PHP extension, which can be found here:
 * http://github.com/owlient/phpredis
 *
 * The Redis cache adapter is meant to be used through the `Cache` interface,
 * which abstracts away key generation, adapter instantiation and filter
 * implementation. This adapter does not aim to provide a full implementation of the
 * Redis API, but rather only a subset of its features that are useful in the context of a
 * semi-persistent cache.
 *
 * A simple configuration of this adapter can be accomplished in `app/config/bootstrap.php`
 * as follows:
 *
 * {{{
 * Cache::config(array(
 *     'cache-config-name' => array(
 *         'adapter' => 'Redis',
 *         'server' => '127.0.0.1:6379'
 *     )
 * ));
 * }}}
 *
 * The 'server' key accepts a string argument in the format of ipaddress:port where the Redis
 * server can be found.
 *
 * This Redis adapter provides basic support for `write`, `read`, `delete`
 * and `clear` cache functionality, as well as allowing the first four
 * methods to be filtered as per the Lithium filtering system. Additionally,
 * This adapter defines several methods that are _not_ implemented in other
 * adapters, and are thus non-portable - see the documentation for `Cache`
 * as to how these methods should be accessed.
 *
 * @see lithium\storage\Cache::key()
 * @see lithium\storage\Cache::adapter()
 *
 */
class Redis extends \lithium\core\Object {

	/**
	 * Redis object instance used by this adapter.
	 *
	 * @var object Memcache object
	 */
	protected static $_Redis = null;

	/**
	 * Object constructor
	 *
	 * Instantiates the Redis object and connects it to the configured server.
	 *
	 * @param array $config Configuration parameters for this cache adapter.
	 *        These settings are indexed by name and queryable through `Cache::config('name')`.
	 *
	 * @return void
	 * @see lithium\storage\Cache::config()
	 * @todo Implement configurable & optional authentication
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'prefix' => '',
			'server' => '127.0.0.1:6379'
		);

		if (is_null(static::$_Redis)) {
			static::$_Redis = new \Redis();
		}

		$config += $defaults;
		parent::__construct($config);

		list($IP, $port) = explode(':', $this->_config['server']);
		static::$_Redis->connect($IP, $port);
	}

	/**
	 * Sets expiration time for cache keys
	 *
	 * @param string $key The key to uniquely identify the cached item
	 * @param string $expiry A strtotime() compatible cache time
	 * @return boolean True if expiry could be set for the given key, false otherwise
	 */
	protected function _ttl($key, $expiry) {
		$expires = strtotime($expiry) - time();
		return static::$_Redis->setTimeout($key, $expires);
	}

	/**
	 * Write value(s) to the cache
	 *
	 * @param string $key The key to uniquely identify the cached item
	 * @param mixed $value The value to be cached
	 * @param string $expiry A strtotime() compatible cache time
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($key, $value, $expiry) {
		$Redis =& static::$_Redis;

		return function($self, $params, $chain) use (&$Redis) {
			extract($params);
			if($Redis->set($key, $data)){
				return $self->invokeMethod('_ttl', array($key, $expiry));
			}
		};
	}

	/**
	 * Read value(s) from the cache
	 *
	 * @param string $key The key to uniquely identify the cached item
	 * @return mixed Cached value if successful, false otherwise
	 */
	public function read($key) {
		$Redis =& static::$_Redis;

		return function($self, $params, $chain) use (&$Redis) {
			extract($params);
			return $Redis->get($key);
		};
	}

	/**
	 * Delete value from the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @return mixed True on successful delete, false otherwise
	 */
	public function delete($key) {
		$Redis =& static::$_Redis;

		return function($self, $params, $chain) use (&$Redis) {
			extract($params);
			return (boolean) $Redis->delete($key);
		};
	}

	/**
	 * Performs an atomic decrement operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to decrement
	 * @param integer $offset Offset to decrement - defaults to 1.
	 * @return mixed Item's new value on successful decrement, false otherwise
	 */
	public function decrement($key, $offset = 1) {
		$Redis =& static::$_Redis;

		return function($self, $params, $chain) use (&$Redis, $offset) {
			extract($params);
			return $Redis->decr($key, $offset);
		};
	}

	/**
	 * Performs an atomic increment operation on specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to increment
	 * @param integer $offset Offset to increment - defaults to 1.
	 * @return mixed Item's new value on successful increment, false otherwise
	 */
	public function increment($key, $offset = 1) {
		$Redis =& static::$_Redis;

		return function($self, $params, $chain) use (&$Redis, $offset) {
			extract($params);
			return $Redis->incr($key, $offset);
		};
	}

	/**
	 * Clears user-space cache
	 *
	 * @return mixed True on successful clear, false otherwise
	 */
	public function clear() {
		return static::$_Redis->flushdb();
	}

	/**
	 * Determines if the Redis extension has been installed and
	 * that there is a redis-server available
	 *
	 * return boolean True if enabled, false otherwise
	 */
	public function enabled() {
		if (!extension_loaded('redis')) {
			return false;
		}
		$version = static::$_Redis->info();
		return (!empty($version));
	}
}

?>