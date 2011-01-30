<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 *                Copyright 2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace lithium\util;

use Closure;
use Exception;

/**
 * String manipulation utility class. Includes functionality for hashing, UUID generation,
 * {:tag} and regex replacement, and tokenization.
 *
 */
class String {
	/**
	 * UUID related constants
	 */
	const clearVer = 15;  // 00001111  Clears all bits of version byte
	const version4 = 64;  // 01000000  Sets the version bit
	const clearVar = 63;  // 00111111  Clears relevant bits of variant byte
	const varRFC   = 128; // 10000000  The RFC 4122 variant

	/**
	 * A file pointer towards urandom if available, else false
	 *
	 * @var resource|false
	 */
	protected static $_urandom;

	/**
	 * Seeds the random generator if it has yet to be done.
	 *
	 * @return boolean Success.
	 **/
	public static function seed() {
		// Seeding more than once means less entropy, not more, so bail
		if (isset(static::$_urandom)) {
			return false;
		}

		// Use urandom if the device is available
		if (is_readable('/dev/urandom')) {
			static::$_urandom = fopen('/dev/urandom', 'rb');
		// Else seed PHP's mt_rand()
		} else {
			$seed = function() {
				list($usec, $sec) = explode(' ', microtime());
				$seed = (float) $sec + ((float) $usec * 100000);
				if (function_exists('getmypid')) {
					$seed .= getmypid();
				}
				return $seed;
			};
			mt_srand($seed());
			static::$_urandom = false;
		}

		return true;
	}

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
	 * @param integer $bytes The number of bytes to generate
	 * @param string Random bytes
	 */
	public static function random($bytes) {
		if (!isset(static::$_urandom)) {
			static::seed();
		}

		if (static::$_urandom) {
			$rand = fread(static::$_urandom, $bytes);
		} else {
			$rand = '';
			for ($i = 0; $i < $bytes; $i++) {
				$rand .= chr(mt_rand(0, 255));
			}
		}

		return $rand;
	}

	/**
	 * Generates an RFC 4122-compliant version 4 UUID.
	 *
	 * @return string The string representation of an RFC 4122-compliant, version 4 UUID.
	 * @link http://www.ietf.org/rfc/rfc4122.txt
	 */
	public static function uuid() {
		$uuid = static::random(16);

		// Set version
		$uuid[6] = chr(ord($uuid[6]) & static::clearVer | static::version4);

		// Set variant
		$uuid[8] = chr(ord($uuid[8]) & static::clearVar | static::varRFC);

		// Return the uuid's string representation
		return bin2hex(substr($uuid, 0, 4)) . '-'
			. bin2hex(substr($uuid, 4, 2)) . '-'
			. bin2hex(substr($uuid, 6, 2)) . '-'
			. bin2hex(substr($uuid, 8, 2)) . '-'
			. bin2hex(substr($uuid, 10, 6));
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

	/**
	 * Hashes a password using PHP's `crypt()` and an optional salt. If no
	 * salt is supplied, a cryptographically strong salt will be generated
	 * using `String::genSalt()`.
	 *
	 * Using this function is the proper way to hash a password. Using naive
	 * methods such as `String::hash()` is fine to check a file's integrity,
	 * but fundamentally insecure for passwords, due to the invariable lack
	 * of a cryptographically strong salt.
	 *
	 * Moreover, `String::hashPassword()`'s cryptographically strong salts
	 * ensure that:
	 *
	 * - Two identical passwords will not be hashed the same way.
	 * - `String::genSalt()`'s count interator can later be increased (assuming
	 *   BF or XDES is available) within Lithium or your application, without
	 *   invalidating existing password hashes.
	 *
	 * Usage:
	 *
	 * {{{
	 * // Hash a password before storing it:
	 * $hashed  = String::hashPassword($password);
	 *
	 * // Check a password by comparing it to its hashed value:
	 * $check   = String::checkPassword($password, $hashed);
	 *
	 * // Use a stronger custom salt:
	 * $salt    = String::genSalt('bf', 16); // 2^16 iterations
	 * $hashed  = String::hashPassword($password, $salt); // Very slow
	 * $check   = String::checkPassword($password, $hashed); // Very slow
	 *
	 * // Forward/backward compatibility
	 * $salt1   = String::genSalt('bf', 6);
	 * $salt2   = String::genSalt('bf', 12);
	 * $hashed1 = String::hashPassword($password, $salt1); // Fast
	 * $hashed2 = String::hashPassword($password, $salt2); // Slow
	 * $check1  = String::checkPassword($password, $hashed1); // True
	 * $check2  = String::checkPassword($password, $hashed2); // True
	 * }}}
	 *
	 * @see lithium\util\String::genSalt()
	 * @param string $password The password to hash.
	 * @param string $salt Optional. The salt string.
	 * @return string The hashed password.
	 *        The result's length will be:
	 *        - 60 chars for Blowfish hashes
	 *        - 20 chars for XDES hashes
	 *        - 34 chars for MD5 hashes
	 **/
	public static function hashPassword($password, $salt = null) {
		return crypt($password, $salt ?: static::genSalt());
	}

	/**
	 * Compares a password and its hashed value using PHP's `crypt()`.
	 *
	 * @see lithium\util\String::hashPassword()
	 * @see lithium\util\String::genSalt()
	 * @param string $password The password to check
	 * @param string $hash The hashed password to compare
	 * @return boolean Whether the password is correct or not
	 **/
	public static function checkPassword($password, $hash) {
		return $hash == crypt($password, $hash);
	}

	/**
	 * Generates a cryptographically strong salt, using the best available
	 * method (tries Blowfish, then XDES, and fallbacks to MD5), for use in
	 * `String::hashPassword()`.
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
	 * (e.g. `md5(microtime())`) that are invariably found in OSS PHP applications,
	 * in that it uses all of the available bits of entropy for the supplied salt
	 * method.
	 *
	 * Note2: this method should not be used as custom salts, for instance in a
	 * custom password hasher. Indeed, salts are prefixed with information expected
	 * by PHP's `crypt()`. To get an arbitrarily long, cryptographically strong salt
	 * consisting in random sequences of alpha numeric characters, combine
	 * `String::random()` and `String::encode64()` instead.
	 *
	 * @link http://php.net/manual/en/function.crypt.php
	 * @link http://www.postgresql.org/docs/9.0/static/pgcrypto.html
	 * @see lithium\util\String::hashPassword()
	 * @param string $type The hash type. Optional. Defaults to '`bf`'.
	 *        Supported values include:
	 *        - `'bf'`: Blowfish (128 salt bits, adaptive, max 72 chars)
	 *        - `'xdes'`: XDES (24 salt bits, adaptive, max 8 chars)
	 *        - `'md5'`: MD5 (48 salt bits, non-adaptive, unlimited length)
	 * @param integer $count Optional. The base-2 logarithm of the iteration
	 *        count, for adaptive algorithms. Defaults to:
	 *        - `10` for Blowfish
	 *        - `18` for XDES
	 * @return string The salt string.
	 **/
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
	 * Encodes bytes into an `./0-9A-Za-z` alphabet, for use as salt when
	 * hashing passwords.
	 *
	 * Note: this is not the same as RFC 1421, or `base64_encode()`, which
	 * uses an `+/0-9A-Za-z` alphabet.
	 *
	 * This function can be combined with `String::random()` to generate random
	 * sequences of `./0-9A-Za-z` characters:
	 *
	 * {{{
	 * $salt = String::encode64(String::random(8)); // 64 bits
	 * }}}
	 *
	 * @see lithium\util\String::random()
	 * @param string $input The input bytes.
	 * @return string The same bytes in the `/.0-9A-Za-z` alphabet.
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
	 * Generates a Blowfish salt for use in `String::hashPassword()`.
	 *
	 * @param integer $count The base-2 logarithm of the iteration count.
	 *        Defaults to `10`. Can be `4` to `31`.
	 * @return string $salt
	 **/
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
	 * Generates an Extended DES salt for use in `String::hashPassword()`.
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
	 * Generates an MD5 salt for use in `String::hashPassword()`.
	 *
	 * @return string The MD5 salt.
	 **/
	protected static function _genSaltMD5() {
		$output = '$1$'
			// 48 bits of salt
			. static::encode64(static::random(6));
		return $output;
	}

	/**
	 * Replaces variable placeholders inside a string with any given data. Each key
	 * in the `$data` array corresponds to a variable placeholder name in `$str`.
	 *
	 * Usage:
	 * {{{
	 * String::insert(
	 *     'My name is {:name} and I am {:age} years old.',
	 *     array('name' => 'Bob', 'age' => '65')
	 * ); // returns 'My name is Bob and I am 65 years old.'
	 * }}}
	 *
	 * @param string $str A string containing variable place-holders.
	 * @param string $data A key, value array where each key stands for a place-holder variable
	 *                     name to be replaced with value.
	 * @param string $options Available options are:
	 *        - `'after'`: The character or string after the name of the variable place-holder
	 *          (defaults to `null`).
	 *        - `'before'`: The character or string in front of the name of the variable
	 *          place-holder (defaults to `':'`).
	 *        - `'clean'`: A boolean or array with instructions for `String::clean()`.
	 *        - `'escape'`: The character or string used to escape the before character or string
	 *          (defaults to `'\'`).
	 *        - `'format'`: A regular expression to use for matching variable place-holders
	 *          (defaults to `'/(?<!\\)\:%s/'`. Please note that this option takes precedence over
	 *          all other options except `'clean'`.
	 * @return string
	 * @todo Optimize this
	 */
	public static function insert($str, array $data, array $options = array()) {
		$defaults = array(
			'before' => '{:',
			'after' => '}',
			'escape' => null,
			'format' => null,
			'clean' => false,
		);
		$options += $defaults;
		$format = $options['format'];
		reset($data);

		if ($format == 'regex' || (empty($format) && !empty($options['escape']))) {
			$format = sprintf(
				'/(?<!%s)%s%%s%s/',
				preg_quote($options['escape'], '/'),
				str_replace('%', '%%', preg_quote($options['before'], '/')),
				str_replace('%', '%%', preg_quote($options['after'], '/'))
			);
		}

		if (empty($format) && key($data) !== 0) {
			$replace = array();

			foreach ($data as $key => $value) {
				$replace["{$options['before']}{$key}{$options['after']}"] = $value;
			}
			$str = strtr($str, $replace);
			return $options['clean'] ? static::clean($str, $options) : $str;
		}

		if (strpos($str, '?') !== false && isset($data[0])) {
			$offset = 0;
			while (($pos = strpos($str, '?', $offset)) !== false) {
				$val = array_shift($data);
				$offset = $pos + strlen($val);
				$str = substr_replace($str, $val, $pos, 1);
			}
			return $options['clean'] ? static::clean($str, $options) : $str;
		}

		foreach ($data as $key => $value) {
			$hashVal = crc32($key);
			$key = sprintf($format, preg_quote($key, '/'));

			if (!$key) {
				continue;
			}
			$str = preg_replace($key, $hashVal, $str);

			if (is_object($value) && !$value instanceof Closure) {
				try {
					$value = $value->__toString();
				} catch (Exception $e) {
					$value = '';
				}
			}
			if (!is_array($value)) {
				$str = str_replace($hashVal, $value, $str);
			}
		}

		if (!isset($options['format']) && isset($options['before'])) {
			$str = str_replace($options['escape'] . $options['before'], $options['before'], $str);
		}
		return $options['clean'] ? static::clean($str, $options) : $str;
	}

	/**
	 * Cleans up a `Set::insert()` formatted string with given `$options` depending
	 * on the `'clean'` option. The goal of this function is to replace all whitespace
	 * and unneeded mark-up around place-holders that did not get replaced by `Set::insert()`.
	 *
	 * @param string $str The string to clean.
	 * @param string $options Available options are:
	 *        - `'after'`: characters marking the end of targeted substring.
	 *        - `'andText'`: (defaults to `true`).
	 *        - `'before'`: characters marking the start of targeted substring.
	 *        - `'clean'`: `true` or an array of clean options:
	 *        - `'gap'`: Regular expression matching gaps.
	 *        - `'method'`: Either `'text'` or `'html'` (defaults to `'text'`).
	 *        - `'replacement'`: String to use for cleaned substrings (defaults to `''`).
	 *        - `'word'`: Regular expression matching words.
	 * @return string The cleaned string.
	 */
	public static function clean($str, array $options = array()) {
		if (!$options['clean']) {
			return $str;
		}
		$clean = $options['clean'];
		$clean = ($clean === true) ? array('method' => 'text') : $clean;
		$clean = (!is_array($clean)) ? array('method' => $options['clean']) : $clean;

		switch ($clean['method']) {
			case 'html':
				$clean += array('word' => '[\w,.]+', 'andText' => true, 'replacement' => '');
				$kleenex = sprintf(
					'/[\s]*[a-z]+=(")(%s%s%s[\s]*)+\\1/i',
					preg_quote($options['before'], '/'),
					$clean['word'],
					preg_quote($options['after'], '/')
				);
				$str = preg_replace($kleenex, $clean['replacement'], $str);

				if ($clean['andText']) {
					$options['clean'] = array('method' => 'text');
					$str = static::clean($str, $options);
				}
			break;
			case 'text':
				$clean += array(
					'word' => '[\w,.]+', 'gap' => '[\s]*(?:(?:and|or|,)[\s]*)?', 'replacement' => ''
				);
				$before = preg_quote($options['before'], '/');
				$after = preg_quote($options['after'], '/');

				$kleenex = sprintf(
					'/(%s%s%s%s|%s%s%s%s|%s%s%s%s%s)/',
					$before, $clean['word'], $after, $clean['gap'],
					$clean['gap'], $before, $clean['word'], $after,
					$clean['gap'], $before, $clean['word'], $after, $clean['gap']
				);
				$str = preg_replace($kleenex, $clean['replacement'], $str);
			break;
		}
		return $str;
	}

	/**
	 * Extract a part of a string based on a regular expression `$regex`.
	 *
	 * @param string $regex The regular expression to use.
	 * @param string $str The string to run the extraction on.
	 * @param integer $index The number of the part to return based on the regex.
	 * @return mixed
	 */
	public static function extract($regex, $str, $index = 0) {
		if (!preg_match($regex, $str, $match)) {
			return false;
		}
		return isset($match[$index]) ? $match[$index] : null;
	}

	/**
	 * Tokenizes a string using `$options['separator']`, ignoring any instance of
	 * `$options['separator']` that appears between `$options['leftBound']` and
	 * `$options['rightBound']`.
	 *
	 * @param string $data The data to tokenize.
	 * @param array $options Options to use when tokenizing:
	 *              -`'separator'` _string_: The token to split the data on.
	 *              -`'leftBound'` _string_: Left scope-enclosing boundary.
	 *              -`'rightBound'` _string_: Right scope-enclosing boundary.
	 * @return array Returns an array of tokens.
	 */
	public static function tokenize($data, array $options = array()) {
		$defaults = array('separator' => ',', 'leftBound' => '(', 'rightBound' => ')');
		extract($options + $defaults);

		if (empty($data) || is_array($data)) {
			return $data;
		}

		$depth = 0;
		$offset = 0;
		$buffer = '';
		$results = array();
		$length = strlen($data);
		$open = false;

		while ($offset <= $length) {
			$tmpOffset = -1;
			$offsets = array(
				strpos($data, $separator, $offset),
				strpos($data, $leftBound, $offset),
				strpos($data, $rightBound, $offset)
			);
			for ($i = 0; $i < 3; $i++) {
				if ($offsets[$i] !== false && ($offsets[$i] < $tmpOffset || $tmpOffset == -1)) {
					$tmpOffset = $offsets[$i];
				}
			}
			if ($tmpOffset !== -1) {
				$buffer .= substr($data, $offset, ($tmpOffset - $offset));
				if ($data{$tmpOffset} == $separator && $depth == 0) {
					$results[] = $buffer;
					$buffer = '';
				} else {
					$buffer .= $data{$tmpOffset};
				}
				if ($leftBound != $rightBound) {
					if ($data{$tmpOffset} == $leftBound) {
						$depth++;
					}
					if ($data{$tmpOffset} == $rightBound) {
						$depth--;
					}
				} else {
					if ($data{$tmpOffset} == $leftBound) {
						if (!$open) {
							$depth++;
							$open = true;
						} else {
							$depth--;
							$open = false;
						}
					}
				}
				$offset = ++$tmpOffset;
			} else {
				$results[] = $buffer . substr($data, $offset);
				$offset = $length + 1;
			}
		}

		if (empty($results) && !empty($buffer)) {
			$results[] = $buffer;
		}
		return empty($results) ? array() : array_map('trim', $results);
	}
}

?>