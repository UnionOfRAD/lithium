<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

use SplFileInfo;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use lithium\core\Libraries;
use lithium\storage\Cache;
use Closure;

/**
 * A minimal file-based cache.
 *
 * This File adapter provides basic support for `write`, `read`, `delete`
 * and `clear` cache functionality, as well as allowing the first four
 * methods to be filtered as per the Lithium filtering system. The File adapter
 * is a very simple cache, and should only be used for prototyping or for specifically
 * caching _files_. For more general caching needs, please consider using a more
 * appropriate cache adapter.
 *
 * This adapter does *not* provide increment/decrement functionality. For such
 * functionality, please use a more appropriate cache adapter.
 *
 * This adapter synthetically supports multi-key `write`, `read` and `delete` operations.
 *
 * The path that the cached files will be written to defaults to
 * `<app>/resources/tmp/cache`, but is user-configurable on cache configuration.
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
	 * @param array $config Configuration parameters for this cache adapter. These settings are
	 *        indexed by name and queryable through `Cache::config('name')`.
	 *        The defaults are:
	 *        - 'path' : Path where cached entries live, for example
	 *          `Libraries::get(true, 'resources') . '/tmp/cache'`.
	 *        - 'expiry' : Default expiry time used if none is explicitly set when calling
	 *          `Cache::write()`.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'path' => Libraries::get(true, 'resources') . '/tmp/cache',
			'prefix' => '',
			'expiry' => '+1 hour'
		);
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
		$path = $this->_config['path'];
		$expiry = $expiry || $expiry === Cache::PERSIST ? $expiry : $this->_config['expiry'];

		return function($self, $params) use (&$path, $expiry) {
			if (!$expiry || $expiry === Cache::PERSIST) {
				$expires = 0;
			} elseif (is_int($expiry)) {
				$expires = $expiry + time();
			} else {
				$expires = strtotime($expiry);
			}
			foreach ($params['keys'] as $key => $value) {
				$data = "{:expiry:{$expires}}\n{$value}";

				if (!file_put_contents("{$path}/{$key}", $data)) {
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
		$path = $this->_config['path'];

		return function($self, $params) use (&$path) {
			$results = array();

			foreach ($params['keys'] as $key) {
				$file = new SplFileInfo($p = "{$path}/{$key}");

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
		$path = $this->_config['path'];

		return function($self, $params) use (&$path) {
			foreach ($params['keys'] as $key) {
				$file = new SplFileInfo($p = "{$path}/{$key}");

				if (!$file->isFile() || !$file->isReadable()) {
					return false;
				}
				if (!file_exists($p) || !unlink($p)) {
					return false;
				}
			}
			return true;
		};
	}

	/**
	 * Clears user-space cache.
	 *
	 * @return mixed True on successful clear, false otherwise.
	 */
	public function clear() {
		$base = new RecursiveDirectoryIterator($this->_config['path']);
		$iterator = new RecursiveIteratorIterator($base);

		foreach ($iterator as $file) {
			if ($file->isFile()) {
				unlink($file->getPathName());
			}
		}
		return true;
	}
}

?>