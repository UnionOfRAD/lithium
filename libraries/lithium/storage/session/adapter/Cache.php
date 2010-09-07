<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapter;

use RuntimeException;
use lithium\core\ConfigException;

/**
 * The `Cache` adapter is a simple session adapter which allows session data to be written to a
 * cache configuration.
 *
 * In order to use this adapter, you must first create a named cache configuration to connect the
 * adapter to. For example:
 *
 * {{{
 * use lithium\storage\Cache;
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
 * use lithium\storage\Session;
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
	 * @var array
	 */
	protected $_classes = array(
		'cache' => 'lithium\storage\Cache'
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
	 * Initialization of the session.
	 * Sets the session save handlers to this adapters' corresponding methods.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		foreach ($this->_defaults as $key => $config) {
			if (isset($this->_config[$key])) {
				if (ini_set("session.{$key}", $this->_config[$key]) === false) {
					throw new ConfigException("Could not initialize the session.");
				}
			}
		}
		$this->_model = Libraries::locate('models', $this->_config['model']);

		session_set_save_handler(
			array(&$this, '_open'), array(&$this, '_close'), array(&$this, '_read'),
			array(&$this, '_write'), array(&$this, '_destroy'), array(&$this, '_gc')
		);
		register_shutdown_function('session_write_close');
		$this->_startup();
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

	/**
	 * Read a value from the session.
	 *
	 * @param null|string $key Key of the entry to be read. If no key is passed, all
	 *        current session data is returned.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return mixed Data in the session if successful, false otherwise.
	 */
	public static function read($key = null, array $options = array()) {
		if (!static::isStarted() && !static::_start()) {
			throw new RuntimeException("Could not start session.");
		}
		return function($self, $params, $chain) {
			$key = $params['key'];

			if (!$key) {
				return $_SESSION;
			}
			if (strpos($key, '.') === false) {
				return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
			}
			$filter  = function($keys, $data) use (&$filter) {
				$key = array_shift($keys);
				if (isset($data[$key])) {
					return (empty($keys)) ? $data[$key] : $filter($keys, $data[$key]);
				}
			};
			return $filter(explode('.', $key), $_SESSION);
		};
	}
}

?>