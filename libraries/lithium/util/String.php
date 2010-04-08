<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 *                Copyright 2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace lithium\util;

use \Closure;
use \Exception;

/**
 * String manipulation utility class. Includes functionality for hashing, UUID generation,
 * {:tag} and regex replacement, and tokenization.
 *
 */
class String {

	/**
	 * Generates a random UUID.
	 *
	 * @param mixed $context Used to determine the values for `'SERVER_ADDR'`, `'HOST'`
	 *        and `'HOSTNAME'`. Either a closure which is passed the requested context values, an
	 *        object with properties for each value or an array keyed by requested context value.
	 * @return string An RFC 4122-compliant UUID.
	 * @link http://www.ietf.org/rfc/rfc4122.txt
	 */
	public static function uuid($context) {
		$val = function($value) use ($context) {
			switch (true) {
				case is_object($context) && is_callable($context):
					$result = $context($value);
				break;
				case is_object($context):
					$result = isset($context->$value) ? $context->$value : null;
				break;
				case is_array($context):
					$result = isset($context[$value]) ? $context[$value] : null;
				break;
			}
			return $result;
		};

		$node = static::_hostname($val);
		$pid = function_exists('zend_thread_id') ? zend_thread_id() : getmypid();
		$pid = (!$pid || $pid > 65535) ? mt_rand(0, 0xfff) | 0x4000 : $pid;
		list($timeMid, $timeLow) = explode(' ', microtime());

		return sprintf(
			"%08x-%04x-%04x-%02x%02x-%04x%08x",
			(integer) $timeLow, (integer) substr($timeMid, 2) & 0xffff, mt_rand(0, 0xfff) | 0x4000,
			mt_rand(0, 0x3f) | 0x80, mt_rand(0, 0xff), $pid, $node
		);
	}

	/**
	 * Create a hash from string using given method.  Fallback on next available method.
	 *
	 * @param string $string String to hash.
	 * @param string $type Method to use (sha1/sha256/md5, or any method supported
	 *        by the `hash()` function).
	 * @param string $salt
	 * @return string Hash.
	 */
	public static function hash($string, $type = 'sha1', $salt = null) {
		$string = $salt . $string;

		switch (true) {
			case ($type == 'sha1' && function_exists('sha1')):
				return sha1($string);
			case ($type == 'sha256' && function_exists('mhash')):
				return bin2hex(mhash(MHASH_SHA256, $string));
			case (function_exists('hash')):
				return hash($type, $string);
			default:
		}
		return md5($string);
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
	 *        - `'before'`: The character or string in front of the name of the variable place-holder
	 *          (defaults to `':'`).
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
			'clean' => false
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

	/**
	 * Used by `String::uuid()` to get the hostname from request context data. Uses fallbacks to get
	 * the current host name or IP, depending on what values are available.
	 *
	 * @param mixed $context An array (i.e. `$_SERVER`), `Request` object, or anonymous function
	 *              containing host data.
	 * @return string Returns the host name or IP for use in generating a UUID.
	 */
	protected static function _hostname($context) {
		$node = $context('SERVER_ADDR');

		if (strpos($node, ':') !== false) {
			if (substr_count($node, '::')) {
				$pad = str_repeat(':0000', 8 - substr_count($node, ':'));
				$node = str_replace('::', $pad . ':', $node);
			}
			$node = explode(':', $node);
			$ipv6 = '';

			foreach ($node as $id) {
				$ipv6 .= str_pad(base_convert($id, 16, 2), 16, 0, STR_PAD_LEFT);
			}
			$node = base_convert($ipv6, 2, 10);
			$node = (strlen($node) < 38) ? null : crc32($node);
		} elseif (empty($node)) {
			$host = $context('HOSTNAME');
			$host = $host ?: $context('HOST');

			if (!empty($host)) {
				$ip = gethostbyname($host);
				$node = ($ip === $host) ? crc32($host) : $node = ip2long($ip);
			}
		} elseif ($node !== '127.0.0.1') {
			$node = ip2long($node);
		} else {
			$node = crc32(rand());
		}
		return $node;
	}
}

?>