<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapters;

use \lithium\util\Set;

/**
 * libmemcached cache adapter implementation
 *
 */
class Memcache extends \lithium\core\Object {

	/**
	 * Memcache object
	 *
	 * @var object Memcache object
	 */
	protected static $_Memcached = null;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'prefix' => '',
			'servers' => array(
				array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 100)
			)
		);

		if (is_null(static::$_Memcached)) {
			static::$_Memcached = new \Memcached();
		}

		$configuration = Set::merge($defaults, $config);
		parent::__construct($configuration);

		foreach ($this->_config['servers'] as $server) {
			static::$_Memcached->addServer($server['host'], $server['port'], $server['weight']);
		}

		return extension_loaded('memcached');
	}

	/**
	 * Write value(s) to the cache
	 *
	 * @param string $key
	 * @param string $value
	 * @param object $conditions
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($key, $value, $expiry, $conditions = null) {
		$Memcached =& static::$_Memcached;

		return function($self, $params, $chain) use (&$Memcached) {
			extract($params);
			$expires = strtotime($expiry);

			$Memcached->set($key . '_expires', $expires, $expires);
			return $Memcached->set($key, $data, $expires);

		};
	}

	/**
	 * Read value(s) from the cache
	 *
	 * @param string $key
	 * @param object $conditions
	 * @return mixed Cached value if successful, false otherwise
	 * @todo Refactor to use RES_NOTFOUND for return value checks
	 */
	public function read($key, $conditions = null) {
		$Memcached =& static::$_Memcached;

		return function($self, $params, $chain) use (&$Memcached) {
			extract($params);
			$cachetime = intval($Memcached->get($key . '_expires'));
			$time = time();
			return ($cachetime < $time) ? false : $Memcached->get($key);
		};
	}

	/**
	 * Delete value from the cache
	 *
	 * @param string $key
	 * @param object $conditions
	 * @return mixed True on successful delete, false otherwise
	 */
	public function delete($key, $conditions = null) {
		$Memcached =& static::$_Memcached;

		return function($self, $params, $chain) use (&$Memcached) {
			extract($params);
			$Memcached->delete($key . '_expires');
			return $Memcached->delete($key);
		};
	}

	/**
	 * Clears user-space cache
	 *
	 * @return mixed True on successful clear, false otherwise
	 */
	public function clear() {
		return static::$_Memcached->flush();
	}
}

?>