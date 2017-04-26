<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace lithium\util;

use COM;
use Exception;

/**
 * String manipulation utility class. Includes functionality for generating UUIDs,
 * {:tag} and regex replacement, and tokenization. Also includes a cryptographically-strong random
 * number generator, and a base64 encoder for use with DES and XDES.
 */
class String {

	/**
	 * UUID-related constant. Clears all bits of version byte (`00001111`).
	 */
	const UUID_CLEAR_VER = 15;

	/**
	 * UUID constant that sets the version bit for generated UUIDs (`01000000`).
	 */
	const UUID_VERSION_4 = 64;

	/**
	 * Clears relevant bits of variant byte (`00111111`).
	 */
	const UUID_CLEAR_VAR = 63;

	/**
	 * The RFC 4122 variant (`10000000`).
	 */
	const UUID_VAR_RFC = 128;

	/**
	 * Option flag used in `String::random()`.
	 */
	const ENCODE_BASE_64 = 1;

	/**
	 * A closure which, given a number of bytes, returns that amount of
	 * random bytes.
	 *
	 * @var \Closure
	 */
	protected static $_source;

	/**
	 * Generates an RFC 4122-compliant version 4 UUID.
	 *
	 * @return string The string representation of an RFC 4122-compliant, version 4 UUID.
	 * @link http://www.ietf.org/rfc/rfc4122.txt RFC 4122: UUID URN Namespace
	 */
	public static function uuid() {
		$uuid = static::random(16);
		$uuid[6] = chr(ord($uuid[6]) & static::UUID_CLEAR_VER | static::UUID_VERSION_4);
		$uuid[8] = chr(ord($uuid[8]) & static::UUID_CLEAR_VAR | static::UUID_VAR_RFC);

		return join('-', array(
			bin2hex(substr($uuid, 0, 4)),
			bin2hex(substr($uuid, 4, 2)),
			bin2hex(substr($uuid, 6, 2)),
			bin2hex(substr($uuid, 8, 2)),
			bin2hex(substr($uuid, 10, 6))
		));
	}

	/**
	 * Generates random bytes for use in UUIDs and password salts, using
	 * (when available) a cryptographically strong random number generator.
	 *
	 * ```
	 * $bits = String::random(8); // 64 bits
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
	 *              - `'encode'` _integer_: If specified, and set to `String::ENCODE_BASE_64`, the
	 *                resulting value will be base64-encoded, per the notes above.
	 * @return string Returns a string of random bytes.
	 */
	public static function random($bytes, array $options = array()) {
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
	 * Initializes `String::$_source` using the best available random number generator.
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
	 * @see lithium\util\String::$_source
	 * @return \Closure Returns a closure containing a random number generator.
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

	/**
	 * Uses PHP's hashing functions to create a hash of the string provided, using the options
	 * specified. The default hash algorithm is SHA-512.
	 *
	 * @link http://php.net/function.hash.php PHP Manual: `hash()`
	 * @link http://php.net/function.hash-hmac.php PHP Manual: `hash_hmac()`
	 * @link http://php.net/function.hash-algos.php PHP Manual: `hash_algos()`
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
	 * Compares two strings in constant time to prevent timing attacks.
	 *
	 * To successfully mitigate timing attacks and not leak the actual length of the known
	 * string, it is important that _both provided strings have the same length_ and that
	 * the _user-supplied string is passed as a second parameter_ rather than first.
	 *
	 * This function has the same signature and behavior as the native `hash_equals()` function
	 * and will use that function if available (PHP >= 5.6).
	 *
	 * An E_USER_WARNING will be emitted when either of the supplied parameters is not a string.
	 *
	 * @link http://php.net/hash_equals
	 * @link http://codahale.com/a-lesson-in-timing-attacks/ More about timing attacks.
	 * @param string $known The string of known length to compare against.
	 * @param string $user The user-supplied string.
	 * @return boolean Returns a boolean indicating whether the two strings are equal.
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

	/**
	 * Replaces variable placeholders inside a string with any given data. Each key
	 * in the `$data` array corresponds to a variable placeholder name in `$str`.
	 *
	 * Usage:
	 * ```
	 * String::insert(
	 *     'My name is {:name} and I am {:age} years old.',
	 *     array('name' => 'Bob', 'age' => '65')
	 * ); // returns 'My name is Bob and I am 65 years old.'
	 * ```
	 *
	 * Please note that optimization have applied to this method and parts of the code
	 * may look like it can refactored or removed but in fact this is part of the applied
	 * optimization. Please check the history for this section of code before refactoring
	 *
	 * @param string $str A string containing variable place-holders.
	 * @param array $data A key, value array where each key stands for a place-holder variable
	 *                     name to be replaced with value.
	 * @param array $options Available options are:
	 *        - `'after'`: The character or string after the name of the variable place-holder
	 *          (defaults to `}`).
	 *        - `'before'`: The character or string in front of the name of the variable
	 *          place-holder (defaults to `'{:'`).
	 *        - `'clean'`: A boolean or array with instructions for `String::clean()`.
	 *        - `'escape'`: The character or string used to escape the before character or string
	 *          (defaults to `'\'`).
	 *        - `'format'`: A regular expression to use for matching variable place-holders
	 *          (defaults to `'/(?<!\\)\:%s/'`. Please note that this option takes precedence over
	 *          all other options except `'clean'`.
	 * @return string
	 */
	public static function insert($str, array $data, array $options = array()) {
		$defaults = array(
			'before' => '{:',
			'after' => '}',
			'escape' => null,
			'format' => null,
			'clean' => false
		);
		$options += $defaults;
		$format = $options['format'];

		if ($format === 'regex' || (!$format && $options['escape'])) {
			$format = sprintf(
				'/(?<!%s)%s%%s%s/',
				preg_quote($options['escape'], '/'),
				str_replace('%', '%%', preg_quote($options['before'], '/')),
				str_replace('%', '%%', preg_quote($options['after'], '/'))
			);
		}

		if (!$format && key($data) !== 0) {
			$replace = array();

			foreach ($data as $key => $value) {
				if (!is_scalar($value)) {
					if (is_object($value) && method_exists($value, '__toString')) {
						$value = (string) $value;
					} else {
						$value = '';
					}
				}
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
			if (!$key = sprintf($format, preg_quote($key, '/'))) {
				continue;
			}
			$hash = crc32($key);

			$str = preg_replace($key, $hash, $str);
			$str = str_replace($hash, $value, $str);
		}

		if (!isset($options['format']) && isset($options['before'])) {
			$str = str_replace($options['escape'] . $options['before'], $options['before'], $str);
		}
		return $options['clean'] ? static::clean($str, $options) : $str;
	}

	/**
	 * Cleans up a `String::insert()` formatted string with given `$options` depending
	 * on the `'clean'` option. The goal of this function is to replace all whitespace
	 * and unneeded mark-up around place-holders that did not get replaced by `String::insert()`.
	 *
	 * @param string $str The string to clean.
	 * @param array $options Available options are:
	 *        - `'after'`: characters marking the end of targeted substring.
	 *        - `'andText'`: (defaults to `true`).
	 *        - `'before'`: characters marking the start of targeted substring.
	 *        - `'clean'`: `true` or an array of clean options:
	 *          - `'gap'`: Regular expression matching gaps.
	 *          - `'method'`: Either `'text'` or `'html'` (defaults to `'text'`).
	 *          - `'replacement'`: String to use for cleaned substrings (defaults to `''`).
	 *          - `'word'`: Regular expression matching words.
	 * @return string The cleaned string.
	 */
	public static function clean($str, array $options = array()) {
		if (is_array($options['clean'])) {
			$clean = $options['clean'];
		} else {
			$clean = array(
				'method' => is_bool($options['clean']) ? 'text' : $options['clean']
			);
		}

		switch ($clean['method']) {
			case 'text':
				$clean += array(
					'word' => '[\w,.]+',
					'gap' => '[\s]*(?:(?:and|or|,)[\s]*)?',
					'replacement' => ''
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
			case 'html':
				$clean += array(
					'word' => '[\w,.]+',
					'andText' => true,
					'replacement' => ''
				);
				$kleenex = sprintf(
					'/[\s]*[a-z]+=(")(%s%s%s[\s]*)+\\1/i',
					preg_quote($options['before'], '/'),
					$clean['word'],
					preg_quote($options['after'], '/')
				);
				$str = preg_replace($kleenex, $clean['replacement'], $str);

				if ($clean['andText']) {
					return static::clean($str, array(
						'clean' => array('method' => 'text')
					) + $options);
				}
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
	 * Tokenizes a string using `$options['separator']`, ignoring any instances of
	 * `$options['separator']` that appear between `$options['leftBound']` and
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
		$options += array('separator' => ',', 'leftBound' => '(', 'rightBound' => ')');

		if (!$data || is_array($data)) {
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
				strpos($data, $options['separator'], $offset),
				strpos($data, $options['leftBound'], $offset),
				strpos($data, $options['rightBound'], $offset)
			);

			for ($i = 0; $i < 3; $i++) {
				if ($offsets[$i] !== false && ($offsets[$i] < $tmpOffset || $tmpOffset === -1)) {
					$tmpOffset = $offsets[$i];
				}
			}

			if ($tmpOffset === -1) {
				$results[] = $buffer . substr($data, $offset);
				$offset = $length + 1;
				continue;
			}
			$buffer .= substr($data, $offset, ($tmpOffset - $offset));

			if ($data[$tmpOffset] === $options['separator'] && $depth === 0) {
				$results[] = $buffer;
				$buffer = '';
			} else {
				$buffer .= $data{$tmpOffset};
			}

			if ($options['leftBound'] !== $options['rightBound']) {
				if ($data[$tmpOffset] === $options['leftBound']) {
					$depth++;
				}
				if ($data[$tmpOffset] === $options['rightBound']) {
					$depth--;
				}
				$offset = ++$tmpOffset;
				continue;
			}

			if ($data[$tmpOffset] === $options['leftBound']) {
				($open) ? $depth-- : $depth++;
				$open = !$open;
			}
			$offset = ++$tmpOffset;
		}

		if (!$results && $buffer) {
			$results[] = $buffer;
		}
		return $results ? array_map('trim', $results) : array();
	}
}

?>