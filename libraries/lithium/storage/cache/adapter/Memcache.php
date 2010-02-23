<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

use \lithium\util\Set;

/**
 * A Memcache (libmemcached) cache adapter implementation.
 *
 * The Memcache cache adapter is meant to be used through the `Cache` interface,
 * which abstracts away key generation, adapter instantiation and filter
 * implementation.
 *
 * A simple configuration of this adapter can be accomplished in `app/config/bootstrap.php`
 * as follows:
 *
 * {{{
 * Cache::config(array(
 *     'cache-config-name' => array(
 *         'adapter' => 'Memcached',
 *         'servers' => array(
 *             array('127.0.0.1', 11211, 100)
 *         )
 *     )
 * ));
 * }}}
 *
 * The 'servers' key accepts entries as arrays, where the format is `array(server, port, [weight])`,
 * with the weight being optional.
 *
 * This Memcache adapter provides basic support for `write`, `read`, `delete`
 * and `clear` cache functionality, as well as allowing the first four
 * methods to be filtered as per the Lithium filtering system.
 *
 * This adapter supports multi-key `write` and `read` operations.
 *
 * @see lithium\storage\Cache::key()
 * @see lithium\storage\Cache::adapter()
 *
 */
class Memcache extends \lithium\core\Object {

	/**
	 * Memcache object instance used by this adapter.
	 *
	 * @var object Memcache object
	 */
	public static $connection = null;

	/**
	 * Object constructor.
	 * Instantiates the Memcached object, adds appropriate servers to the pool,
	 * and configures any optional settings passed.
	 *
	 * @param array $config Configuration parameters for this cache adapter.
	 *        These settings are indexed by name and queryable
	 *        through `Cache::config('name')`.
	 *
	 * @return void
	 * @see lithium\storage\Cache::config()
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'prefix' => '',
			'servers' => array(
				array('127.0.0.1', 11211, 100)
			)
		);

		if (is_null(static::$connection)) {
			static::$connection = new \Memcached();
		}
		$configuration = Set::merge($defaults, $config);
		parent::__construct($configuration);

		static::$connection->addServers($this->_config['servers']);
	}

	/**
	 * Write value(s) to the cache.
	 *
	 * This adapter method supports multi-key write. By specifying `$key` as an
	 * associative array of key/value pairs, `$data` is ignored and all keys that
	 * are cached will receive an expiration time of `$expiry`.
	 *
	 * @param string|array $key The key to uniquely identify the cached item.
	 * @param mixed $value The value to be cached.
	 * @param string $expiry A strtotime() compatible cache time.
	 * @return boolean True on successful write, false otherwise.
	 */
	public function write($key, $value, $expiry) {
		$connection =& static::$connection;

		return function($self, $params, $chain) use (&$connection) {
			$expires = strtotime($params['expiry']);
			$key = $params['key'];

			if (is_array($key)) {
				return $connection->setMulti($key, $expires);
			}
			return $connection->set($key, $params['data'], $expires);
		};
	}

	/**
	 * Read value(s) from the cache.
	 *
	 * This adapter method supports multi-key reads. By specifying `$key` as an
	 * array of key names, this adapter will attempt to return an array of data
	 * containing key/value pairs of the requested data.
	 *
	 * @param string|array $key The key to uniquely identify the cached item.
	 * @return mixed Cached value if successful, false otherwise.
	 * @todo Refactor to use RES_NOTFOUND for return value checks.
	 */
	public function read($key) {
		$connection =& static::$connection;

		return function($self, $params, $chain) use (&$connection) {
			$key = $params['key'];

			if (is_array($key)) {
				return $connection->getMulti($key);
			}
			return $connection->get($key);
		};
	}

	/**
	 * Delete value from the cache.
	 *
	 * @param string $key The key to uniquely identify the cached item.
	 * @return mixed True on successful delete, false otherwise.
	 */
	public function delete($key) {
		$connection =& static::$connection;

		return function($self, $params, $chain) use (&$connection) {
			return $connection->delete($params['key']);
		};
	}

	/**
	 * Performs an atomic decrement operation on specified numeric cache item.
	 *
	 * Note that, as per the Memcached specification:
	 * "If the item's value is not numeric, it is treated as if the value were 0.
	 * If the operation would decrease the value below 0, the new value will be 0."
	 * (see http://www.php.net/manual/memcached.decrement.php)
	 *
	 * @param string $key Key of numeric cache item to decrement
	 * @param integer $offset Offset to decrement - defaults to 1.
	 * @return mixed Item's new value on successful decrement, false otherwise
	 */
	public function decrement($key, $offset = 1) {
		$connection =& static::$connection;

		return function($self, $params, $chain) use (&$connection, $offset) {
			return $connection->decrement($params['key'], $offset);
		};
	}

	/**
	 * Performs an atomic increment operation on specified numeric cache item.
	 *
	 * Note that, as per the Memcached specification:
	 * "If the item's value is not numeric, it is treated as if the value were 0."
	 * (see http://www.php.net/manual/memcached.decrement.php)
	 *
	 * @param string $key Key of numeric cache item to increment
	 * @param integer $offset Offset to increment - defaults to 1.
	 * @return mixed Item's new value on successful increment, false otherwise
	 */
	public function increment($key, $offset = 1) {
		$connection =& static::$connection;

		return function($self, $params, $chain) use (&$connection, $offset) {
			return $connection->increment($params['key'], $offset);
		};
	}

	/**
	 * Clears user-space cache.
	 *
	 * @return mixed True on successful clear, false otherwise.
	 */
	public function clear() {
		return static::$connection->flush();
	}

	/**
	 * Determines if the Memcached extension has been installed.
	 *
	 * @return boolean Returns `true` if the Memcached extension is installed and enabled, `false`
	 *         otherwise.
	 */
	public static function enabled() {
		return extension_loaded('memcached');
	}
}

?>