<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapter;

/**
 * The `Cache` adapter is a simple session adapter which allows session data to be written to a
 * cache configuration.
 *
 * In order to use this adapter, you must first create a named cache configuration to connect the
 * adapter to. For example:
 *
 * {{{
 * use \lithium\storage\Cache;
 *
 * Cache::config(array(
 * 	'local' => array('adapter' => 'Apc'),
 * 	'distributed' => array(
 * 		'adapter' => 'Memcached',
 * 		'servers' => array('127.0.0.1', 11211),
 * 	)
 * ));}}}
 *
 * Then, you can configure your session storage:
 *
 * {{{
 * use \lithium\storage\Session;
 *
 * Session::config(array(
 * 	'default' => array('adapter' => 'Cache', 'config' => 'distributed')
 * ));
 * }}}
 *
 * This will cause your users' session data to be written to a Memcache server. See the constructor
 * for additional information on the available configuration settings for this adapter.
 */
class Cache extends \lithium\core\Object {

	/**
	 * Classes used by `Cache`.
	 *
	 * @package default
	 * @author John David Anderson
	 */
	protected $_classes = array(
		'cache' => '\lithium\storage\Cache'
	);

	/**
	 * Sets up the adapter with the configuration assigned by the `Session` class.
	 *
	 * @param array $config Available configuration options for this adapter:
	 *              - `'config'` _string_: The name of the cache configuration (created in the
	 *                `Cache` class) with which this adapter should interact.
	 *              - `'expiry'` _string_: A `strtotime()`-compatible string indicating when the
	 *                session store should expire cached session data.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'config' => null,
			'expiry' => '+999 days'
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Uses PHP's default session handling to generate a unique session ID.
	 *
	 * @return string Returns the session ID for the current request, or `null` if the session is
	 *         invalid or if a key could not be generated.
	 */
	public function key() {
		return ($id = session_id()) == '' ? null : $id;
	}

	/**
	 * Write a value to the session.
	 *
	 * @param string $key Key of the item to be stored.
	 * @param mixed $value The value to be stored.
	 * @param array $options Options array.
	 * @return boolean True on successful write, false otherwise.
	 */
	public function write($key, $value = null, array $options = array()) {
		$config = $this->_config;
		$classes = $this->_classes;

		return function($self, $params, $chain) use ($config, $classes) {
			return $classes['cache']::write(
				$config['config'], $params['key'], $params['value']
			);
		};
	}
}

?>