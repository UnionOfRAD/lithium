<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util\reflection;

class Docblock extends \lithium\core\StaticObject {

	/**
	 * undocumented function
	 *
	 * @param string $description 
	 * @return array
	 * @todo Implement text
	 */
	public static function comment($description) {
		$text = null;
		$tags = array();
		$description = trim(preg_replace('/^(\s*\/\*\*|\s*\*\/|\s+\* ?)/m', '', $description));

		if (!(preg_match_all('/\n@(\w+)\s+/', $description, $tagNames))) {
			return compact('description', 'text', 'tags');
		}
		$tagContents = preg_split('/\n@\w+\s+/ms', $description);
		ini_set('xdebug.var_display_max_data', 2048);

		foreach (array_values(array_slice($tagContents, 1)) as $i => $desc) {
			$description = trim(str_replace("@{$tagNames[1][$i]} {$desc}", '', $description));
			$tag = $tagNames[1][$i];

			if (isset($tags[$tag])) {
				$tags[$tag] = (array)$tags[$tag];
				$tags[$tag][] = $desc;
			} else {
				$tags[$tag] = $desc;
			}
		}

		if (isset($tags['param'])) {
			$params = $tags['param'];
			$tags['params'] = array();

			foreach ((array)$params as $param) {
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
	 * undocumented function
	 *
	 * @param string $str 
	 * @param string $options 
	 * @return array
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