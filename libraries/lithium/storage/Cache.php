<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage;

use \lithium\util\Inflector;

/**
 * Maintains global cache configurations for the application.
 *
 * @todo Perhaps re-implement using stream wrappers, and stream filters for strategies.
 */
class Cache extends \lithium\core\Adaptable {


	/**
	 * Stores configurations for cache adapters
	 *
	 * @var object Collection of cache configurations
	 */
	protected static $_configurations = null;

	/**
	 * Generates the cache key.
	 *
	 * @param mixed $key  A string (or lambda/closure that evaluates to a string)
	 *                    that will be used as the cache key.
	 * @param array $data If a lambda/closure is used as a key and requires arguments,
	 *                    pass them in here.
     * @return string     The generated cache key.
	 */
	public static function key($key, $data = array()) {
		$key = is_object($key) ? $key($data) : $key;
		return Inflector::slug($key);
	}

	/**
	 * Writes to the specified cache configuration.
	 *
	 * @param  string  $name       Configuration to be used for writing
	 * @param  mixed   $key        Key to uniquely identify the cache entry
	 * @param  mixed   $data       Data to be cached
	 * @param  mixed   $conditions Conditions for the write operation to proceed
	 * @return boolean             True on successful cache write, false otherwise
	 * @strategy
	 */
	public static function write($name, $key, $data, $expiry, $conditions = null) {
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}

		$key = static::key($key);
		$methods = array($name => static::adapter($name)->write($key, $data, $expiry, $conditions));
		$result = false;

		foreach ($methods as $name => $method) {
			$params = compact('key', 'data', 'expiry', 'conditions');
			$filters = $settings[$name]['filters'];
			$result = $result || static::_filter('write', $params, $method, $filters);
		}
		return $result;
	}


	/**
	 * Reads from the specified cache configuration
	 *
	 * @param  string  $name       Configuration to be used for reading
	 * @param  mixed   $key        Key to be retrieved
	 * @param  mixed   $conditions Conditions for the read operation to proceed
	 * @return mixed               Read results on successful cache read, null otherwise
	 */
	public static function read($name, $key, $conditions = null) {
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}

		$key = static::key($key);
		$methods = array($name => static::adapter($name)->read($key, $conditions));
		$result = false;

		foreach ($methods as $name => $method) {
			$params = compact('key', 'conditions');
			$filters = $settings[$name]['filters'];
			$result = $result || static::_filter('read', $params, $method, $filters);
		}
		return $result;
	}

	/**
	 * Delete a value from the specified cache configuration
	 *
	 * @param  string  $name       The cache configuration to delete from
	 * @param  mixed   $key        Key to be deleted
	 * @param  mixed   $conditions Conditions for the delete operation to proceed
	 * @return boolean             True on successful deletion, false otherwise
	 */
	public static function delete($name, $key, $conditions = null) {
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}

		$key = static::key($key);
		$methods = array($name => static::adapter($name)->delete($key, $conditions));
		$result = false;

		foreach ($methods as $name => $method) {
			$params = compact('key', 'conditions');
			$filters = $settings[$name]['filters'];
			$result = $result || static::_filter('delete', $params, $method, $filters);
		}
		return $result;
	}

	/**
	 * Perform garbage collection on specified cache configuration.
	 *
	 * @param  string  $name The cache configuration to be cleaned
	 * @return boolean       True on successful clean, false otherwise
	 */
	public static function clean($name) {
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}

		return static::adapter($name)->clean();
	}

	/**
	 * Remove all cache keys from specified confiuration.
	 *
	 * @param  string  $name The cache configuration to be cleared
	 * @return boolean       True on successful clearing, false otherwise
	 */
	public static function clear($name) {
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}

		return static::adapter($name)->clear();
	}

	/**
	 * Returns adapter for given named configuration
	 *
	 * @param  string $name Name of configuration
	 * @return object       Adapter for named configuration
	 */
	public static function adapter($name) {
		return static::_adapter('adapters.storage.cache', $name);
	}
}

?>