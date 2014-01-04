<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage;

/**
 * The `Cache` static class provides a consistent interface to configure and utilize the different
 * cache adapters included with Lithium, as well as your own adapters.
 *
 * The Cache layer of Lithium inherits from the common `Adaptable` class, which provides the generic
 * configuration setting & retrieval logic, as well as the logic required to locate & instantiate
 * the proper adapter class.
 *
 * In most cases, you will configure various named cache configurations in your bootstrap process,
 * which will then be available to you in all other parts of your application.
 *
 * A simple example configuration:
 *
 * {{{Cache::config(array(
 *     'local' => array('adapter' => 'Apc'),
 *     'distributed' => array(
 *         'adapter' => 'Memcached',
 *         'host' => '127.0.0.1:11211',
 *     ),
 *     'default' => array('adapter' => 'File')
 * ));}}}
 *
 * Each adapter provides a consistent interface for the basic cache operations of `write`, `read`,
 * `delete` and `clear`, which can be used interchangeably between all adapters. Some adapters
 * may provide additional methods that are not consistently available across other adapters.
 * To make use of these, it is always possible to call:
 *
 * {{{Cache::adapter('named-configuration')->methodName($argument);}}}
 *
 * This allows a very wide range of flexibility, at the cost of portability.
 *
 * Some cache adapters (e.g. `File`) do _not_ provide the functionality for increment/decrement.
 *
 * @see lithium\core\Adaptable
 * @see lithium\storage\cache\adapter
 */
class Cache extends \lithium\core\Adaptable {

	/**
	 * Stores configurations for cache adapters
	 *
	 * @var array
	 */
	protected static $_configurations = array();

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.storage.cache';

	/**
	 * Libraries::locate() compatible path to strategies for this class.
	 *
	 * @var string Dot-delimited path.
	 */
	protected static $_strategies = 'strategy.storage.cache';

	/**
	 * Generates the cache key.
	 *
	 * @param mixed $key A string (or lambda/closure that evaluates to a string)
	 *                    that will be used as the cache key.
	 * @param array $data If a lambda/closure is used as a key and requires arguments,
	 *                    pass them in here.
	 * @return string The generated cache key.
	 */
	public static function key($key, $data = array()) {
		return is_object($key) ? $key($data) : $key;
	}

	/**
	 * Writes to the specified cache configuration.
	 *
	 * Can handle single- and multi-key writes.
	 *
	 * This method has two valid syntaxes depending on if you're storing
	 * data using a single key or multiple keys as outlined below.
	 * {{{
	 * // To write data to a single-key use the following syntax.
	 * Cache::write('default', 'foo', 'bar', '+1 minute');
	 *
	 * // For multi-key writes the $data parameter's role becomes
	 * // the one of the $expiry parameter.
	 * Cache::write('default', array('foo' => 'bar', ... ), '+1 minute');
	 * }}}
	 *
	 * @param string $name Configuration to be used for writing.
	 * @param mixed $key Key to uniquely identify the cache entry or an array of key/value pairs
	 *                   for multi-key writes mapping cache keys to the data to be cached.
	 * @param mixed $data Data to be cached.
	 * @param string $expiry A `strtotime()` compatible cache time.
	 * @param mixed $options Options for the method, filters and strategies.
	 * @return boolean `true` on successful cache write, `false` otherwise. When writing
	 *                 multiple items and an error occurs writing any of the items the
	 *                 whole operation fails and this method will return `false`.
	 * @filter This method may be filtered.
	 */
	public static function write($name, $key, $data = null, $expiry = null, array $options = array()) {
		$options += array('conditions' => null, 'strategies' => true);
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}
		if (is_callable($options['conditions']) && !$options['conditions']()) {
			return false;
		}
		$key = static::key($key, $data);

		if ($isMulti = is_array($key)) {
			$keys = $key;
			$expiry = $data;
		} else {
			$keys = array($key => $data);
		}

		if ($options['strategies']) {
			foreach ($keys as $key => &$value) {
				$value = static::applyStrategies(__FUNCTION__, $name, $value, array(
					'key' => $key, 'class' => __CLASS__
				));
			}
		}
		$method = static::adapter($name)->write($keys, $expiry);
		$params = compact('keys', 'expiry');
		return static::_filter(__FUNCTION__, $params, $method, $settings[$name]['filters']);
	}

	/**
	 * Reads from the specified cache configuration.
	 *
	 * Can handle single- and multi-key reads.
	 *
	 * @param string $name Configuration to be used for reading.
	 * @param mixed $key Key to uniquely identify the cache entry or an array of keys
	 *                   for multikey-reads.
	 * @param mixed $options Options for the method and strategies.
	 * @return mixed For single-key reads will return the result if the cache
	 *               key has been found otherwise returns `null`. When reading
	 *               multiple keys a results array is returned mapping keys to
	 *               retrieved values. Keys where the value couldn't successfully
	 *               been read will not be contained in the results array.
	 * @filter This method may be filtered.
	 */
	public static function read($name, $key, array $options = array()) {
		$options += array('conditions' => null, 'strategies' => true, 'write' => null);
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}
		if (is_callable($options['conditions']) && !$options['conditions']()) {
			return false;
		}
		$key = static::key($key);

		if ($isMulti = is_array($key)) {
			$keys = $key;
		} else {
			$keys = array($key);
		}

		$method = static::adapter($name)->read($keys);
		$params = compact('keys');
		$filters = $settings[$name]['filters'];
		$results = static::_filter(__FUNCTION__, $params, $method, $filters);

		foreach ($params['keys'] as $key) {
			if (!isset($results[$key]) && ($write = $options['write'])) {
				$write = is_callable($write) ? $write() : $write;
				list($expiry, $value) = each($write);
				$value = is_callable($value) ? $value() : $value;

				if (static::write($name, $key, $value, $expiry)) {
					$results[$key] = $value;
				}
			}
		}
		unset($result);

		if ($options['strategies']) {
			foreach ($results as $key => &$result) {
				$result = static::applyStrategies(__FUNCTION__, $name, $result, array(
					'key' => $key, 'mode' => 'LIFO', 'class' => __CLASS__
				));
			}
		}
		return $isMulti ? $results : ($results ? reset($results) : null);
	}

	/**
	 * Deletes using the specified cache configuration.
	 *
	 * Can handle single- and multi-key deletes.
	 *
	 * @param string $name The cache configuration to delete from.
	 * @param mixed $key Key to be deleted or an array of keys to delete.
	 * @param mixed $options Options for the method and strategies.
	 * @return boolean `true` on successful cache delete, `false` otherwise. When deleting
	 *                 multiple items and an error occurs deleting any of the items the
	 *                 whole operation fails and this method will return `false`.
	 * @filter This method may be filtered.
	 * @fixme Support for delete strategies should be removed in future
	 *        versions as cache strategies don't make any use of them and
	 *        the lack of use cases for manipulating the cache key on delete
	 *        can be doubted.
	 */
	public static function delete($name, $key, array $options = array()) {
		$options += array('conditions' => null, 'strategies' => true);
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}
		if (is_callable($options['conditions']) && !$options['conditions']()) {
			return false;
		}

		$key = static::key($key);

		if ($isMulti = is_array($key)) {
			$keys = $key;
		} else {
			$keys = array($key);
		}
		$method = static::adapter($name)->delete($keys);
		$filters = $settings[$name]['filters'];

		if ($options['strategies']) {
			foreach ($keys as &$key) {
				$key = static::applyStrategies(__FUNCTION__, $name, $key, array(
					'key' => $key, 'class' => __CLASS__
				));
			}
		}
		return static::_filter(__FUNCTION__, compact('keys'), $method, $filters);
	}

	/**
	 * Performs an atomic increment operation on specified numeric cache item
	 * from the given cache configuration.
	 *
	 * @param string $name
	 * @param string $key Key of numeric cache item to increment
	 * @param integer $offset Offset to increment - defaults to 1.
	 * @param mixed $options Options for this method.
	 * @return mixed Item's new value on successful increment, false otherwise.
	 * @filter This method may be filtered.
	 */
	public static function increment($name, $key, $offset = 1, array $options = array()) {
		$options += array('conditions' => null);
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}
		$conditions = $options['conditions'];

		if (is_callable($conditions) && !$conditions()) {
			return false;
		}

		$key = static::key($key);
		$method = static::adapter($name)->increment($key, $offset);
		$params = compact('key', 'offset');
		$filters = $settings[$name]['filters'];

		return static::_filter(__FUNCTION__, $params, $method, $filters);
	}

	/**
	 * Performs an atomic decrement operation on specified numeric cache item
	 * from the given cache configuration.
	 *
	 * @param string $name
	 * @param string $key Key of numeric cache item to decrement
	 * @param integer $offset Offset to decrement - defaults to 1.
	 * @param mixed $options Options for this method.
	 * @return mixed Item's new value on successful decrement, false otherwise.
	 * @filter This method may be filtered.
	 */
	public static function decrement($name, $key, $offset = 1, array $options = array()) {
		$options += array('conditions' => null);
		$settings = static::config();

		if (!isset($settings[$name])) {
			return false;
		}
		$conditions = $options['conditions'];

		if (is_callable($conditions) && !$conditions()) {
			return false;
		}

		$key = static::key($key);
		$method = static::adapter($name)->decrement($key, $offset);
		$params = compact('key', 'offset');
		$filters = $settings[$name]['filters'];

		return static::_filter(__FUNCTION__, $params, $method, $filters);
	}

	/**
	 * Perform garbage collection on specified cache configuration.
	 *
	 * This method is not filterable.
	 *
	 * @param string $name The cache configuration to be cleaned
	 * @return boolean True on successful clean, false otherwise
	 */
	public static function clean($name) {
		$settings = static::config();
		return (isset($settings[$name])) ? static::adapter($name)->clean() : false;
	}

	/**
	 * Remove all cache keys from specified configuration.
	 *
	 * This method is non-filterable.
	 *
	 * @param string $name The cache configuration to be cleared
	 * @return boolean True on successful clearing, false otherwise
	 */
	public static function clear($name) {
		$settings = static::config();
		return (isset($settings[$name])) ? static::adapter($name)->clear() : false;
	}
}

?>