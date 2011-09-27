<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapter\zendserver;

/**
 * A Zend Server Shared Memory cache adapter implementation.
 *
 * The Zend Server Shared Memory cache adapter is meant to be used through the `Cache` interface,
 * which abstracts away key generation, adapter instantiation and filter
 * implementation.
 *
 * A simple configuration of this adapter can be accomplished in `app/config/bootstrap.php`
 * as follows:
 *
 * {{{
 * Cache::config(array(
 *     'cache-config-name' => array('adapter' => 'zendserver/SharedMemory')
 * ));
 * }}}
 *
 * This adapter provides basic support for `write`, `read`, `delete`
 * and `clear` cache functionality, as well as allowing the first four
 * methods to be filtered as per the Lithium filtering system. Additionally,
 * This adapter defines several methods that are _not_ implemented in other
 * adapters, and are thus non-portable - see the documentation for `Cache`
 * as to how these methods should be accessed.
 *
 * This adapter supports multi-key `write`, `read` and `delete` operations.
 *
 * Learn more about Zend Server data cache in the [Zend Server manual](http://files.zend.com/help/Zend-Server-Community-Edition/data_cache_component.htm).
 *
 * @see lithium\storage\Cache::key()
 */
class Disk extends \lithium\core\Object {

	/**
	 * Class constructor
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'prefix' => '',
			'expiry' => '+1 hour'
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Write value(s) to the cache.
	 *
	 * This adapter method supports multi-key write. By specifying `$key` as an
	 * associative array of key/value pairs, `$data` is ignored and all keys that
	 * are cached will receive an expiration time of `$expiry`.
	 *
	 * @param string|array $key The key to uniquely identify the cached item.
	 * @param mixed $data The value to be cached.
	 * @param null|string $expiry A strtotime() compatible cache time. If no expiry time is set,
	 *        then the default cache expiration time set with the cache configuration will be used.
	 * @return closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
    public function write($key, $data, $expiry = null) {
   		$expiry = ($expiry) ?: $this->_config['expiry'];

   		return function($self, $params) use ($expiry) {
   			$cachetime = (is_int($expiry) ? $expiry : strtotime($expiry)) - time();
   			$key = $params['key'];

   			if (is_array($key)) {
                $output = array();
                foreach($key as $k=>$v) {
                    $result = zend_disk_cache_store($k, $v, $cachetime);
                    if(!$result) {
                        $output[$k] = false;
                    }
                }
                return $output;
   			}
   			return zend_disk_cache_store($params['key'], $params['data'], $cachetime);
   		};
   	}

	/**
	 * Read value(s) from the cache.
	 *
	 * This adapter method supports multi-key reads. By specifying `$key` as an
	 * array of key names, this adapter will attempt to return an array of data
	 * containing key/value pairs of the requested data.
	 *
	 * @param string|array $key The key to uniquely identify the cached item.
	 * @return closure Function returning cached value on successful read, `false` otherwise.
	 */
	public function read($key) {
		return function($self, $params) {
            
            if(is_array($params['key'])) {
                $output = array();
                foreach($params['key'] as $key) {
                    $output[$key] = $self->read($key);
                }
                return $output;
            } else {
                return zend_disk_cache_fetch($params['key']);
            }
		};
	}

	/**
	 * Delete value from the cache.
	 *
	 * This adapter method supports multi-key deletes. By specifynig `$key` as an
	 * array of key names, this adapter method will attempt to remove these keys
	 * from the user space cache.
	 *
	 * @param string|array $key The key to uniquely identify the cached item.
	 * @return closure Function returning `true` on successful delete, `false` otherwise.
	 */
	public function delete($key) {
		return function($self, $params) {
            if(is_array($params['key'])) {
                $output = array();
                foreach($params['key'] as $k) {
                    $result = zend_disk_cache_delete($k);
                    if(!$result) {
                        $output[$k] = false;
                    }
                }
                return $output;
            }
			return zend_disk_cache_delete($params['key']);
		};
	}

	/**
	 * Clears user-space cache
	 *
	 * @return mixed True on successful clear, false otherwise
	 */
	public function clear() {
		return zend_disk_cache_clear();
	}

	/**
	 * Determines if the Zend Data Cache extension has been installed and
	 * if the userspace cache is available.
	 *
	 * @return boolean `true` if enabled, `false` otherwise
	 */
	public static function enabled() {
		$loaded = extension_loaded('Zend Data Cache');
		$enabled = ini_get('zend_datacache.enable');
		return ($loaded && $enabled);
	}
}

?>