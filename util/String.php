<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\util;

use lithium\util\Text;
use lithium\security\Hash;
use lithium\security\Random;

$message  = "lithium\util\String has been deprecated in favor of ";
$message .= "lithium\util\Text and lithium\security\{Hash,Random}. ";
$message .= "The old class and methods continue to work and redirect calls ";
$message .= "to the new classes. However it is not possible to use the String ";
$message .= "class with PHP >=7.0.";
trigger_error($message, E_USER_DEPRECATED);

if (PHP_VERSION_ID < 70000) {
    class_alias('StringDeprecated', 'String');
}

/**
 * String manipulation utility class.
 *
 * @deprecated Replaced by `\lithium\util\Text` and `\lithium\security\{Hash,Random}`.
 */
class StringDeprecated {

	/**
	 * Option flag used in `String::random()`.
	 *
	 * @deprecated Use `lithium\util\Random::ENCODE_BASE_64`.
	 */
	const ENCODE_BASE_64 = 1;

	/**
	 * UUID-related constant. Clears all bits of version byte (`00001111`).
	 *
	 * @deprecated Use `lithium\util\Text::UUID_CLEAR_VER`.
	 */
	const UUID_CLEAR_VER = 15;

	/**
	 * UUID constant that sets the version bit for generated UUIDs (`01000000`).
	 *
	 * @deprecated Use `lithium\util\Text::UUID_VERSION_4`.
	 */
	const UUID_VERSION_4 = 64;

	/**
	 * Clears relevant bits of variant byte (`00111111`).
	 *
	 * @deprecated Use `lithium\util\Text::UUID_CLEAR_VAR`.
	 */
	const UUID_CLEAR_VAR = 63;

	/**
	 * The RFC 4122 variant (`10000000`).
	 *
	 * @deprecated Use `lithium\util\Text::UUID_CLEAR_RFC`.
	 */
	const UUID_VAR_RFC = 128;

	/**
	 * Generates random bytes for use in UUIDs and password salts, using
	 * (when available) a cryptographically strong random number generator.
	 *
	 * @deprecated Replaced by `lithium\security\Random::generate()`.
	 * @param integer $bytes The number of random bytes to generate.
	 * @param array $options
	 * @return string Returns a string of random bytes.
	 */
	public static function random($bytes, array $options = []) {
		$message  = "lithium\util\String::random() has been deprecated in favor of ";
		$message .= "lithium\security\Random::generate().";
		trigger_error($message, E_USER_DEPRECATED);
		return Random::generate($bytes, $options);
	}

	/**
	 * Uses PHP's hashing functions to create a hash of the string provided, using the options
	 * specified. The default hash algorithm is SHA-512.
	 *
	 * @deprecated Replaced by `lithium\security\Hash::calculate()`.
	 * @link http://php.net/function.hash.php PHP Manual: `hash()`
	 * @link http://php.net/function.hash-hmac.php PHP Manual: `hash_hmac()`
	 * @link http://php.net/function.hash-algos.php PHP Manual: `hash_algos()`
	 * @param string $string The string to hash.
	 * @param array $options
	 * @return string Returns a hashed string.
	 */
	public static function hash($string, array $options = []) {
		$message  = "lithium\util\String::hash() has been deprecated in favor of ";
		$message .= "lithium\security\Hash::calculate().";
		trigger_error($message, E_USER_DEPRECATED);

		return Hash::calculate($string, $options);
	}

	/**
	 * Compares two strings in constant time to prevent timing attacks.
	 *
	 * @deprecated Replaced by `lithium\security\Hash::compare()`.
	 * @param string $known The string of known length to compare against.
	 * @param string $user The user-supplied string.
	 * @return boolean Returns a boolean indicating whether the two strings are equal.
	 */
	public static function compare($known, $user) {
		$message  = "lithium\util\String::compare() has been deprecated in favor of ";
		$message .= "lithium\security\Hash::compare().";
		trigger_error($message, E_USER_DEPRECATED);

		return Hash::compare($known, $user);
	}

	/**
	 * Generates an RFC 4122-compliant version 4 UUID.
	 *
	 * @deprecated Replaced by `lithium\util\Text::uuid()`.
	 * @return string The string representation of an RFC 4122-compliant, version 4 UUID.
	 * @link http://www.ietf.org/rfc/rfc4122.txt RFC 4122: UUID URN Namespace
	 */
	public static function uuid() {
		$message  = "lithium\util\String::uuid() has been deprecated in favor of ";
		$message .= "lithium\util\Text::uuid().";
		trigger_error($message, E_USER_DEPRECATED);

		return Text::uuid();
	}

	/**
	 * Replaces variable placeholders inside a string with any given data. Each key
	 * in the `$data` array corresponds to a variable placeholder name in `$str`.
	 *
	 * @deprecated Replaced by `lithium\util\Text::insert()`.
	 * @param string $str A string containing variable place-holders.
	 * @param array $data A key, value array where each key stands for a place-holder variable
	 *                     name to be replaced with value.
	 * @param array $options
	 * @return string
	 */
	public static function insert($str, array $data, array $options = []) {
		$message  = "lithium\util\String::insert() has been deprecated in favor of ";
		$message .= "lithium\util\Text::insert().";
		trigger_error($message, E_USER_DEPRECATED);

		return Text::insert($str, $data, $options);
	}

	/**
	 * Cleans up a `String::insert()` formatted string with given `$options` depending
	 * on the `'clean'` option. The goal of this function is to replace all whitespace
	 * and unneeded mark-up around place-holders that did not get replaced by `String::insert()`.
	 *
	 * @deprecated Replaced by `lithium\util\Text::clean()`.
	 * @param string $str The string to clean.
	 * @param array $options
	 * @return string The cleaned string.
	 */
	public static function clean($str, array $options = []) {
		$message  = "lithium\util\String::clean() has been deprecated in favor of ";
		$message .= "lithium\util\Text::clean().";
		trigger_error($message, E_USER_DEPRECATED);

		return Text::clean($str, $options);
	}

	/**
	 * Extract a part of a string based on a regular expression `$regex`.
	 *
	 * @deprecated Replaced by `lithium\util\Text::extract()`.
	 * @param string $regex The regular expression to use.
	 * @param string $str The string to run the extraction on.
	 * @param integer $index The number of the part to return based on the regex.
	 * @return mixed
	 */
	public static function extract($regex, $str, $index = 0) {
		$message  = "lithium\util\String::extract() has been deprecated in favor of ";
		$message .= "lithium\util\Text::extract().";
		trigger_error($message, E_USER_DEPRECATED);

		return Text::extract($regex, $str, $index);
	}

	/**
	 * Tokenizes a string using `$options['separator']`, ignoring any instances of
	 * `$options['separator']` that appear between `$options['leftBound']` and
	 * `$options['rightBound']`.
	 *
	 * @deprecated Replaced by `lithium\util\Text::tokenize()`.
	 * @param string $data The data to tokenize.
	 * @param array $options
	 * @return array Returns an array of tokens.
	 */
	public static function tokenize($data, array $options = []) {
		$message  = "lithium\util\String::tokenize() has been deprecated in favor of ";
		$message .= "lithium\util\Text::tokenize().";
		trigger_error($message, E_USER_DEPRECATED);

		return Text::tokenize($data, $options);
	}
}

?>