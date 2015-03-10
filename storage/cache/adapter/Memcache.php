<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter;

use Memcached;
use lithium\util\Set;
use lithium\storage\Cache;
use Closure;

/**
 * Memcache (libmemcached) cache adapter implementation using `pecl/memcached`.
 *
 * This adapter requires `pecl/memcached` to be installed. The extension
 * must be enabled according to the extension documention and a running
 * memcached server instance must be available.
 *
 * This adapter natively handles multi-key reads/writes/deletes, natively
 * provides serialization and key scoping features and supports atomic
 * increment/decrement operations as well as clearing the entire cache.
 * Delegation of method calls to the connection object is available.
 *
 * Cached item persistence is not guaranteed. Infrequently used items will
 * be evicted from the cache when there is no room to store new ones.
 *
 * A simple configuration can be accomplished as follows:
 *
 * ```
 * Cache::config(array(
 *     'default' => array(
 *         'adapter' => 'Memcached',
 *         'host' => '127.0.0.1:11211'
 *     )
 * ));
 * ```
 *
 * The `'host'` key accepts entries in multiple formats, depending on the number of
 * Memcache servers you are connecting to. See the `__construct()` method for more
 * information.
 *
 * @link http://php.net/class.memcached.php
 * @link http://pecl.php.net/package/memcached
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
	 * Constructor. Instantiates the `Memcached` object, adds appropriate servers to the pool,
	 * and configures any optional settings passed (see the `_init()` method). When adding
	 * servers, the following formats are valid for the `'host'` key:
	 *
	 *   - `'127.0.0.1'`
	 *      Configure the adapter to connect to one Memcache server on the default port.
	 *
	 *   - `'127.0.0.1:11222'`
	 *      Configure the adapter to connect to one Memcache server on a custom port.
	 *
	 *   - `array('167.221.1.5:11222' => 200, '167.221.1.6')`
	 *      Connect to one server on a custom port with a high selection weight, and
	 *      a second server on the default port with the default selection weight.
	 *
	 * @see lithium\storage\Cache::config()
	 * @param array $config Configuration for this cache adapter. These settings are queryable
	 *        through `Cache::config('name')`. The available options are as follows:
	 *        - `'scope'` _string_: Scope which will prefix keys; per default not set.
	 *        - `'expiry'` _mixed_: The default expiration time for cache values, if no value
	 *          is otherwise set. Can be either a `strtotime()` compatible tring or TTL in
	 *          seconds. To indicate items should not expire use `Cache::PERSIST`. Defaults
	 *          to `+1 hour`.
	 *        - `'host'` _mixed_: Specifies one or more Memcache servers to connect to, with
	 *          optional server selection weights. See above for example values.
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'scope' => null,
			'expiry' => '+1 hour',
			'host' => '127.0.0.1'
		);
		parent::__construct(Set::merge($defaults, $config));
	}

	/**
	 * Handles the actual `Memcached` connection and server connection
	 * adding for the adapter constructor and sets prefix using the scope
	 * if provided.
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

		if ($this->_config['scope']) {
			$this->connection->setOption(Memcached::OPT_PREFIX_KEY, "{$this->_config['scope']}:");
		}
	}

	/**
	 * Dispatches a not-found method to the connection object. That way, one can
	 * easily use a custom method on the adapter. If you want to know, what methods
	 * are available, have a look at the documentation of memcached.
	 *
	 * ```
	 * Cache::adapter('memcache')->methodName($argument);
	 * ```
	 *
	 * @link http://php.net/class.memcached.php
	 * @param string $method Name of the method to call.
	 * @param array $params Parameter list to use when calling $method.
	 * @return mixed Returns the result of the method call.
	 */
	public function __call($method, $params = array()) {
		return call_user_func_array(array(&$this->connection, $method), $params);
	}

	/**
	 * Determines if a given method can be called.
	 *
	 * @param string $method Name of the method.
	 * @param boolean $internal Provide `true` to perform check from inside the
	 *                class/object. When `false` checks also for public visibility;
	 *                defaults to `false`.
	 * @return boolean Returns `true` if the method can be called, `false` otherwise.
	 */
	public function respondsTo($method, $internal = false) {
		if (parent::respondsTo($method, $internal)) {
			return true;
		}
		return is_callable(array($this->connection, $method));
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
				return array($host, (integer) $port);
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
		if (count($keys) > 1) {
			return $this->connection->setMulti($keys, $expires);
		}
		return $this->connection->set(key($keys), current($keys), $expires);
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
		if (count($keys) > 1) {
			if (!$results = $this->connection->getMulti($keys)) {
				return array();
			}
		} else {
			$result = $this->connection->get($key = current($keys));

			if ($result === false && $this->connection->getResultCode() === Memcached::RES_NOTFOUND) {
				return array();
			}
			$results = array($key => $result);
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
		if (count($keys) > 1) {
			return $this->connection->deleteMulti($keys);
		}
		return $this->connection->delete(current($keys));
	}

	/**
	 * Performs an atomic decrement operation on specified numeric cache item.
	 *
	 * Note that, as per the Memcached specification:
	 * "If the item's value is not numeric, it is treated as if the value were 0.
	 * If the operation would decrease the value below 0, the new value will be 0."
	 *
	 * @link http://php.net/manual/memcached.decrement.php
	 * @param string $key Key of numeric cache item to decrement.
	 * @param integer $offset Offset to decrement - defaults to `1`.
	 * @return integer|boolean The item's new value on successful decrement, else `false`.
	 */
	public function decrement($key, $offset = 1) {
		return $this->connection->decrement($key, $offset);
	}

	/**
	 * Performs an atomic increment operation on specified numeric cache item.
	 *
	 * Note that, as per the Memcached specification:
	 * "If the item's value is not numeric, it is treated as if the value were 0."
	 *
	 * @link http://php.net/manual/memcached.decrement.php
	 * @param string $key Key of numeric cache item to increment.
	 * @param integer $offset Offset to increment - defaults to `1`.
	 * @return integer|boolean The item's new value on successful increment, else `false`.
	 */
	public function increment($key, $offset = 1) {
		return $this->connection->increment($key, $offset);
	}

	/**
	 * Clears entire cache by flushing it. All cache keys using the
	 * configuration but *without* honoring the scope are removed.
	 *
	 * Internally keys are not removed but invalidated. Thus this
	 * operation doesn't actually free memory on the instance.
	 *
	 * The behavior and result when removing a single key
	 * during this process fails is unknown.
	 *
	 * @return boolean `true` on successful clearing, `false` otherwise.
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