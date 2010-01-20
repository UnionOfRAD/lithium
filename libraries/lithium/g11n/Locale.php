<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n;

use \BadMethodCallException;
use \InvalidArgumentException;

/**
 * The `Locale` class provides methods to deal with locale identifiers.  The locale
 * (here: _locale identifier_) is used to distinguish among different sets of common
 * preferences.
 *
 * In order to avoid unnecessary overhead all methods throughout the framework accepting
 * a locale require it to be well-formed according to the structure laid out below. For
 * assuring the correct format use `Locale::canonicalize()` once on the locale.
 *
 * However the methods within this class will also work with not-so-well-formed locales.
 * They accept both underscores and hyphens as separators between and don't care about the
 * case of the individual tags.
 *
 * The identifier used by Lithium is based in its structure upon Unicode's
 * language identifier and is compliant to BCP 47.
 *
 * `language[_Script][_TERRITORY][_VARIANT]`
 *  - `language` The spoken language, here represented by an ISO 639-1 code,
 *    where not available ISO 639-3 and ISO 639-5 codes are allowed too) tag.
 *    The tag should  be lower-cased and is required.
 *  - `Script` The tag should have it's first character capitalized, all others
 *    lower-cased. The tag is optional.
 *  - `TERRITORY` A geographical area, here represented by an ISO 3166-1 code.
 *     Should be all upper-cased and is optional.
 *  - `VARIANT` Should be all upper-cased and is optional.
 *
 * @method string|void language(string $locale) Parses a locale and returns it's language tag.
 * @method string|void script(string $locale) Parses a locale and returns it's script tag.
 * @method string|void territory(string $locale) Parses a locale and returns it's territory tag.
 * @method string|void variant(string $locale) Parses a locale and returns it's variant tag.
 * @link http://www.unicode.org/reports/tr35/tr35-12.html#Identifiers
 * @link http://www.rfc-editor.org/rfc/bcp/bcp47.txt
 * @link http://www.iana.org/assignments/language-subtag-registry
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

		if (!isset(static::$_tags[$method])) {
			throw new BadMethodCallException("Invalid locale tag `{$method}`");
		}
		return isset($tags[$method]) ? $tags[$method] : null;
	}

	/**
	 * Composes a locale from locale tags.  This is the pendant to `Locale::decompose()`.
	 *
	 * @param array $tags An array as obtained from `Locale::decompose()`.
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
	 * Parses a locale into locale tags.  This is the pendant to `Locale::compose()``.
	 *
	 * @param string $locale A locale in an arbitrary form (i.e. `'en_US'` or `'EN-US'`).
	 * @return array Parsed language, script, territory and variant tags.
	 * @throws InvalidArgumentException
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
	 * Returns a locale in its canonical form with tags formatted properly.
	 *
	 * @param string $locale A locale in an arbitrary form (i.e. `'ZH-HANS-HK_REVISED'`).
	 * @return string A locale in it's canoncial form (i.e. `'zh_Hans_HK_REVISED'`).
	 */
	public static function canonicalize($locale) {
		$tags = static::decompose($locale);

		foreach ($tags as $name => &$tag) {
			foreach ((array) static::$_tags[$name]['formatter'] as $formatter) {
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
	 * @param string $locale A locale in an arbitrary form (i.e. `'en_US'` or `'EN-US'`).
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