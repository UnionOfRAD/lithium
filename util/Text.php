<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\util;

use lithium\security\Random;

/**
 * Text manipulation utility class. Includes functionality for generating UUIDs,
 * {:tag} and regex replacement, and tokenization.
 */
class Text {

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
	 * Generates an RFC 4122-compliant version 4 UUID.
	 *
	 * @return string The string representation of an RFC 4122-compliant, version 4 UUID.
	 * @link http://www.ietf.org/rfc/rfc4122.txt RFC 4122: UUID URN Namespace
	 */
	public static function uuid() {
		$uuid = Random::generate(16);
		$uuid[6] = chr(ord($uuid[6]) & static::UUID_CLEAR_VER | static::UUID_VERSION_4);
		$uuid[8] = chr(ord($uuid[8]) & static::UUID_CLEAR_VAR | static::UUID_VAR_RFC);

		return join('-', [
			bin2hex(substr($uuid, 0, 4)),
			bin2hex(substr($uuid, 4, 2)),
			bin2hex(substr($uuid, 6, 2)),
			bin2hex(substr($uuid, 8, 2)),
			bin2hex(substr($uuid, 10, 6))
		]);
	}

	/**
	 * Replaces variable placeholders inside a string with any given data. Each key
	 * in the `$data` array corresponds to a variable placeholder name in `$str`.
	 *
	 * Usage:
	 * ```
	 * Text::insert(
	 *     'My name is {:name} and I am {:age} years old.',
	 *     ['name' => 'Bob', 'age' => '65']
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
	 *        - `'clean'`: A boolean or array with instructions for `Text::clean()`.
	 *        - `'escape'`: The character or string used to escape the before character or string
	 *          (defaults to `'\'`).
	 *        - `'format'`: A regular expression to use for matching variable place-holders
	 *          (defaults to `'/(?<!\\)\:%s/'`. Please note that this option takes precedence over
	 *          all other options except `'clean'`.
	 * @return string
	 */
	public static function insert($str, array $data, array $options = []) {
		$defaults = [
			'before' => '{:',
			'after' => '}',
			'escape' => null,
			'format' => null,
			'clean' => false
		];
		$options += $defaults;
		$format = $options['format'];

		if ($format === 'regex' || (!$format && $options['escape'])) {
			$format = sprintf(
				'/(?<!%s)%s%%s%s/',
				preg_quote($options['escape'], '/'),
				str_replace('%', '%%', preg_quote($options['before'], '/')),
				str_replace('%', '%%', preg_quote($options['after'] ?? '', '/'))
			);
		}

		if (!$format && key($data) !== 0) {
			$replace = [];

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
	 * Cleans up a `Text::insert()` formatted string with given `$options` depending
	 * on the `'clean'` option. The goal of this function is to replace all whitespace
	 * and unneeded mark-up around place-holders that did not get replaced by `Text::insert()`.
	 *
	 * @param string $str The string to clean.
	 * @param array $options Available options are:
	 *        - `'after'`: characters marking the end of targeted substring.
	 *        - `'andText'`: (defaults to `true`).
	 *        - `'before'`: characters marking the start of targeted substring.
	 *        - `'clean'`: `true` or an array of clean options:
	 *          - `'gap'`: Regular expression matching gaps.
	 *          - `'method'`: Either `'text'` or `'html'` (defaults to `'text'`).
	 *          - `'replacement'`: Text to use for cleaned substrings (defaults to `''`).
	 *          - `'word'`: Regular expression matching words.
	 * @return string The cleaned string.
	 */
	public static function clean($str, array $options = []) {
		if (is_array($options['clean'])) {
			$clean = $options['clean'];
		} else {
			$clean = [
				'method' => is_bool($options['clean']) ? 'text' : $options['clean']
			];
		}

		switch ($clean['method']) {
			case 'text':
				$clean += [
					'word' => '[\w,.]+',
					'gap' => '[\s]*(?:(?:and|or|,)[\s]*)?',
					'replacement' => ''
				];
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
				$clean += [
					'word' => '[\w,.]+',
					'andText' => true,
					'replacement' => ''
				];
				$kleenex = sprintf(
					'/[\s]*[a-z]+=(")(%s%s%s[\s]*)+\\1/i',
					preg_quote($options['before'], '/'),
					$clean['word'],
					preg_quote($options['after'], '/')
				);
				$str = preg_replace($kleenex, $clean['replacement'], $str);

				if ($clean['andText']) {
					return static::clean($str, [
						'clean' => ['method' => 'text']
					] + $options);
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
	public static function tokenize($data, array $options = []) {
		$options += ['separator' => ',', 'leftBound' => '(', 'rightBound' => ')'];

		if (!$data || is_array($data)) {
			return $data;
		}

		$depth = 0;
		$offset = 0;
		$buffer = '';
		$results = [];
		$length = strlen($data);
		$open = false;

		while ($offset <= $length) {
			$tmpOffset = -1;
			$offsets = [
				strpos($data, $options['separator'], $offset),
				strpos($data, $options['leftBound'], $offset),
				strpos($data, $options['rightBound'], $offset)
			];

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
				$buffer .= $data[$tmpOffset];
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
		return $results ? array_map('trim', $results) : [];
	}
}

?>