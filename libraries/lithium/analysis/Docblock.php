<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\analysis;

/**
 * A source code doc block parser.
 *
 * This parser may be used as the basis for a variety of secondary tools, including
 * a reflection-based API generator, a code metrics analyzer, and various other possible
 * use cases.
 */
class Docblock extends \lithium\core\StaticObject {

	/**
	 * Parses a doc block into its major components of `description`, `text` and `tags`.
	 *
	 * @param string $description The doc block string to be parsed
	 * @return array An associative array of the parsed comment, whose keys are `description`,
	 *         `text` and `tags`
	 * @todo Implement text
	 */
	public static function comment($description) {
		$text = null;
		$tags = array();
		$description = trim(preg_replace('/^(\s*\/\*\*|\s*\*\/|\s*\* ?)/m', '', $description));

		if (!(preg_match_all('/\n@(\w+)\s+/', $description, $tagNames))) {
			return compact('description', 'text', 'tags');
		}
		$tagContents = preg_split('/\n@\w+\s+/ms', $description);

		foreach (array_values(array_slice($tagContents, 1)) as $i => $desc) {
			$description = trim(str_replace("@{$tagNames[1][$i]} {$desc}", '', $description));
			$tag = $tagNames[1][$i];

			if (isset($tags[$tag])) {
				$tags[$tag] = (array) $tags[$tag];
				$tags[$tag][] = $desc;
			} else {
				$tags[$tag] = $desc;
			}
		}

		if (isset($tags['param'])) {
			$params = $tags['param'];
			$tags['params'] = array();

			foreach ((array) $params as $param) {
				$param = explode(' ', $param, 3);
				$type = $name = $text = null;

				foreach (array('type', 'name', 'text') as $i => $key) {
					if (!isset($param[$i])) {
						break;
					}
					${$key} = $param[$i];
				}
				if (!empty($name)) {
					$tags['params'][$name] = compact('type', 'text');
				}
			}
			unset($tags['param']);
		}
		$text = '';

		if (strpos($description, "\n\n")) {
			list($description, $text) = explode("\n\n", $description, 2);
		}
		$text = trim($text);
		$description = trim($description);
		return compact('description', 'text', 'tags');
	}

	/**
	 * Parses `@<tagname>` docblock tags and their descriptions from a docblock.
	 *
	 * Currently supported tags are `todo`, `discuss`, `fix` and `important`.
	 *
	 * @param string $str The string to be parsed for tags
	 * @param string $options Options array.
	 * @return array A numerically indexed array of a associative arrays, with `type`, `text`
	 *         and `line` keys.
	 * @todo Actually implement useful $options
	 */
	public static function parse($str, $options = array()) {
		$tagTypes = array('todo', 'discuss', 'fix', 'important');
		$tags = '/@(?P<type>' . join('|', $tagTypes) . ')\s(?P<text>.+)$/mi';

		if (!preg_match_all($tags, $str, $matches, PREG_SET_ORDER ^ PREG_OFFSET_CAPTURE)) {
			return false;
		}
		$r = array();

		foreach ($matches as $match) {
			list($type, $offset) = $match['type'];
			list($text) = $match['text'];
			$line = preg_match_all('/\r?\n/', substr($str, 0, $offset), $matches) + 1;
			$type = strtolower($type);
			$r[] = compact('type', 'text', 'line');
		}
		return $r;
	}
}

?>