<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 *                Copyright 2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace lithium\security;

use \Exception;

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
	 * Generates random bytes for use in UUIDs and password salts.
	 *
	 * The method seeds the random source automatically. It uses
	 * /dev/urandom if the latter is available; `md_rand()` if not.
	 *
	 * It can also be used to generate arbitrary bits:
	 *
	 * {{{
	 * $bits = bin2hex(String::random(8)); // 64 bits
	 * }}}
	 *
	 * @param integer $bytes The number of random bytes to generate
	 * @param string Random bytes
	 */
	public static function random($bytes) {
		$source = static::$_source ?: static::_source();
		return $source($bytes);
	}
	
	/**
	 * Encodes bytes into an `./0-9A-Za-z` alphabet, for use as salt when
	 * hashing passwords.
	 *
	 * Note: this is not the same as RFC 1421, or `base64_encode()`, which
	 * uses an `+/0-9A-Za-z` alphabet.
	 *
	 * This function can be combined with `Crypto::random()` to generate random
	 * sequences of `./0-9A-Za-z` characters:
	 *
	 * {{{
	 * $salt = String::encode64(String::random(8)); // 64 bits
	 * }}}
	 *
	 * @param string $input The input bytes.
	 * @return string The same bytes in the `/.0-9A-Za-z` alphabet.
	 * @see lithium\security\Crypto::random()
	 */
	public static function encode64($input) {
		$base64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$i = 0;

		$count = strlen($input);
		$output = '';

		do {
			$value = ord($input[$i++]);
			$output .= $base64[$value & 0x3f];

			if ($i < $count) {
				$value |= ord($input[$i]) << 8;
			}
			$output .= $base64[($value >> 6) & 0x3f];

			if ($i++ >= $count) {
				break;
			}
			if ($i < $count) {
				$value |= ord($input[$i]) << 16;
			}
			$output .= $base64[($value >> 12) & 0x3f];

			if ($i++ >= $count) {
				break;
			}
			$output .= $base64[($value >> 18) & 0x3f];
		} while ($i < $count);

		return $output;
	}

	/**
	 * Configures Crypto::_$source using the best available random
	 * number generator, or the supplied Closure.
	 *
	 * On *nix systems, /dev/urandom gets used if available. On Windows
	 * systems, COM gets used if available.
	 *
	 * If all else fails, a Mersenne Twister gets used. (Strictly speaking,
	 * this fallback is inadequate, but good enough.)
	 *
	 * @return Closure The random number generator.
	 **/
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
				}
				catch (Exception $e) {
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
}

?>