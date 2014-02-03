<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

use Memcached;
use lithium\util\Set;
use lithium\storage\Cache;
use Closure;

/**
 * A Memcache (libmemcached) cache adapter implementation. Requires
 * [pecl/memcached](http://pecl.php.net/package/memcached).
 *
 * The `Memcache` cache adapter is meant to be used through the `Cache` interface,
 * which abstracts away key generation, adapter instantiation and filter
 * implementation.
 *
 * A simple configuration of this adapter can be accomplished in `config/bootstrap/cache.php`
 * as follows:
 *
 * {{{
 * Cache::config(array(
 *     'cache-config-name' => array(
 *         'adapter' => 'Memcached',
 *         'host' => '127.0.0.1:11211'
 *     )
 * ));
 * }}}
 *
 * The `'host'` key accepts entries in multiple formats, depending on the number of Memcache servers
 * you are connecting to. See the `__construct()` method for more information.
 *
 * This Memcache adapter provides basic support for `write`, `read`, `delete`
 * and `clear` cache functionality, as well as allowing the first four
 * methods to be filtered as per the Lithium filtering system.
 *
 * This adapter natively supports multi-key `write` and `read` operations.
 *
 * @see lithium\storage\cache\adapter\Memcache::__construct()
 * @see lithium\storage\Cache::key()
 * @see lithium\storage\Cache::adapter()
 */
class Memcache extends \lithium\storage\cache\Adapter {

	/**
	 * The default port used to connect to Memcache servers, if none is specified.
	 */
	const CONN_DEFAULT_PORT = 11211;

	/**
	 * `Memcached` object instance used by this adapter.
	 *
	 * @var object
	 */
	public $connection = null;

	/**
	 * Object constructor. Instantiates the `Memcached` object, adds appropriate servers to the
	 * pool, and configures any optional settings passed (see the `_init()` method). When adding
	 * servers, the following formats are valid for the `'host'` key:
	 *
	 * - `'127.0.0.1'`: Configure the adapter to connect to one Memcache server on the default port.
	 * - `'127.0.0.1:11222'`: Configure the adapter to connect to one Memcache server on a custom
	 *   port.
	 * - `array('167.221.1.5:11222' => 200, '167.221.1.6')`: Connect to one server on a
	 *   custom port with a high selection weight, and a second server on the default port with the
	 *   default selection weight.
	 *
	 * @see lithium\storage\Cache::config()
	 * @param array $config Configuration parameters for this cache adapter.
	 *              These settings are indexed by name and queryable through
	 *              `Cache::config('name')`. The available options are as follows:
	 *              - `'expiry'` _mixed_: The default expiration time for cache values, if no value
	 *                is otherwise set. See the `$expiry` parameter of `Memcache::write()`.
	 *              - `'host'` _mixed_: Specifies one or more Memcache servers to connect to, with
	 *                optional server selection weights. See above for example values.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'expiry' => '+1 hour',
			'host' => '127.0.0.1'
		);
		parent::__construct(Set::merge($defaults, $config));
	}

	/**
	 * Handles the actual `Memcached` connection and server connection adding for the adapter
	 * constructor.
	 *
	 * @return void
	 */
	protected function _init() {
		$this->connection = $this->connection ?: new Memcached();
		$servers = array();

		if (isset($this->_config['servers'])) {
			$this->connection->addServers($this->_config['servers']);
			return;
		}
		$this->connection->addServers($this->_formatHostList($this->_config['host']));
	}

	/**
	 * Formats standard `'host:port'` strings into arrays used by `Memcached`.
	 *
	 * @param mixed $host A host string in `'host:port'` format, or an array of host strings
	 *              optionally paired with relative selection weight values.
	 * @return array Returns an array of `Memcached` server definitions.
	 */
	protected function _formatHostList($host) {
		$fromString = function($host) {
			if (strpos($host, ':')) {
				list($host, $port) = explode(':', $host);
				return array($host, intval($port));
			}
			return array($host, Memcache::CONN_DEFAULT_PORT);
		};

		if (is_string($host)) {
			return array($fromString($host));
		}
		$servers = array();

		while (list($server, $weight) = each($this->_config['host'])) {
			if (is_string($weight)) {
				$servers[] = $fromString($weight);
				continue;
			}
			$server = $fromString($server);
			$server[] = $weight;
			$servers[] = $server;
		}
		return $servers;
	}

	/**
	 * Write values to the cache. All items to be cached will receive an
	 * expiration time of `$expiry`.
	 *
	 * Expiration is always based off the current unix time in order to gurantee we never
	 * exceed the TTL limit of 30 days when specifying the TTL directly.
	 *
	 * @param array $keys Key/value pairs with keys to uniquely identify the to-be-cached item.
	 * @param string|integer $expiry A `strtotime()` compatible cache time or TTL in seconds.
	 *                       To persist an item use `\lithium\storage\Cache::PERSIST`.
	 * @return Closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
	public function write(array $keys, $expiry = null) {
		$connection =& $this->connection;
		$expiry = $expiry || $expiry === Cache::PERSIST ? $expiry : $this->_config['expiry'];

		return function($self, $params) use (&$connection, $expiry) {
			if (!$expiry || $expiry === Cache::PERSIST) {
				$expires = 0;
			} elseif (is_int($expiry)) {
				$expires = $expiry + time();
			} else {
				$expires = strtotime($expiry);
			}
			if (count($params['keys']) > 1) {
				return $connection->setMulti($params['keys'], $expires);
			}
			return $connection->set(key($params['keys']), current($params['keys']), $expires);
		};
	}

	/**
	 * Read values from the cache. Will attempt to return an array of data
	 * containing key/value pairs of the requested data.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return Closure Function returning cached values keyed by cache keys
	 *                 on successful read, keys which could not be read will
	 *                 not be included in the results array.
	 */
	public function read(array $keys) {
		$connection =& $this->connection;

		return function($self, $params) use (&$connection) {
			if (count($params['keys']) > 1) {
				return $connection->getMulti($params['keys']);
			}
			$result = $connection->get($key = current($params['keys']));
			$results = array($key => $result);

			if ($results[$key] === false) {
				if ($connection->getResultCode() === Memcached::RES_NOTFOUND) {
					$results = array();
				}
			}
			return $results;
		};
	}

	/**
	 * Will attempt to remove specified keys from the user space cache.
	 *
	 * @param array $keys Keys to uniquely identify the cached items.
	 * @return Closure Function returning `true` on successful delete, `false` otherwise.
	 */
	public function delete(array $keys) {
		$connection =& $this->connection;

		return function($self, $params) use (&$connection) {
			if (count($params['keys']) > 1) {
				return $connection->deleteMulti($params['keys']);
			}
			return $connection->delete(current($params['keys']));
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
	 * @return Closure Function returning item's new value on successful decrement, else `false`
	 */
	public function decrement($key, $offset = 1) {
		$connection =& $this->connection;

		return function($self, $params) use (&$connection, $offset) {
			return $connection->decrement($params['key'], $offset);
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
	 * @return Closure Function returning item's new value on successful increment, else `false`
	 */
	public function increment($key, $offset = 1) {
		$connection =& $this->connection;

		return function($self, $params) use (&$connection, $offset) {
			return $connection->increment($params['key'], $offset);
		};
	}

	/**
	 * Clears user-space cache.
	 *
	 * @return mixed Returns `true` on successful clear, `false` otherwise.
	 */
	public function clear() {
		return $this->connection->flush();
	}

	/**
	 * Determines if the `Memcached` extension has been installed.
	 *
	 * @return boolean Returns `true` if the `Memcached` extension is installed and enabled, `false`
	 *         otherwise.
	 */
	public static function enabled() {
		return extension_loaded('memcached');
	}
}

?>