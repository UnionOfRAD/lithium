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
			'name' => '', 'expires' => '+1 day', 'domain' => '',
			'path' => '/', 'secure' => false, 'http' => false
		);
		parent::__construct((array)$config + $defaults);
	}

	protected function _init() {
		if (!isset($_SESSION)) {
			session_cache_limiter("must-revalidate");
			session_start();
		}
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