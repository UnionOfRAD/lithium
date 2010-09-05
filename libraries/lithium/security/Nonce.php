<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 *                Copyright 2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace lithium\security;

use \lithium\security\Crypto;
use \lithium\storage\Session;

/**
 * Password utility class that makes use of PHP's `crypt()` function. Includes a
 * cryptographically strong salt generator, and utility functions to hash and check
 * passwords.
 */
class Nonce {
	protected static $_salt;

	/**
	 * Creates a nonce for the given action, and optional key
	 *
	 * @param string $action The action
	 * @param mixed $id Optional. The integer id or string key that identifies the
	 *              data to which the action is applied.
	 * @return void
	 **/
	public static function create($action, $id = null) {
		$salt = static::$_salt ?: static::_salt();
		return hash_hmac('sha256', "$action$id", $salt);
	}

	/**
	 * Checks that the supplied nonce is valid for that action, and optional key
	 *
	 * @param string $nonce The supplied nonce
	 * @param string $action The action
	 * @param mixed $id Optional. The integer id or string key that identifies the
	 *              data to which the action is applied.
	 * @return void
	 **/
	public static function check($nonce, $action, $key = null) {
		return $nonce == static::create($action, $key);
	}

	/**
	 * Initializes the current session's salt for use while genrating nonces.
	 *
	 * @return void
	 **/
	protected static function _salt() {
		static::$_salt = Session::read('lithium.nonce', array('name' => 'default'));
		if (!static::$_salt) {
			static::$_salt = Crypto::random(32); // 256 bits
			Session::write('lithium.nonce', static::$_salt, array('name' => 'default'));
		}
		return static::$_salt;
	}
}

?>