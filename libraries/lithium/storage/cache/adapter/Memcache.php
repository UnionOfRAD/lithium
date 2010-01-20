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
 * methods to be filtered as per the Lithium filtering system. Additionally,
 * This adapter defines several methods that are _not_ implemented in other
 * adapters, and are thus non-portable - see the documentation for `Cache`
 * as to how these methods should be accessed.
 *
 * This adapter stores two keys for each written value - one which consists
 * of the data to be cached, and the other being a cache of the expiration time.
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
	protected static $_Memcached = null;

	/**
	 * Object constructor.
	 * Instantiates the Memcached object, adds appropriate servers to the pool,
	 * and configures any optional settings passed.
	 *
	 * @param  array $config Configuration parameters for this cache adapter.
	 *                       These settings are indexed by name and queryable
	 *                       through `Cache::config('name')`.
	 *
	 * @return void
	 * @see lithium\storage\Cache::config()
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'prefix' => '',
			'servers' => array(
				array('127.0.0.1', 11211, 100)
			)
		);

		if (is_null(static::$_Memcached)) {
			static::$_Memcached = new \Memcached();
		}

		$configuration = Set::merge($defaults, $config);
		parent::__construct($configuration);

		static::$_Memcached->addServers($this->_config['servers']);
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
		$Memcached =& static::$_Memcached;

		return function($self, $params, $chain) use (&$Memcached) {
			extract($params);
			$expires = strtotime($expiry);

			$Memcached->set($key . '_expires', $expires, $expires);
			return $Memcached->set($key, $data, $expires);

		};
	}

	/**
	 * Read value(s) from the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @return mixed Cached value if successful, false otherwise
	 * @todo Refactor to use RES_NOTFOUND for return value checks
	 */
	public function read($key) {
		$Memcached =& static::$_Memcached;

		return function($self, $params, $chain) use (&$Memcached) {
			extract($params);
			$cachetime = intval($Memcached->get($key . '_expires'));
			$time = time();
			return ($cachetime < $time) ? false : $Memcached->get($key);
		};
	}

	/**
	 * Delete value from the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @return mixed True on successful delete, false otherwise
	 */
	public function delete($key) {
		$Memcached =& static::$_Memcached;

		return function($self, $params, $chain) use (&$Memcached) {
			extract($params);
			$Memcached->delete($key . '_expires');
			return $Memcached->delete($key);
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
		$Memcached =& static::$_Memcached;

		return function($self, $params, $chain) use (&$Memcached, $offset) {
			extract($params);
			return $Memcached->decrement($key, $offset);
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
		$Memcached =& static::$_Memcached;

		return function($self, $params, $chain) use (&$Memcached, $offset) {
			extract($params);
			return $Memcached->increment($key, $offset);
		};
	}

	/**
	 * Clears user-space cache
	 *
	 * @return mixed True on successful clear, false otherwise
	 */
	public function clear() {
		return static::$_Memcached->flush();
	}

	/**
	 * Determines if the Memcached extension has been installed and
	 * properly started.
	 *
	 * @todo make this a bit smarter.
	 * return boolean True if enabled, false otherwise
	 */
	public function enabled() {
		if (!extension_loaded('memcached')) {
			return false;
		}
		$version = static::$_Memcached->getVersion();

		if (empty($version)) {
			return false;
		}
		return true;
	}
}

?>