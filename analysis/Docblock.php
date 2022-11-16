<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\analysis;

/**
 * A source code doc block parser.
 *
 * This parser may be used as the basis for a variety of secondary tools, including
 * a reflection-based API generator, a code metrics analyzer, and various other code or structural
 * analysis tools.
 */
class Docblock {

	/**
	 * List of supported docblock tags.
	 *
	 * @var array
	 */
	public static $tags = [
		'todo', 'discuss', 'fix', 'important', 'var',
		'param', 'return', 'throws', 'see', 'link',
		'task', 'dependencies', 'filter', 'deprecated'
	];

	/**
	 * Parses a doc block into its major components of `description`, `text` and `tags`.
	 *
	 * @param string $comment The doc block string to be parsed
	 * @return array An associative array of the parsed comment, whose keys are `description`,
	 *         `text` and `tags`.
	 */
	public static function comment($comment) {
		$text = null;
		$tags = [];
		$description = null;
		$comment = trim(preg_replace('/^(\s*\/\*\*|\s*\*{1,2}\/|\s*\* ?)/m', '', $comment));
		$comment = str_replace("\r\n", "\n", $comment);

		if ($items = preg_split('/\n@/ms', $comment, 2)) {
			list($description, $tags) = $items + ['', ''];
			$tags = $tags ? static::tags("@{$tags}") : [];
		}

		if (strpos($description, "\n\n")) {
			list($description, $text) = explode("\n\n", $description, 2);
		}
		$text = trim($text ?? "");
		$description = trim($description);
		return compact('description', 'text', 'tags');
	}

	/**
	 * Parses `@<tagname>` docblock tags and their descriptions from a docblock.
	 *
	 * See the `$tags` property for the list of supported tags.
	 *
	 * @param string $string The string to be parsed for tags
	 * @return array Returns an array where each docblock tag is a key name, and the corresponding
	 *         values are either strings (if one of each tag), or arrays (if multiple of the same
	 *         tag).
	 */
	public static function tags($string) {
		$regex = '/\n@(?P<type>' . join('|', static::$tags) . ")/msi";
		$string = trim($string ?? "");

		$result = preg_split($regex, "\n$string", -1, PREG_SPLIT_DELIM_CAPTURE);
		$tags = [];

		for ($i = 1; $i < count($result) - 1; $i += 2) {
			$type = trim(strtolower($result[$i]));
			$text = trim($result[$i + 1]);

			if (isset($tags[$type])) {
				$tags[$type] = is_array($tags[$type]) ? $tags[$type] : (array) $tags[$type];
				$tags[$type][] = $text;
			} else {
				$tags[$type] = $text;
			}
		}

		if (isset($tags['param'])) {
			$tags['params'] = static::_params((array) $tags['param']);
			unset($tags['param']);
		}
		return $tags;
	}

	/**
	 * Parses `@param` docblock tags to separate out the parameter type from the description.
	 *
	 * @param array $params An array of `@param` tags, as parsed from the `tags()` method.
	 * @return array Returns an array where each key is a parameter name, and each value is an
	 *         associative array containing `'type'` and `'text'` keys.
	 */
	protected static function _params(array $params) {
		$result = [];
		foreach ($params as $param) {
			$param = explode(' ', $param, 3);
			$type = $name = $text = null;

			foreach (['type', 'name', 'text'] as $i => $key) {
				if (!isset($param[$i])) {
					break;
				}
				${$key} = $param[$i];
			}
			if ($name) {
				$result[$name] = compact('type', 'text');
			}
		}
		return $result;
	}
}

?>