<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 *                Copyright 2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace lithium\security;

use Exception;

/**
 * Cryptographic utility class. Includes a random number generator, and a base64
 * encoder for use with DES and XDES.
 *
 * @see lithium\security\Password
 */
class Crypto {

	/**
	 * A closure which, given a number of bytes, returns that amount of
	 * random bytes.
	 *
	 * @var Closure
	 */
	protected static $_source;

	/**
	 * Generates random bytes for use in UUIDs and password salts, using
	 * (when available) a cryptographically strong random number generator.
	 *
	 * {{{
	 * $bits = String::random(8); // 64 bits
	 * $hex = bin2hex($bits); // [0-9a-f]+
	 * }}}
	 *
	 * @param integer $bytes The number of random bytes to generate
	 * @return string Random bytes
	 */
	public static function random($bytes) {
		$source = static::$_source ?: static::_source();
		return $source($bytes);
	}

	/**
	 * Generates random bytes encoded into an `./0-9A-Za-z` alphabet, for use
	 * as salt when hashing passwords for instance.
	 *
	 * Note: this is not the same as `base64_encode()`, which encodes bytes
	 * using an `A-Za-z0-9+/` alphabet.
	 *
	 * @param integer $bytes The number of random bytes to generate.
	 * @return string The random bytes encoded in the `./0-9A-Za-z` alphabet.
	 * @see lithium\security\Crypto::random()
	 */
	public static function random64($bytes) {
		// The alphabet used by base64_encode() is different than the one we
		// should be using. When considering the meaty part of the resulting
		// string, however, a bijection allows to go the from one to another.
		// Given that we're working on random bytes, we can use safely use
		// base64_encode() without losing any entropy, sparing ourselves the
		// hassle of maintaining more code than needed.
		$encoded = base64_encode(static::random($bytes));
		return strtr(rtrim($encoded, '='), '+', '.');
	}

	/**
	 * Initializes Crypto::$_source using the best available random
	 * number generator.
	 *
	 * When available, /dev/urandom and COM gets used on *unix and
	 * Windows systems, respectively.
	 *
	 * If all else fails, a Mersenne Twister gets used. (Strictly
	 * speaking, this fallback is inadequate, but good enough.)
	 *
	 * @return Closure The random number generator.
	 */
	protected static function _source() {
		switch (true) {
			case isset(static::$_source);
				return static::$_source;

			case is_readable('/dev/urandom') && $fp = fopen('/dev/urandom', 'rb'):
				return static::$_source = function($bytes) use (&$fp) {
					return fread($fp, $bytes);
				};

			case class_exists('COM', 0):
				// http://msdn.microsoft.com/en-us/library/aa388182(VS.85).aspx
				try {
					$com = new COM('CAPICOM.Utilities.1');
					return static::$_source = function($bytes) use ($com) {
						return base64_decode($com->GetRandom($bytes,0));
					};
				} catch (Exception $e) {
				}

			default:
				// fallback to using mt_rand() if all else fails
				return static::$_source = function($bytes) {
					$rand = '';
					for ($i = 0; $i < $bytes; $i++) {
						$rand .= chr(mt_rand(0, 255));
					}
					return $rand;
				};
		}
	}

	/**
	 * Uses PHP's hashing functions to create a hash of the string provided, using the options
	 * specified. The default hash algorithm is SHA-512.
	 *
	 * @link http://php.net/manual/en/function.hash.php PHP Manual: hash()
	 * @link http://php.net/manual/en/function.hash-hmac.php PHP Manual: hash_hmac()
	 * @link http://php.net/manual/en/function.hash-algos.php PHP Manual: hash_algos()
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
	public static function hash($string, array $options = array()) {
		$defaults = array(
			'type' => 'sha512',
			'salt' => false,
			'key' => false,
			'raw' => false,
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
}

?>