<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\security;

use COM;
use Exception;

/**
 * A cryptographically-strong random number generator, and a base64 encoder
 * for use with DES and XDES.
 */
class Random {

	/**
	 * Option flag used in `Random::generate()`.
	 */
	const ENCODE_BASE_64 = 1;

	/**
	 * A callable which, given a number of bytes, returns that amount of
	 * random bytes.
	 *
	 * @var callable
	 */
	protected static $_source;

	/**
	 * Generates random bytes for use in UUIDs and password salts, using
	 * (when available) a cryptographically strong random number generator.
	 *
	 * ```
	 * $bits = Random::generate(8); // 64 bits
	 * $hex = bin2hex($bits); // [0-9a-f]+
	 * ```
	 *
	 * Optionally base64-encodes the resulting random string per the following. The
	 * alphabet used by `base64_encode()` is different than the one we should be using.
	 * When considering the meaty part of the resulting string, however, a bijection
	 * allows to go the from one to another. Given that we're working on random bytes, we
	 * can use safely use `base64_encode()` without losing any entropy.
	 *
	 * @param integer $bytes The number of random bytes to generate.
	 * @param array $options The options used when generating random bytes:
	 *              - `'encode'` _integer_: If specified, and set to `Random::ENCODE_BASE_64`, the
	 *                resulting value will be base64-encoded, per the notes above.
	 * @return string Returns a string of random bytes.
	 */
	public static function generate($bytes, array $options = array()) {
		$defaults = array('encode' => null);
		$options += $defaults;

		$source = static::$_source ?: static::_source();
		$result = $source($bytes);

		if ($options['encode'] !== static::ENCODE_BASE_64) {
			return $result;
		}
		return strtr(rtrim(base64_encode($result), '='), '+', '.');
	}


	/**
	 * Initializes `Random::$_source` using the best available random number generator.
	 *
	 * When available, `/dev/urandom` and COM gets used on *nix and
	 * [Windows systems](http://msdn.microsoft.com/en-us/library/aa388182%28VS.85%29.aspx?ppud=4),
	 * respectively.
	 *
	 * If all else fails, a Mersenne Twister gets used. (Strictly
	 * speaking, this fallback is inadequate, but good enough.)
	 *
	 * Note: Users restricting path access through the `open_basedir` INI setting,
	 * will need to include `/dev/urandom` into the list of allowed paths, as this
	 * method might read from `/dev/urandom`.
	 *
	 * @see lithium\util\Random::$_source
	 * @return Closure Returns a closure containing a random number generator.
	 */
	protected static function _source() {
		switch (true) {
			case isset(static::$_source):
				return static::$_source;
			case is_readable('/dev/urandom') && $fp = fopen('/dev/urandom', 'rb'):
				return static::$_source = function($bytes) use (&$fp) {
					return fread($fp, $bytes);
				};
			case class_exists('COM', false):
				try {
					$com = new COM('CAPICOM.Utilities.1');
					return static::$_source = function($bytes) use ($com) {
						return base64_decode($com->GetRandom($bytes, 0));
					};
				} catch (Exception $e) {
				}
			default:
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