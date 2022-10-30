<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage;

use lithium\aop\Filters;
use lithium\core\ConfigException;
use lithium\security\Hash;

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
 * ```
 * Cache::config([
 *     'local' => [
 *         'adapter' => 'Apc'
 *     ],
 *     'distributed' => [
 *         'adapter' => 'Memcached',
 *         'host' => '127.0.0.1:11211'
 *     ],
 *     'default' => [
 *         'adapter' => 'File',
 *         'strategies => ['Serializer']
 *     ]
 * ];
 * ```
 *
 * Adapter configurations can be scoped, adapters will then handle the
 * namespacing of the keys transparently for you:
 *
 * ```
 * Cache::config([
 *     'primary'   => ['adapter' => 'Apc', 'scope' => 'primary'],
 *     'secondary' => ['adapter' => 'Apc', 'scope' => 'secondary']
 * ];
 * ```
 *
 * Cache adapters differ in the functionality they provide and how the provide it. To see
 * if an adapter meets your requirement and for more information on the specifics
 * (i.e. atomicity of operations), consult the documentation the adapter first.
 *
 * All adapters will provide `write`, `read`, `delete` and `increment`/`decrement` functionality. On
 * top of that adapters may provide `clean` and `clear` functionality as well as direct access to
 * additional methods. Which allows for a very wide range of flexibility at the cost of portability.
 *
 * ```
 * Cache::adapter('default')->methodName($argument);
 * ```
 *
 * @see lithium\core\Adaptable
 * @see lithium\storage\cache\Adapter
 * @see lithium\storage\cache\adapter
 */
class Cache extends \lithium\core\Adaptable {

	/**
	 * Can be used for expiry parameters or configuration options to
	 * specify that a cached item should persist as long and expire as
	 * late as possible.
	 */
	const PERSIST = 0;

	/**
	 * Stores configurations for cache adapters.
	 *
	 * @var array
	 */
	protected static $_configurations = [];

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
	 * Generates one or multiple safe cache keys optionally adding a suffix
	 * with a hash over the provided data. The hash value is generated in
	 * an optimized way dependending on the type of data.
	 *
	 * Simple usage (in this case noop):
	 * ```
	 * Cache::key('default', 'post');
	 * // returns `'post'`
	 *
	 * Cache::key('default', ['posts', 'banners']);
	 * // returns `['posts', 'banners']`
	 *
	 * Cache::key('default', ['posts' => 'foo', 'banners' => 'bar]);
	 * // returns `['posts' => 'foo', 'banners' => 'bar']`
	 * ```
	 *
	 * Make a key safe to use with adapter (exact result depends
	 * on key constraints enforced by the selected adapter:
	 * ```
	 * Cache::key('default', 'posts for Helgi Þorbjörnsson');
	 * // returns `'posts_for_Helgi__orbj_rnsson_c7f8433a'`
	 * ```
	 *
	 * Using additional scalar or non-scalar data to generate key:
	 * ```
	 * Cache::key('default', 'post', 2);
	 * // returns `'post:1ad5be0d'`
	 *
	 * Cache::key('default', 'post', [2, 'json']);
	 * // returns `'post:723f0e19'`
	 *
	 * Cache::key('default', ['posts', 'banners'], 'json');
	 * // returns `['posts:6b072545', 'banners:6b072545']`
	 *
	 * Cache::key('default', ['posts' => 'foo', 'banners' => 'bar'], 'json');
	 * // returns `['posts:38ec40e5' => 'foo', 'banners:38ec40e5' => 'bar']`
	 * ```
	 *
	 * Or with a resuable key generator function:
	 * ```
	 * $posts[0] = ['id' => 1];
	 * $posts[1] = ['id' => 2];
	 *
	 * $key = function($data) { return 'post:' . $data['id']};
	 *
	 * Cache::key('default', $key, $post[0]); // returns `'post:1'`
	 * Cache::key('default', $key, $post[1]); // returns `'post:2'`
	 * ```
	 *
	 * This example shows a key mutating generator function:
	 * ```
	 * $base = 'post';
	 * $key  = function($id) use (&base) { return $base .= ":{$id}"; };
	 *
	 * Cache::key('default', $key, 1); // returns `'post:1'`
	 * Cache::key('default', $key, 2); // returns `'post:1:2'`
	 * ```
	 *
	 * @param string $name Configuration to be used for generating key/s.
	 * @param mixed $key String or an array of strings that will be used as the cache key/s.
	 *              Also accepts associative arrays where the key part will be modified, but
	 *              the value left untouched. Also accepts a key generator function that
	 *              is passed $data and must return a string that will be used as the key.
	 * @param mixed $data Additional data to use when generating key. Can be any kind of type except
	 *              a resource. The method will calculate a hash of the data and append that to
	 *              the key/s. When $key is a function the data is passed to it instead.
	 * @return string|array The generated cache key/s.
	 */
	public static function key($name, $key, $data = null) {
		$adapter = static::adapter($name);

		if (is_callable($key)) {
			return current($adapter->key([$key($data)]));
		}
		$keys = ($isMulti = is_array($key)) ? $key : [$key];
		$keys = ($hasData = !is_integer(key($keys))) ? array_keys($keys) : $keys;

		if ($data !== null) {
			$data = Hash::calculate($data, ['type' => 'crc32b']);
			$keys = array_map(function($key) use ($data) { return $key .= ":{$data}"; }, $keys);
		}
		$keys = $adapter->key($keys);
		$keys = $hasData ? array_combine($keys, array_values((array) $key)) : $keys;

		return $isMulti ? $keys : current($keys);
	}

	/**
	 * Writes to the specified cache configuration.
	 *
	 * Can handle single- and multi-key writes.
	 *
	 * This method has two valid syntaxes depending on if you're storing
	 * data using a single key or multiple keys as outlined below.
	 * ```
	 * // To write data to a single-key use the following syntax.
	 * Cache::write('default', 'foo', 'bar', '+1 minute');
	 *
	 * // For multi-key writes the $data parameter's role becomes
	 * // the one of the $expiry parameter.
	 * Cache::write('default', ['foo' => 'bar', ... ], '+1 minute');
	 * ```
	 *
	 * These two calls are synonymical and demonstrate the two
	 * possible ways to specify the expiration time.
	 * ```
	 * Cache::write('default', 'foo', 'bar', '+1 minute');
	 * Cache::write('default', 'foo', 'bar', 60);
	 * ```
	 *
	 * @param string $name Configuration to be used for writing.
	 * @param mixed $key Key to uniquely identify the cache entry or an array of key/value pairs
	 *                   for multi-key writes mapping cache keys to the data to be cached.
	 * @param mixed $data Data to be cached.
	 * @param string|integer $expiry A `strtotime()` compatible cache time. Alternatively an integer
	 *                       denoting the seconds until the item expires (TTL). If no expiry time is
	 *                       set, then the default cache expiration time set with the cache adapter
	 *                       configuration will be used. To persist an item use `Cache::PERSIST`.
	 * @param array $options Options for the method and strategies.
	 *              - `'strategies'` _boolean_: Indicates if strategies should be used,
	 *                 defaults to `true`.
	 *              - `'conditions'` _mixed_: A function or item that must return or
	 *                evaluate to `true` in order to continue write operation.
	 * @return boolean `true` on successful cache write, `false` otherwise. When writing
	 *                 multiple items and an error occurs writing any of the items the
	 *                 whole operation fails and this method will return `false`.
	 * @filter
	 */
	public static function write($name, $key, $data = null, $expiry = null, array $options = []) {
		$options += ['conditions' => null, 'strategies' => true];

		if (is_callable($options['conditions']) && !$options['conditions']()) {
			return false;
		}
		$adapter = static::adapter($name);

		if (is_array($key)) {
			$keys = $key;
			$expiry = $data;
		} else {
			$keys = [$key => $data];
		}

		if ($options['strategies']) {
			foreach ($keys as $key => &$value) {
				$value = static::applyStrategies(__FUNCTION__, $name, $value, [
					'key' => $key, 'class' => __CLASS__
				]);
			}
		}
		$params = compact('keys', 'expiry');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) use ($adapter) {
			return $adapter->write($params['keys'], $params['expiry']);
		});
	}

	/**
	 * Reads from the specified cache configuration.
	 *
	 * Can handle single- and multi-key reads.
	 *
	 * Read-through caching can be used by passing expiry and the to-be-cached value
	 * in the `write` option. Following three ways to achieve this.
	 * ```
	 * Cache::read('default', 'foo', [
	 *	'write' => ['+5 days' => 'bar']
	 * ]); // returns `'bar'`
	 *
	 * Cache::read('default', 'foo', [
	 *	'write' => ['+5 days' => function() { return 'bar'; }]
	 * ]);
	 *
	 * Cache::read('default', 'foo', [
	 *	'write' => function() { return ['+5 days' => 'bar']; }
	 * ]);
	 * ```
	 *
	 * @param string $name Configuration to be used for reading.
	 * @param mixed $key Key to uniquely identify the cache entry or an array of keys
	 *                   for multikey-reads.
	 * @param array $options Options for the method and strategies.
	 *              - `'write'`: Allows for read-through caching see description for usage.
	 *              - `'strategies'` _boolean_: Indicates if strategies should be used,
	 *                 defaults to `true`.
	 *              - `'conditions'` _mixed_: A function or item that must return or
	 *                evaluate to `true` in order to continue write operation.
	 * @return mixed For single-key reads will return the result if the cache
	 *               key has been found otherwise returns `null`. When reading
	 *               multiple keys a results array is returned mapping keys to
	 *               retrieved values. Keys where the value couldn't successfully
	 *               been read will not be contained in the results array.
	 * @filter
	 */
	public static function read($name, $key, array $options = []) {
		$options += ['conditions' => null, 'strategies' => true, 'write' => null];

		if (is_callable($options['conditions']) && !$options['conditions']()) {
			return false;
		}
		$adapter = static::adapter($name);

		if ($isMulti = is_array($key)) {
			$keys = $key;
		} else {
			$keys = [$key];
		}
		$params = compact('keys');

		$results = Filters::run(get_called_class(), __FUNCTION__, $params, function($params) use ($adapter) {
			return $adapter->read($params['keys']);
		});

		if ($write = $options['write']) {
			$isEvaluated = false;

			foreach ($keys as $key) {
				if (isset($results[$key])) {
					continue;
				}
				if (!$isEvaluated) {
					$write = is_callable($write) ? $write() : $write;
					$expiry = key($write);
					$value = current($write);
					$value = is_callable($value) ? $value() : $value;

					$isEvaluated = true;
				}
				if (!static::write($name, $key, $value, $expiry)) {
					return false;
				}
				$results[$key] = static::applyStrategies('write', $name, $value, [
					'key' => $key, 'mode' => 'LIFO', 'class' => __CLASS__
				]);
			}
		}

		if ($options['strategies']) {
			foreach ($results as $key => &$result) {
				$result = static::applyStrategies(__FUNCTION__, $name, $result, [
					'key' => $key, 'mode' => 'LIFO', 'class' => __CLASS__
				]);
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
	 * @param array $options Options for the method and strategies.
	 *              - `'conditions'` _mixed_: A function or item that must return or
	 *                evaluate to `true` in order to continue write operation.
	 * @return boolean `true` on successful cache delete, `false` otherwise. When deleting
	 *                 multiple items and an error occurs deleting any of the items the
	 *                 whole operation fails and this method will return `false`.
	 * @filter
	 */
	public static function delete($name, $key, array $options = []) {
		$options += ['conditions' => null, 'strategies' => true];

		if (is_callable($options['conditions']) && !$options['conditions']()) {
			return false;
		}
		$adapter = static::adapter($name);

		if (is_array($key)) {
			$keys = $key;
		} else {
			$keys = [$key];
		}
		$params = compact('keys');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) use ($adapter) {
			return $adapter->delete($params['keys']);
		});
	}

	/**
	 * Performs a increment operation on specified numeric cache item
	 * from the given cache configuration.
	 *
	 * @param string $name Name of the cache configuration to use.
	 * @param string $key Key of numeric cache item to increment
	 * @param integer $offset Offset to increment - defaults to 1.
	 * @param array $options Options for this method.
	 *              - `'conditions'`: A function or item that must return or evaluate to
	 *                                `true` in order to continue operation.
	 * @return integer|boolean Item's new value on successful increment, false otherwise.
	 * @filter
	 */
	public static function increment($name, $key, $offset = 1, array $options = []) {
		$options += ['conditions' => null];

		if (is_callable($options['conditions']) && !$options['conditions']()) {
			return false;
		}
		$adapter = static::adapter($name);
		$params = compact('key', 'offset');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) use ($adapter) {
			return $adapter->increment($params['key'], $params['offset']);
		});
	}

	/**
	 * Performs a decrement operation on specified numeric cache item
	 * from the given cache configuration.
	 *
	 * @param string $name Name of the cache configuration to use.
	 * @param string $key Key of numeric cache item to decrement
	 * @param integer $offset Offset to decrement - defaults to 1.
	 * @param array $options Options for this method.
	 *              - `'conditions'`: A function or item that must return or evaluate to
	 *                                `true` in order to continue operation.
	 * @return integer|boolean Item's new value on successful decrement, false otherwise.
	 * @filter
	 */
	public static function decrement($name, $key, $offset = 1, array $options = []) {
		$options += ['conditions' => null];

		if (is_callable($options['conditions']) && !$options['conditions']()) {
			return false;
		}
		$adapter = static::adapter($name);
		$params = compact('key', 'offset');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) use ($adapter) {
			return $adapter->decrement($params['key'], $params['offset']);
		});
	}

	/**
	 * Perform garbage collection on specified cache configuration. All invalidated cache
	 * keys - *without* honoring a configured scope - from the specified configuration are
	 * removed.
	 *
	 * @param string $name The cache configuration to be cleaned.
	 * @return boolean `true` on successful cleaning, `false` if failed partially or entirely.
	 */
	public static function clean($name) {
		return static::adapter($name)->clean();
	}

	/**
	 * Clears entire cache by flushing it. All cache keys - *without* honoring
	 * a configured scope - from the specified configuration are removed.
	 *
	 * @param string $name The cache configuration to be cleared.
	 * @return boolean `true` on successful clearing, `false` if failed partially or entirely.
	 */
	public static function clear($name) {
		return static::adapter($name)->clear();
	}
}

?>