<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapters;

use \lithium\util\Set;

/**
 * A Memcache (libmemcached) cache adapter implementation.
 *
 * The Memcache cache adapter is meant to be used through the `Cache` interface,
 * which abstracts away key generation, adapter instantiation and filter
 * implementation.
 *
 * A simple configuration of this adapter can be accomplished in app/config/bootstrap.php
 * as follows:
 *
 * Cache::config(array(
 *     'cache-config-name' => array(
 *         'adapter' => 'Memcached',
 *         'servers' => array(
 *             array('127.0.0.1', 11211, 100)
 *         )
 *     )
 * ));
 *
 * The 'servers' key accepts entries as arrays, where the format is array(server, port, <weight>),
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
 * @see \lithium\storage\Cache::key()
 * @see \lithium\storage\Cache::adapter()
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
	 * @see \lithium\storage\Cache::config()
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
		return extension_loaded('memcached');
	}

	/**
	 * Write value(s) to the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @param mixed  $value      The value to be cached
	 * @param string $expiry     A strtotime() compatible cache time
	 * @param object $conditions Conditions under which the operation should proceed
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($key, $value, $expiry, $conditions = null) {
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
	 * @param object $conditions Conditions under which the operation should proceed
	 * @return mixed Cached value if successful, false otherwise
	 * @todo Refactor to use RES_NOTFOUND for return value checks
	 */
	public function read($key, $conditions = null) {
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
	 * @param object $conditions Conditions under which the operation should proceed
	 * @return mixed True on successful delete, false otherwise
	 */
	public function delete($key, $conditions = null) {
		$Memcached =& static::$_Memcached;

		return function($self, $params, $chain) use (&$Memcached) {
			extract($params);
			$Memcached->delete($key . '_expires');
			return $Memcached->delete($key);
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
}

?>