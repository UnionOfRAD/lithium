<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapters;

class Php extends \lithium\core\Object {

	public function __construct($config = array()) {
		$defaults = array(
			'name' => '', 'expires' => '+1 day', 'domain' => '',
			'path' => '/', 'secure' => false, 'http' => false
		);
		parent::__construct((array)$config + $defaults);
	}

	/**
	 * Initialization of the session
	 *
	 */
	protected function _init() {
		if (function_exists('session_write_close')) {
			session_write_close();
		}

        if (headers_sent()) {
            if (empty($_SESSION)) {
                $_SESSION = array();
            }
            return false;
        } elseif (!isset($_SESSION)) {
            session_cache_limiter ("must-revalidate");
            session_start();
            header ('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
            return true;
        } else {
            session_start();
            return true;
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

		};
	}

	public static function write($key, $value, $options = array()) {
		return function($self, $params, $chain) {

		};
	}
}

?>