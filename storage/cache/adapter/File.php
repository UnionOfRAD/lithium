<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

use SplFileInfo;
use DirectoryIterator;
use lithium\core\Libraries;
use lithium\storage\Cache;
use Closure;

/**
 * A minimal file-based cache.
 *
 * The File adapter is a very simple cache, and should only be used for prototyping
 * or for specifically caching _files_. For more general caching needs, please consider
 * using a more appropriate cache adapter.
 *
 * This adapter has no external dependencies. Operations in read/write/delete are atomic
 * for single-keys only. Clearing the cache is supported. Real persistence of cached items
 * is provided.
 *
 * This adapter does *not* provided increment/decrement functionality and also can't handle
 * serialization natively. Scope support is available but not natively.
 *
 * A simple configuration can be accomplished as follows:
 *
 * {{{
 * Cache::config(array(
 *     'default' => array(
 *         'adapter' => 'File',
 *         'strategies => array('Serializer')
 *      )
 * ));
 * }}}
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
	 * Class constructor.
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
			$data = "{:expiry:{$expires}}\n{$value}";

			if (!file_put_contents("{$this->_config['path']}/{$key}", $data)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Read values from the cache. Will attempt to return an array of data
	 * containing key/value pairs of the requested data.
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
			$file = new SplFileInfo($p = "{$this->_config['path']}/{$key}");

			if (!$file->isFile() || !$file->isReadable()) {
				continue;
			}
			$data = file_get_contents($p);

			preg_match('/^\{\:expiry\:(\d+)\}\\n/', $data, $matches);
			$expiry = $matches[1];

			if ($expiry < time() && $expiry != 0) {
				file_exists($p) && unlink($p);
				continue;
			}
			$results[$key] = preg_replace('/^\{\:expiry\:\d+\}\\n/', '', $data, 1);
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
			$file = new SplFileInfo($p = "{$this->_config['path']}/{$key}");

			if (!$file->isFile() || !$file->isReadable()) {
				return false;
			}
			if (!file_exists($p) || !unlink($p)) {
				return false;
			}
		}
		return true;
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
			if ($file->isFile()) {
				$result = unlink($file->getPathName()) && $result;
			}
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
			$data = file_get_contents($p = $file->getPathName());

			if (!preg_match('/^\{\:expiry\:(\d+)\}\\n/', $data, $matches)) {
				continue;
			}
			if ($matches[1] > time()) {
				continue;
			}
			$result = file_exists($p) && unlink($p) && $result;
		}
		return $result;
	}
}

?>