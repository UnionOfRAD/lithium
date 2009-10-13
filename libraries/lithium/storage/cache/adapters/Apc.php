<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapters;

/**
 * Alternative PHP Cache (APC) cache adapter implementation
 *
 */
class Apc extends \lithium\core\Object {

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('prefix' => '');
		parent::__construct($config + $defaults);
	}

	/**
	 * Write value(s) to the cache
	 *
	 * @param string $key
	 * @param string $value
	 * @param object $conditions
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($key, $data, $expiry, $conditions = null) {
		return function($self, $params, $chain) {
			extract($params);
			$cachetime = strtotime($expiry);
			$duration = $cachetime - time();

			apc_store($key . '_expires', $cachetime, $duration);
			return apc_store($key, $data, $cachetime);

		};
	}

	/**
	 * Read value(s) from the cache
	 *
	 * @param string $key
	 * @param object $conditions
	 * @return mixed Cached value if successful, false otherwise
	 */
	public function read($key, $conditions = null) {
		return function($self, $params, $chain) {
			extract($params);
			$cachetime = intval(apc_fetch($key . '_expires'));
			$time = time();
			return ($cachetime < $time) ? false : apc_fetch($key);
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
		return function($self, $params, $chain) {
			extract($params);
			apc_delete($key . '_expires');
			return apc_delete($key);
		};
	}

	/**
	 * Clears user-space cache
	 *
	 * @return mixed True on successful clear, false otherwise
	 */
	public function clear() {
		return apc_clear_cache('user');
	}
}

?>