<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 *                Copyright 2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace lithium\security;

/**
 * Password utility class that makes use of PHP's `crypt()` function. Includes a
 * cryptographically strong salt generator, and utility functions to hash and check
 * passwords.
 */
class Password extends \lithium\security\Crypto {
	/**
	 * Hashes a password using PHP's `crypt()` and an optional salt. If no
	 * salt is supplied, a cryptographically strong salt will be generated
	 * using `lithium\security\Password::genSalt()`.
	 *
	 * Using this function is the proper way to hash a password. Using naive
	 * methods such as sha1 or md5, as is done in many web applications, is
	 * improper due to the lack of a cryptographically strong salt.
	 *
	 * Using `lithium\security\Password::hash()` ensures that:
	 *
	 * - Two identical passwords will never use the same salt, thus never
	 *   resulting in the same hash; this prevents a potential attacker from
	 *   compromising user accounts by using a database of most commonly used
	 *   passwords.
	 * - The salt generator's count interator can be increased within Lithium
	 *   or your application as computer hardware becomes faster; this results
	 *   in slower hash generation, without invalidating existing passwords.
	 *
	 * Usage:
	 *
	 * {{{
	 * // Hash a password before storing it:
	 * $hashed  = Password::hash($password);
	 *
	 * // Check a password by comparing it to its hashed value:
	 * $check   = Password::check($password, $hashed);
	 *
	 * // Use a stronger custom salt:
	 * $salt    = Password::genSalt('bf', 16); // 2^16 iterations
	 * $hashed  = Password::hash($password, $salt); // Very slow
	 * $check   = Password::check($password, $hashed); // Very slow
	 *
	 * // Forward/backward compatibility
	 * $salt1   = Password::genSalt('bf', 6);
	 * $salt2   = Password::genSalt('bf', 12);
	 * $hashed1 = Password::hash($password, $salt1); // Fast
	 * $hashed2 = Password::hash($password, $salt2); // Slow
	 * $check1  = Password::check($password, $hashed1); // True
	 * $check2  = Password::check($password, $hashed2); // True
	 * }}}
	 *
	 * @param string $password The password to hash.
	 * @param string $salt Optional. The salt string.
	 * @return string The hashed password.
	 *        The result's length will be:
	 *        - 60 chars long for Blowfish hashes
	 *        - 20 chars long for XDES hashes
	 *        - 34 chars long for MD5 hashes
	 * @see lithium\security\Password::check()
	 * @see lithium\security\Password::genSalt()
	 */
	public static function hash($password, $salt = null) {
		return crypt($password, $salt ?: static::genSalt());
	}

	/**
	 * Compares a password and its hashed value using PHP's `crypt()`.
	 *
	 * @param string $password The password to check
	 * @param string $hash The hashed password to compare it to
	 * @return boolean Whether the password is correct or not
	 * @see lithium\security\Password::hash()
	 * @see lithium\security\Password::genSalt()
	 */
	public static function check($password, $hash) {
		return $hash == crypt($password, $hash);
	}

	/**
	 * Generates a cryptographically strong salt, using the best available
	 * method (tries Blowfish, then XDES, and fallbacks to MD5), for use in
	 * `Password::hash()`.
	 *
	 * Blowfish and XDES are adaptive hashing algorithms. MD5 is not. Adaptive
	 * hashing algorithms are designed in such a way that when computers get
	 * faster, you can tune the algorithm to be slower by increasing the number
	 * of hash iterations, without introducing incompatibility with existing
	 * passwords.
	 *
	 * To pick an appropriate iteration count for adaptive algorithms, consider
	 * that the original DES crypt was designed to have the speed of 4 hashes
	 * per second on the hardware of that time. Slower than 4 hashes per second
	 * would probably dampen usability. Faster than 100 hashes per second is
	 * probably too fast. The defaults generate about 10 hashes per second
	 * using a dual-core 2.2GHz CPU.
	 *
	 * Note1: this salt generator is different from naive salt implementations
	 * (e.g. `md5(microtime())`) in that it uses all of the available bits of
	 * entropy for the supplied salt method.
	 *
	 * Note2: this method should not be use to generate custom salts. Indeed,
	 * the resulting salts are prefixed with information expected by PHP's
	 * `crypt()`. To get an arbitrarily long, cryptographically strong salt
	 * consisting in random sequences of alpha numeric characters, use
	 * `lithium\security\Crypto::random()` instead.
	 *
	 * @param string $type The hash type. Optional. Defaults to the best
	 *        available option. Supported values, along with their maximum
	 *        password lengths, include:
	 *        - `'bf'`: Blowfish (128 salt bits, max 72 chars)
	 *        - `'xdes'`: XDES (24 salt bits, max 8 chars)
	 *        - `'md5'`: MD5 (48 salt bits, unlimited length)
	 * @param integer $count Optional. The base-2 logarithm of the iteration
	 *        count, for adaptive algorithms. Defaults to:
	 *        - `10` for Blowfish
	 *        - `18` for XDES
	 * @return string The salt string.
	 * @link http://php.net/manual/en/function.crypt.php
	 * @link http://www.postgresql.org/docs/9.0/static/pgcrypto.html
	 * @see lithium\security\Password::hash()
	 * @see lithium\security\Password::check()
	 * @see lithium\security\Crypto::random()
	 */
	public static function genSalt($type = null, $count = null) {
		switch (true) {
			case CRYPT_BLOWFISH == 1 && (!$type || $type === 'bf'):
				return static::_genSaltBF($count);
			case CRYPT_EXT_DES == 1 && (!$type || $type === 'xdes'):
				return static::_genSaltXDES($count);
			default:
				return static::_genSaltMD5();
		}
	}

	/**
	 * Generates a Blowfish salt for use in `lithium\security\Password::hash()`.
	 *
	 * @param integer $count The base-2 logarithm of the iteration count.
	 *        Defaults to `10`. Can be `4` to `31`.
	 * @return string The Blowfish salt
	 */
	protected static function _genSaltBf($count = 10) {
		$count = (integer) $count;
		if ($count < 4 || $count > 31)
			$count = 10;

		// We don't use the encode64() method here because it could result
		// in 2 bits less of entropy depending on the last char.
		$base64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$i = 0;

		$input = static::random(16); // 128 bits of salt
		$output = '';

		do {
			$c1 = ord($input[$i++]);
			$output .= $base64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $base64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $base64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $base64[$c1];
			$output .= $base64[$c2 & 0x3f];
		} while (1);

		return '$2a$'
			// zeroize $count
			. chr(ord('0') + $count / 10) . chr(ord('0') + $count % 10)
			. '$' . $output;
	}

	/**
	 * Generates an Extended DES salt for use in `lithium\security\Password::hash()`.
	 *
	 * @param integer $count The base-2 logarithm of the iteration count.
	 *        Defaults to `18`. Can be `1` to `24`. 1 will be stripped
	 *        from the non-log value, e.g. 2^18 - 1, to ensure we don't
	 *        use a weak DES key.
	 * @return string The XDES salt.
	 */
	protected static function _genSaltXDES($count = 18) {
		$count = (integer) $count;
		if ($count < 1 || $count > 24)
			$count = 16;

		// Count should be odd to not reveal weak DES keys
		$count = (1 << $count) - 1;

		$base64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		$output = '_'
			// iterations
			. $base64[$count & 0x3f]
			. $base64[($count >> 6) & 0x3f]
			. $base64[($count >> 12) & 0x3f]
			. $base64[($count >> 18) & 0x3f]
			// 24 bits of salt
			. static::encode64(static::random(3));

		return $output;
	}

	/**
	 * Generates an MD5 salt for use in `lithium\security\Password::hash()`.
	 *
	 * @return string The MD5 salt.
	 */
	protected static function _genSaltMD5() {
		$output = '$1$'
			// 48 bits of salt
			. static::encode64(static::random(6));
		return $output;
	}
}

?>