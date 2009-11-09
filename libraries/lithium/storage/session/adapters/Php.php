<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapters;

class Php extends \lithium\core\Object {

	protected $_defaults = array(
		'name' => '', 'cookie_lifetime' => '86400', 'cookie_domain' => '',
		'save_path' => '/tmp', 'cookie_secure' => false, 'cookie_httponly' => false
	);

	public function __construct($config = array()) {
		parent::__construct((array)$config + $this->_defaults);
	}

	/**
	 * Initialization of the session
	 *
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

	public function isStarted() {
		return (isset($_SESSION) && isset($_SESSION['_timestamp']));
	}

	public function key() {
		return ($id = session_id()) == '' ? null : $id;
	}

	public function read($key, $options = array()) {
		return function($self, $params, $chain) {
			extract($params);
			return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
		};
	}

	public static function write($key, $value, $options = array()) {
		return function($self, $params, $chain) {
			extract($params);
			$_SESSION[$key] = $value;
		};
	}

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
	public function enabled() {
		return (bool) session_id();
	}
}

?>