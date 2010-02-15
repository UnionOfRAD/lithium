<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapter;

/**
 * A minimal adapter to interface with native PHP sessions.
 *
 * This adapter provides basic support for `write`, `read` and `delete`
 * session handling, as well as allowing these three methods to be filtered as
 * per the Lithium filtering system.
 *
 */
class Php extends \lithium\core\Object {

	/**
	 * Default ini settings for this session adapter
	 *
	 * @var array Keys are session ini settings, but without the `session.` namespace.
	 */
	protected $_defaults = array(
		'name' => '', 'cookie_lifetime' => '86400', 'cookie_domain' => '',
		'cookie_secure' => false, 'cookie_httponly' => false
	);

	/**
	 * Class constructor
	 *
	 * Takes care of setting appropriate configurations for
	 * this object.
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array()) {
		parent::__construct((array) $config + $this->_defaults);
	}

	/**
	 * Initialization of the session.
	 *
	 * @todo Split up into an _initialize() and a _startup().
	 * @return void
	 */
	protected function _init() {
		session_write_close();

		if (headers_sent()) {
			$_SESSION = (empty($_SESSION)) ?: array();
		} elseif (!isset($_SESSION)) {
			session_cache_limiter("nocache");
		}
		session_start();

		foreach ($this->_defaults as $key => $config) {
			if (isset($this->_config[$key])) {
				ini_set("session.$key", $this->_config[$key]);
			}
		}
		$_SESSION['_timestamp'] = time();
	}

	/**
	 * Obtain the status of the session
	 *
	 * @return boolean True if $_SESSION is accessible and if a '_timestamp' key
	 *                 has been set, false otherwise.
	 */
	public function isStarted() {
		return (isset($_SESSION) && isset($_SESSION['_timestamp']));
	}

	/**
	 * Obtain the session id
	 *
	 * @return mixed Session id, or null if the session has not been started.
	 */
	public function key() {
		return ($id = session_id()) == '' ? null : $id;
	}

	/**
	 * Read value from the session
	 *
	 * @param string $key Key of the entry to be read
	 * @param array $options Options array
	 * @return mixed Data in the session if successful, false otherwise
	 */
	public function read($key, $options = array()) {
		return function($self, $params, $chain) {
			extract($params);
			return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
		};
	}

	/**
	 * Write value to the session
	 *
	 * @param string $key Key of the item to be stored
	 * @param mixed $value The value to be stored
	 * @param array $options Options array
	 * @return boolean True on successful write, false otherwise
	 */
	public static function write($key, $value, $options = array()) {
		return function($self, $params, $chain) {
			extract($params);
			$_SESSION[$key] = $value;
		};
	}

	/**
	 * Delete value from the session
	 *
	 * @param string $key The key to be deleted
	 * @param array $options Options array
	 * @return boolean True on successful delete, false otherwise
	 */
	public static function delete($key, $options = array()) {
		return function($self, $params, $chain) {
			extract($params);

			if (isset($_SESSION[$key])) {
				unset($_SESSION[$key]);
				return true;
			} else {
				return false;
			}
		};
	}

	/**
	 * Determines if PHP sessions are enabled.
	 *
	 * return boolean True if enabled, false otherwise
	 */
	public static function enabled() {
		return (boolean) session_id();
	}
}

?>