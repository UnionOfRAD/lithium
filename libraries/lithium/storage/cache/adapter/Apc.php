<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

/**
 * An Alternative PHP Cache (APC) cache adapter implementation.
 *
 * The APC cache adapter is meant to be used through the `Cache` interface,
 * which abstracts away key generation, adapter instantiation and filter
 * implementation.
 *
 * This APC adapter provides basic support for `write`, `read`, `delete`
 * and `clear` cache functionality, as well as allowing the first four
 * methods to be filtered as per the Lithium filtering system.
 *
 * This adapter stores two keys for each written value - one which consists
 * of the data to be cached, and the other being a cache of the expiration time.
 * This is to unify the behavior of the APC adapter to be in line with the other
 * adapters, since APC cache expirations are only evaluated on requests subsequent
 * to their initial storage.
 *
 * Learn more about APC in the [PHP APC manual](http://php.net/manual/en/book.apc.php).
 *
 * @see lithium\storage\Cache::key()
 */
class Apc extends \lithium\core\Object {

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('prefix' => '');
		parent::__construct($config + $defaults);
	}

	/**
	 * Write value(s) to the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @param mixed  $value      The value to be cached
	 * @param string $expiry     A strtotime() compatible cache time
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($key, $data, $expiry) {
		return function($self, $params, $chain) {
			extract($params);
			$cachetime = strtotime($expiry);
			$duration = $cachetime - time();

			apc_store($key . '_expires', $cachetime, $duration);
			return apc_store($key, $data, $cachetime);

		};
	}

	/**
	 * Read value(s) from the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @return mixed Cached value if successful, false otherwise
	 */
	public function read($key) {
		return function($self, $params, $chain) {
			extract($params);
			$cachetime = intval(apc_fetch($key . '_expires'));
			$time = time();
			return ($cachetime < $time) ? false : apc_fetch($key);
		};
	}

	/**
	 * Delete value from the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @return mixed True on successful delete, false otherwise
	 */
	public function delete($key) {
		return function($self, $params, $chain) {
			extract($params);
			apc_delete($key . '_expires');
			return apc_delete($key);
		};
	}

	/**
	 * Clears user-space cache
	 *
	 * @return mixed True on successful clear, false otherwise
	 */
	public function clear() {
		return apc_clear_cache('user');
	}

	/**
	 * Determines if the APC extension has been installed and
	 * if the userspace cache is available.
	 *
	 * return boolean True if enabled, false otherwise
	 */
	public function enabled() {
		return (extension_loaded('apc') && apc_cache_info('user'));
	}
}

?>