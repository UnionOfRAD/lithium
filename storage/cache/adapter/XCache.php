<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

use Closure;
use lithium\storage\Cache;

/**
 * An XCache opcode cache adapter implementation.
 *
 * The XCache adapter is meant to be used through the `Cache` interface,
 * which abstracts away key generation, adapter instantiation and filter
 * implementation.
 *
 * A simple configuration of this adapter can be accomplished in `config/bootstrap/cache.php`
 * as follows:
 *
 * {{{
 * use lithium\storage\Cache;
 *
 * Cache::config(array(
 *     'cache-config-name' => array(
 *         'adapter' => 'XCache',
 *         'username' => 'user',
 *         'password' => 'pass'
 *     )
 * ));
 * }}}
 *
 * Note that the `username` and `password` configuration fields are only required if
 * you wish to use `XCache::clear()` - all other methods do not require XCache administrator
 * credentials.
 *
 * This XCache adapter provides basic support for `write`, `read`, `delete`
 * and `clear` cache functionality, as well as allowing the first four
 * methods to be filtered as per the Lithium filtering system.
 *
 * This adapter synthetically supports multi-key `write`, `read` and `delete` operations.
 *
 * @see lithium\storage\Cache::key()
 * @see lithium\storage\cache\adapter
 */
class XCache extends \lithium\storage\cache\Adapter {

	/**
	 * Class constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$defaults = array('prefix' => '', 'expiry' => '+1 hour');
		parent::__construct($config + $defaults);
	}

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
	public function write(array $keys, $expiry = null) {
		$expiry = $expiry || $expiry === Cache::PERSIST ? $expiry : $this->_config['expiry'];

		return function($self, $params) use ($expiry) {
			if (!$expiry || $expiry === Cache::PERSIST) {
				$ttl = 0;
			} elseif (is_int($expiry)) {
				$ttl = $expiry;
			} else {
				$ttl = strtotime($expiry) - time();
			}
			foreach ($params['keys'] as $key => $value) {
				if (!xcache_set($key, $value, $ttl)) {
					return false;
				}
			}
			return true;
		};
	}

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
	public function read(array $keys) {
		return function($self, $params) {
			$results = array();

			foreach ($params['keys'] as $key) {
				$result = xcache_get($key);

				if ($result === null && !xcache_isset($key)) {
					continue;
				}
				$results[$key] = $result;
			}
			return $results;
		};
	}

	/**
	 * Will attempt to remove specified keys from the user space cache.
	 *
	 * Note that this is not an atomic operation when using multiple keys.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return Closure Function returning `true` on successful delete, `false` otherwise.
	 */
	public function delete(array $keys) {
		return function($self, $params) {
			foreach ($params['keys'] as $key) {
				if (!xcache_unset($key)) {
					return false;
				}
			}
			return true;
		};
	}

	/**
	 * Performs an atomic decrement operation on specified numeric cache item.
	 *
	 * Note that, as per the XCache specification:
	 * If the item's value is not numeric, it is treated as if the value were 0.
	 * It is possible to decrement a value into the negative integers.
	 *
	 * @param string $key Key of numeric cache item to decrement
	 * @param integer $offset Offset to decrement - defaults to 1.
	 * @return Closure Function returning item's new value on successful decrement, else `false`
	 */
	public function decrement($key, $offset = 1) {
		return function($self, $params) use ($offset) {
			return xcache_dec($params['key'], $offset);
		};
	}

	/**
	 * Performs an atomic increment operation on specified numeric cache item.
	 *
	 * Note that, as per the XCache specification:
	 * If the item's value is not numeric, it is treated as if the value were 0.
	 *
	 * @param string $key Key of numeric cache item to increment
	 * @param integer $offset Offset to increment - defaults to 1.
	 * @return Closure Function returning item's new value on successful increment, else `false`
	 */
	public function increment($key, $offset = 1) {
		return function($self, $params) use ($offset) {
			extract($params);
			return xcache_inc($params['key'], $offset);
		};
	}

	/**
	 * Clears user-space cache.
	 *
	 * This method requires valid XCache admin credentials to be set when the
	 * adapter was configured, due to the use of the xcache_clear_cache admin method.
	 *
	 * If the xcache.admin.enable_auth ini setting is set to "Off", no credentials
	 * required.
	 *
	 * @return mixed True on successful clear, false otherwise.
	 */
	public function clear() {
		$admin = (ini_get('xcache.admin.enable_auth') === "On");
		if ($admin && (!isset($this->_config['username']) || !isset($this->_config['password']))) {
			return false;
		}
		$credentials = array();

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			$credentials['username'] = $_SERVER['PHP_AUTH_USER'];
			$_SERVER['PHP_AUTH_USER'] = $this->_config['username'];
		}
		if (isset($_SERVER['PHP_AUTH_PW'])) {
			$credentials['password'] = $_SERVER['PHP_AUTH_PW'];
			$_SERVER['PHP_AUTH_PW'] = $this->_config['pass'];
		}

		for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
			if (xcache_clear_cache(XC_TYPE_VAR, $i) === false) {
				return false;
			}
		}

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			$_SERVER['PHP_AUTH_USER'] = $credentials['username'];
		}
		if (isset($_SERVER['PHP_AUTH_PW'])) {
			$_SERVER['PHP_AUTH_PW'] = $credentials['password'];
		}
		return true;
	}

	/**
	 * Determines if the XCache extension has been installed and
	 * if the userspace cache is available.
	 *
	 * @return boolean True if enabled, false otherwise.
	 */
	public static function enabled() {
		return extension_loaded('xcache');
	}
}

?>