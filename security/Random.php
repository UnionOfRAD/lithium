<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\security;

use COM;
use LogicException;

/**
 * A cryptographically-strong random number generator, which allows to generate arbritrary
 * length strings of bytes, usable for i.e. password salts, UUIDs, keys or initialization
 * vectors (IVs). Random byte strings can be encoded using the base64-encoder for use with
 * DES and XDES.
 */
class Random {

	/**
	 * Option flag for the encoder.
	 *
	 * @see lithium\security\Random::generate()
	 */
	const ENCODE_BASE_64 = 1;

	/**
	 * A callable which, given a number of bytes, returns that
	 * amount of random bytes.
	 *
	 * @see lithium\security\Random::_source()
	 * @var callable
	 */
	protected static $_source;

	/**
	 * Generates random bytes for use in UUIDs and password salts, using
	 * a cryptographically strong random number generator source.
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
	 *                resulting value will be base64-encoded, per the note above.
	 * @return string Returns (an encoded) string of random bytes.
	 */
	public static function generate($bytes, array $options = []) {
		$defaults = ['encode' => null];
		$options += $defaults;

		$source = static::$_source ?: (static::$_source = static::_source());
		$result = $source($bytes);

		if ($options['encode'] !== static::ENCODE_BASE_64) {
			return $result;
		}
		return strtr(rtrim(base64_encode($result), '='), '+', '.');
	}

	/**
	 * Returns the best available random number generator source.
	 *
	 * The source of randomness used are as follows:
	 *
	 * 1. `random_bytes()`, available in PHP >=7.0
	 * 2. `mcrypt_create_iv()`, available if the mcrypt extensions is installed
	 * 3. `/dev/urandom`, available on *nix
	 * 4. `GetRandom()` through COM, available on Windows
	 *
	 * Note: Users restricting path access through the `open_basedir` INI setting,
	 * will need to include `/dev/urandom` into the list of allowed paths, as this
	 * method might read from it.
	 *
	 * The `openssl_random_pseudo_bytes()` function is not used, as it is not clear
	 * under which circumstances it will not have a strong source available to it.
	 *
	 * @link http://php.net/random_bytes
	 * @link http://php.net/mcrypt_create_iv
	 * @link http://msdn.microsoft.com/en-us/library/aa388182%28VS.85%29.aspx?ppud=4
	 * @link http://sockpuppet.org/blog/2014/02/25/safely-generate-random-numbers/
	 * @see lithium\util\Random::$_source
	 * @return callable Returns a closure containing a random number generator.
	 */
	protected static function _source() {
		if (function_exists('random_bytes')) {
			return function($bytes) {
				return random_bytes($bytes);
			};
		}
		if (function_exists('mcrypt_create_iv')) {
			return function($bytes) {
				return mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
			};
		}
		if (is_readable('/dev/urandom')) {
			return function($bytes) {
				$stream = fopen('/dev/urandom', 'rb');
				$result = fread($stream, $bytes);
				fclose($stream);

				return $result;
			};
		}
		if (class_exists('COM', false)) {
			$com = new COM('CAPICOM.Utilities.1');

			return function($bytes) use ($com) {
				return base64_decode($com->GetRandom($bytes, 0));
			};
		}
		throw new LogicException('No suitable strong random number generator source found.');
	}
}

?>