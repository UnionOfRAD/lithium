<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

use DirectoryIterator;
use lithium\core\Libraries;
use lithium\storage\Cache;

/**
 * A minimal file-based cache.
 *
 * The File adapter is a very simple cache, and should only be used for prototyping
 * or for specifically caching _files_. For more general caching needs, please consider
 * using a more appropriate cache adapter.
 *
 * This adapter has no external dependencies. Operations in read/write/delete are atomic
 * for single-keys only. Clearing the cache is supported. Real persistence of cached items
 * is provided. Increment/decrement functionality is provided but only in a non-atomic way.
 *
 * This can't handle serialization natively. Scope support is available but not natively.
 *
 * A simple configuration can be accomplished as follows:
 *
 * ```
 * Cache::config(array(
 *     'default' => array(
 *         'adapter' => 'File',
 *         'strategies => array('Serializer')
 *      )
 * ));
 * ```
 *
 * The path that the cached files will be written to defaults to
 * `<app>/resources/tmp/cache`, but is user-configurable.
 *
 * Note that the cache expiration time is stored within the first few bytes
 * of the cached data, and is transparently added and/or removed when values
 * are stored and/or retrieved from the cache.
 *
 * @see lithium\storage\cache\adapter
 */
class File extends \lithium\storage\cache\Adapter {

	/**
	 * The maximum line length of the file header storing meta data.
	 *
	 * @var integer
	 */
	const MAX_HEADER_LENGTH = 500;

	/**
	 * Constructor.
	 *
	 * @see lithium\storage\Cache::config()
	 * @param array $config Configuration for this cache adapter. These settings are queryable
	 *        through `Cache::config('name')`. The available options are as follows:
	 *        - `'scope'` _string_: Scope which will prefix keys; per default not set.
	 *        - `'expiry'` _mixed_: The default expiration time for cache values, if no value
	 *          is otherwise set. Can be either a `strtotime()` compatible tring or TTL in
	 *          seconds. To indicate items should not expire use `Cache::PERSIST`. Defaults
	 *          to `+1 hour`.
	 *        - `'path'` _string_: Path where cached entries live, defaults to
	 *          `Libraries::get(true, 'resources') . '/tmp/cache'`.
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'path' => Libraries::get(true, 'resources') . '/tmp/cache',
			'scope' => null,
			'expiry' => '+1 hour'
		);
		parent::__construct($config + $defaults);
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
			$expires = 0;
		} elseif (is_int($expiry)) {
			$expires = $expiry + time();
		} else {
			$expires = strtotime($expiry);
		}
		if ($this->_config['scope']) {
			$keys = $this->_addScopePrefix($this->_config['scope'], $keys, '_');
		}
		foreach ($keys as $key => $value) {
			if (!$this->_write($key, $value, $expires)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Read values from the cache. Will attempt to return an array of data
	 * containing key/value pairs of the requested data.
	 *
	 * Invalidates and cleans up expired items on-the-fly when found.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return array Cached values keyed by cache keys on successful read,
	 *               keys which could not be read will not be included in
	 *               the results array.
	 */
	public function read(array $keys) {
		if ($this->_config['scope']) {
			$keys = $this->_addScopePrefix($this->_config['scope'], $keys, '_');
		}
		$results = array();

		foreach ($keys as $key) {
			if (!$item = $this->_read($key)) {
				continue;
			}
			if ($item['expiry'] < time() && $item['expiry'] != 0) {
				$this->_delete($key);
				continue;
			}
			$results[$key] = $item['value'];
		}
		if ($this->_config['scope']) {
			$results = $this->_removeScopePrefix($this->_config['scope'], $results, '_');
		}
		return $results;
	}

	/**
	 * Will attempt to remove specified keys from the user space cache.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return boolean `true` on successful delete, `false` otherwise.
	 */
	public function delete(array $keys) {
		if ($this->_config['scope']) {
			$keys = $this->_addScopePrefix($this->_config['scope'], $keys, '_');
		}
		foreach ($keys as $key) {
			if (!$this->_delete($key)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Performs a decrement operation on a specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to decrement.
	 * @param integer $offset Offset to decrement - defaults to `1`.
	 * @return integer|boolean The item's new value on successful decrement, else `false`.
	 */
	public function decrement($key, $offset = 1) {
		if ($this->_config['scope']) {
			$key = "{$this->_config['scope']}_{$key}";
		}
		if (!$result = $this->_read($key)) {
			return false;
		}
		if (!$this->_write($key, $result['value'] -= $offset, $result['expiry'])) {
			return false;
		}
		return $result['value'];
	}

	/**
	 * Performs an increment operation on a specified numeric cache item.
	 *
	 * @param string $key Key of numeric cache item to increment
	 * @param integer $offset Offset to increment - defaults to `1`.
	 * @return integer|boolean The item's new value on successful increment, else `false`.
	 */
	public function increment($key, $offset = 1) {
		if ($this->_config['scope']) {
			$key = "{$this->_config['scope']}_{$key}";
		}
		if (!$result = $this->_read($key)) {
			return false;
		}
		if (!$this->_write($key, $result['value'] += $offset, $result['expiry'])) {
			return false;
		}
		return $result['value'];
	}

	/**
	 * Clears entire cache by flushing it. Please note
	 * that a scope - in case one is set - is *not* honored.
	 *
	 * The operation will continue to remove keys even if removing
	 * one single key fails, clearing thoroughly as possible.
	 *
	 * @return boolean `true` on successful clearing, `false` if failed partially or entirely.
	 */
	public function clear() {
		$result = true;
		foreach (new DirectoryIterator($this->_config['path']) as $file) {
			if (!$file->isFile()) {
				continue;
			}
			$result = $this->_delete($file->getBasename()) && $result;
		}
		return $result;
	}

	/**
	 * Cleans entire cache running garbage collection on it. Please
	 * note that a scope - in case one is set - is *not* honored.
	 *
	 * The operation will continue to remove keys even if removing
	 * one single key fails, cleaning thoroughly as possible.
	 *
	 * @return boolean `true` on successful cleaning, `false` if failed partially or entirely.
	 */
	public function clean() {
		$result = true;
		foreach (new DirectoryIterator($this->_config['path']) as $file) {
			if (!$file->isFile()) {
				continue;
			}
			if (!$item = $this->_read($key = $file->getBasename())) {
				continue;
			}
			if ($item['expiry'] > time()) {
				continue;
			}
			$result = $this->_delete($key) && $result;
		}
		return $result;
	}

	/**
	 * Compiles value to format and writes file.
	 *
	 * @see lithium\storage\cache\adapter\File::write()
	 * @param string $key Key to uniquely identify the cached item.
	 * @param mixed $value Value or resource with value to store under given key.
	 * @param integer $expires UNIX timestamp after which the item is invalid.
	 * @return boolean `true` on success, `false` otherwise.
	 */
	protected function _write($key, $value, $expires) {
		$path = "{$this->_config['path']}/{$key}";

		if (!$stream = fopen($path, 'wb')) {
			return false;
		}
		fwrite($stream, "{:expiry:{$expires}}\n");

		if (is_resource($value)) {
			stream_copy_to_stream($value, $stream);
		} else {
			fwrite($stream, $value);
		}
		return fclose($stream);
	}

	/**
	 * Reads from file, parses its format and returns its expiry and value.
	 *
	 * @see lithium\storage\cache\adapter\File::read()
	 * @param string $key Key to uniquely identify the cached item.
	 * @return array|boolean Array with `expiry` and `value` or `false` otherwise.
	 */
	protected function _read($key) {
		$path = "{$this->_config['path']}/{$key}";

		if (!is_file($path) || !is_readable($path)) {
			return false;
		}
		if (!$stream = fopen($path, 'rb')) {
			return false;
		}
		$header = stream_get_line($stream, static::MAX_HEADER_LENGTH, "\n");

		if (!preg_match('/^\{\:expiry\:(\d+)\}/', $header, $matches)) {
			return false;
		}
		$value = stream_get_contents($stream);
		fclose($stream);

		return array('expiry' => $matches[1], 'value' => $value);
	}

	/**
	 * Deletes a file using the corresponding cached item key.
	 *
	 * @see lithium\storage\cache\adapter\File::delete()
	 * @param string $key Key to uniquely identify the cached item.
	 * @return boolean `true` on success, `false` otherwise.
	 */
	protected function _delete($key) {
		$path = "{$this->_config['path']}/{$key}";
		return is_readable($path) && is_file($path) && unlink($path);
	}
}

?>