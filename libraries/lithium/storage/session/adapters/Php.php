<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapters;

class Php extends \lithium\core\Object {

	public function __construct($config = array()) {
		$defaults = array(
			'name' => '', 'cookie_lifetime' => '86400', 'cookie_domain' => '',
			'save_path' => '/tmp', 'cookie_secure' => false, 'cookie_httponly' => false
		);
		parent::__construct((array)$config + $defaults);
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

		foreach ($this->_config as $key => &$config) {
			if ($config == 'init') continue;
			ini_set("session.$key", $config);
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
			return (isset($_SESSION[$key])) ?: null;
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
}

?>