<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\security;

/**
 * Hash utility class. Contains methods to calculate hashes and compare
 * them in a safe way preventing timing attacks.
 */
class Hash {

	/**
	 * Uses PHP's hashing functions to calculate a hash over the string provided, using the options
	 * specified. The default hash algorithm is SHA-512.
	 *
	 * @link http://php.net/hash
	 * @link http://php.net/hash_hmac
	 * @link http://php.net/hash_algos
	 * @param string $string The string to hash.
	 * @param array $options Supported options:
	 *        - `'type'` _string_: Any valid hashing algorithm. See the `hash_algos()` function to
	 *          determine which are available on your system.
	 *        - `'salt'` _string_: A _salt_ value which, if specified, will be prepended to the
	 *          string.
	 *        - `'key'` _string_: If specified `hash_hmac()` will be used to hash the string,
	 *          instead of `hash()`, with `'key'` being used as the message key.
	 *        - `'raw'` _boolean_: If `true`, outputs the raw binary result of the hash operation.
	 *          Defaults to `false`.
	 * @return string Returns a hashed string.
	 */
	public static function calculate($string, array $options = array()) {
		$defaults = array(
			'type' => 'sha512',
			'salt' => false,
			'key' => false,
			'raw' => false
		);
		$options += $defaults;

		if ($options['salt']) {
			$string = $options['salt'] . $string;
		}
		if ($options['key']) {
			return hash_hmac($options['type'], $string, $options['key'], $options['raw']);
		}
		return hash($options['type'], $string, $options['raw']);
	}

	/**
	 * Compares two hashes in constant time to prevent timing attacks.
	 *
	 * To successfully mitigate timing attacks and not leak the actual length of the known
	 * hash, it is important that _both provided hash strings  have the same length_ and that
	 * the _user-supplied hash string is passed as a second parameter_ rather than first.
	 *
	 * This function has the same signature and behavior as the native `hash_equals()` function
	 * and will use that function if available (PHP >= 5.6).
	 *
	 * An E_USER_WARNING will be emitted when either of the supplied parameters is not a string.
	 *
	 * @link http://php.net/hash_equals
	 * @link http://codahale.com/a-lesson-in-timing-attacks/ More about timing attacks.
	 * @param string $known The hash string of known length to compare against.
	 * @param string $user The user-supplied hash string.
	 * @return boolean Returns a boolean indicating whether the two hash strings are equal.
	 */
	public static function compare($known, $user) {
		if (function_exists('hash_equals')) {
			return hash_equals($known, $user);
		}
		if (!is_string($known) || !is_string($user)) {
			trigger_error('Expected `$known` & `$user` parameters to be strings.', E_USER_WARNING);
			return false;
		}

		if (($length = strlen($known)) !== strlen($user)) {
			return false;
		}
		for ($i = 0, $result = 0; $i < $length; $i++) {
			$result |= ord($left[$i]) ^ ord($right[$i]);
		}
		return $result === 0;
	}
}

?>