<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n;

use \BadMethodCallException;
use \InvalidArgumentException;

/**
 * Locale class.
 *
 * @method string|void language(string $locale) Parses a locale and returns it's language tag.
 * @method string|void script(string $locale) Parses a locale and returns it's script tag.
 * @method string|void territory(string $locale) Parses a locale and returns it's territory tag.
 * @method string|void variant(string $locale) Parses a locale and returns it's variant tag.
 */
class Locale extends \lithium\core\StaticObject {

	/**
	 * Properties for locale tags.
	 *
	 * @var array
	 */
	protected static $_tags = array(
		'language' => array('formatter' => 'strtolower'),
		'script' => array('formatter' => array('strtolower', 'ucfirst')),
		'territory' => array('formatter' => 'strtoupper'),
		'variant' => array('formatter' => 'strtoupper')
	);

	/**
	 * Magic method enabling tag methods.
	 *
	 * @param string $method
	 * @param array $params
	 * @return string|void
	 */
	public static function __callStatic($method, $params = array()) {
		$tags = static::invokeMethod('decompose', $params);

		if (!array_key_exists($method, static::$_tags)) {
			throw new BadMethodCallException("Invalid locale tag `{$method}`");
		}
		return isset($tags[$method]) ? $tags[$method] : null;
	}

	/**
	 * Composes a locale from locale tags.
	 *
	 * @param array $tags An array as obtained from {@see decompose()}.
	 * @return string|void A locale with tags separated by underscores or `null`
	 *         if none of the passed tags could be used to compose a locale.
	 */
	public static function compose($tags) {
		$result = array();

		foreach (static::$_tags as $name => $tag) {
			if (isset($tags[$name])) {
				$result[] = $tags[$name];
			}
		}
		if ($result) {
			return implode('_', $result);
		}
	}

	/**
	 * Parses a locale into locale tags.  A valid locale has the structure and format
	 * `language[_Script][_TERRITORY][_VARIANT]`. The language tag is an ISO 639-1 code,
	 * where not available ISO 639-3 and ISO 639-5 codes are allowed too. The territory
	 * tag is an ISO 3166-1 code.
	 *
	 * @param string $locale I.e. `'en'`, `'en_US'` or `'de_DE'`.
	 * @return array Parsed language, script, territory and variant tags.
	 * @throws InvalidArgumentException
	 * @link http://www.rfc-editor.org/rfc/bcp/bcp47.txt
	 */
	public static function decompose($locale) {
		$regex  = '(?P<language>[a-z]{2,3})';
		$regex .= '(?:[_-](?P<script>[a-z]{4}))?';
		$regex .= '(?:[_-](?P<territory>[a-z]{2}))?';
		$regex .= '(?:[_-](?P<variant>[a-z]{5,}))?';

		if (!preg_match("/^{$regex}$/i", $locale, $matches)) {
			throw new InvalidArgumentException("Locale `{$locale}` could not be parsed");
		}
		return array_filter(array_intersect_key($matches, static::$_tags));
	}

	/**
	 * Returns a locale in it's canonical form with tags formatted properly.
	 *
	 * @param string $locale A locale in an arbitrary form (i.e. `'ZH-HANS-HK_REVISED'`).
	 * @return string A locale in it's canoncial form (i.e. `'zh_Hans_HK_REVISED'`).
	 */
	public static function canonicalize($locale) {
		$tags = static::decompose($locale);

		foreach ($tags as $name => &$tag) {
			foreach ((array)static::$_tags[$name]['formatter'] as $formatter) {
				$tag = $formatter($tag);
			}
		}
		return static::compose($tags);
	}

	/**
	 * Cascades a locale.
	 *
	 * Usage:
	 * {{{
	 * Locale::cascade('en_US');
	 * // returns array('en_US', 'en', 'root')
	 *
	 * Locale::cascade('zh_Hans_HK_REVISED');
	 * // returns array('zh_Hans_HK_REVISED', 'zh_Hans_HK', 'zh_Hans', 'zh', 'root')
	 * }}}
	 *
	 * @return array Indexed array of locales (starting with the most specific one).
	 */
	public static function cascade($locale) {
		$locales[] = $locale;

		if ($locale === 'root') {
			return $locales;
		}
		$tags = static::decompose($locale);

		while (count($tags) > 1) {
			array_pop($tags);
			$locales[] = static::compose($tags);
		}
		$locales[] = 'root';
		return $locales;
	}
}

?>