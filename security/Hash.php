<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\security;

use Closure;

/**
 * Hash utility class. Contains methods to calculate hashes and compare
 * them in a safe way preventing timing attacks.
 */
class Hash {

	/**
	 * Uses PHP's hashing functions to calculate a hash over the data provided, using the options
	 * specified. The default hash algorithm is SHA-512.
	 *
	 * Examples:
	 * ```
	 * // Calculates a secure hash over string `'foo'`.
	 * Hash::calculate('foo');
	 *
	 * // It is possible to hash non-scalar data, too.
	 * Hash::calculate(['foo' => 'bar']);             // serializes before hashing
	 * Hash::calculate(new Foo());                    // -- " --
	 * Hash::calculate(function() { return 'bar'; }); // uses `spl_object_hash()`
	 *
	 * // Also allows for quick - non secure - hashing of arbitrary data.
	 * Hash::calculate(['foo' => 'bar'], ['type' => 'crc32b']);
	 * ```
	 *
	 * @link http://php.net/hash
	 * @link http://php.net/hash_hmac
	 * @link http://php.net/hash_algos
	 * @param mixed $data The arbitrary data to hash. Non-scalar data will be serialized, before
	 *        being hashed. For anonymous functions the object hash will be used.
	 * @param array $options Supported options:
	 *        - `'type'` _string_: Any valid hashing algorithm. See the `hash_algos()` function to
	 *          determine which are available on your system.
	 *        - `'salt'` _string_: A _salt_ value which, if specified, will be prepended to the
	 *          data.
	 *        - `'key'` _string_: If specified generates a keyed hash using `hash_hmac()`
	 *          instead of `hash()`, with `'key'` being used as the message key.
	 *        - `'raw'` _boolean_: If `true`, outputs the raw binary result of the hash operation.
	 *          Defaults to `false`.
	 * @return string Returns a hash calculated over given data.
	 */
	public static function calculate($data, array $options = []) {
		if (!is_scalar($data)) {
			$data = ($data instanceof Closure) ? spl_object_hash($data) : serialize($data);
		}
		$defaults = [
			'type' => 'sha512',
			'salt' => false,
			'key' => false,
			'raw' => false
		];
		$options += $defaults;

		if ($options['salt']) {
			$data = "{$options['salt']}{$data}";
		}
		if ($options['key']) {
			return hash_hmac($options['type'], $data, $options['key'], $options['raw']);
		}
		return hash($options['type'], $data, $options['raw']);
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
			$result |= ord($known[$i]) ^ ord($user[$i]);
		}
		return $result === 0;
	}
}

?>