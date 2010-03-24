<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapter;

use \Exception;

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
		'cookie_secure' => false, 'cookie_httponly' => false, 'save_path' => ''
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
	public function __construct(array $config = array()) {
		parent::__construct($config + $this->_defaults);
	}

	/**
	 * Initialization of the session.
	 *
	 * @todo Split up into an _initialize() and a _startup().
	 * @return void
	 */
	protected function _init() {
		foreach ($this->_defaults as $key => $config) {
			if (isset($this->_config[$key])) {
				if (ini_set("session.{$key}", $this->_config[$key]) === false) {
					throw new Exception("Could not initialize the session.");
				}
			}
		}
		if (!$this->isStarted()) {
			if (!$this->_startup()) {
				throw new Exception("Could not start session.");
			}
		}
		$_SESSION['_timestamp'] = time();
	}

	/**
	 * Starts the session.
	 *
	 * @return boolean True if session successfully started, false otherwise.
	 */
	protected function _startup() {
		session_write_close();

		if (headers_sent()) {
			if (empty($_SESSION)) {
				$_SESSION = array();
			}
			return false;
		} elseif (!isset($_SESSION)) {
			session_cache_limiter("must-revalidate");
		}
		return session_start();
	}

	/**
	 * Obtain the status of the session.
	 *
	 * @return boolean True if $_SESSION is accessible and if a '_timestamp' key
	 *         has been set, false otherwise.
	 */
	public function isStarted() {
		return (isset($_SESSION) && isset($_SESSION['_timestamp']));
	}

	/**
	 * Obtain the session id.
	 *
	 * @return mixed Session id, or null if the session has not been started.
	 */
	public function key() {
		return ($id = session_id()) == '' ? null : $id;
	}

	/**
	 * Checks if a value has been set in the session.
	 *
	 * @param string $key Key of the entry to be checked.
	 * @return boolean True if the key exists, false otherwise.
	 */
	public function check($key) {
		return function($self, $params, $chain) {
			return (isset($_SESSION[$params['key']]));
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
	public function read($key = null, array $options = array()) {
		return function($self, $params, $chain) {
			$key = $params['key'];

			if (!$key) {
				return $_SESSION;
			}
			return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
		};
	}

	/**
	 * Write a value to the session.
	 *
	 * @param string $key Key of the item to be stored.
	 * @param mixed $value The value to be stored.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return boolean True on successful write, false otherwise
	 */
	public static function write($key, $value, array $options = array()) {
		return function($self, $params, $chain) {
			$_SESSION[$params['key']] = $params['value'];
		};
	}

	/**
	 * Delete value from the session
	 *
	 * @param string $key The key to be deleted
	 * @param array $options Options array. Not used for this adapter method.
	 * @return boolean True on successful delete, false otherwise
	 */
	public static function delete($key, array $options = array()) {
		return function($self, $params, $chain) {
			$key = $params['key'];

			if (isset($_SESSION[$key])) {
				unset($_SESSION[$key]);
				return true;
			}
			return false;
		};
	}

	/**
	 * Determines if PHP sessions are enabled.
	 *
	 * return boolean True if enabled (that is, if session_id() returns a value), false otherwise.
	 */
	public static function enabled() {
		return (boolean) session_id();
	}
}

?>