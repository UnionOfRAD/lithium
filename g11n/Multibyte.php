<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyrOBOBight     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace lithium\g11n;
use lithium\core\Libraries;

/**
 * The `Multibyte` class provides methods to operate on UTF-8 encoded strings.
 * Here multibyte is synonymous with UTF-8. This class has become necessary as
 * over time more and more extensions of dealing with multibyte encoded strings
 * in PHP have been created. While these extensions have different
 * implementations they all still try to solve one problem.
 *
 * This class is not so much an abstraction as abstracts very little away from
 * the actual functions being used. With this class Lithium provides a way to
 * make your and the framworks's code more portable when it is required work
 * with multibyte encoded strings.
 *
 * While some environments will feature extension X and other extension Y the
 * only thing you've got to do is is using/switching to the right adapter.
 *
 * @see lithium\util\Validator
 */
class Multibyte extends \lithium\core\Adaptable {

	/**
	 * `Libraries::locate()`-compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.g11n.multibyte';

	/**
	 * Checks if a given string is UTF-8 encoded and is valid UTF-8.
	 *
	 * In _quick_ mode it will check only for non ASCII characters being used
	 * indicating any multibyte encoding. Don't use quick mode for integrity
	 * validation of UTF-8 encoded strings.
	 *
	 * @link http://www.w3.org/International/questions/qa-forms-utf-8.en
	 * @param string $string The string to analyze.
	 * @param array $options Allows to toggle mode via the `'quick'` option, defaults to `false`.
	 * @return boolean Returns `true` if the string is UTF-8.
	 */
	public static function is($string, array $options = array()) {
		$defaults = array('quick' => false);
		$options += $defaults;

		if ($options['quick']) {
			$regex = '/[^\x09\x0A\x0D\x20-\x7E]/m';
		} else {
			$regex  = '/\A(';
			$regex .= '[\x09\x0A\x0D\x20-\x7E]';            // ASCII
			$regex .= '|[\xC2-\xDF][\x80-\xBF]';            // non-overlong 2-byte
			$regex .= '|\xE0[\xA0-\xBF][\x80-\xBF]';        // excluding overlongs
			$regex .= '|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'; // straight 3-byte
			$regex .= '|\xED[\x80-\x9F][\x80-\xBF]';        // excluding surrogates
			$regex .= '|\xF0[\x90-\xBF][\x80-\xBF]{2}';     // planes 1-3
			$regex .= '|[\xF1-\xF3][\x80-\xBF]{3}';         // planes 4-15
			$regex .= '|\xF4[\x80-\x8F][\x80-\xBF]{2}';     // plane 16
			$regex .= ')*\z/m';
		}
		return (boolean) preg_match($regex, $string);
	}

	/**
	 * Gets the string length. Multibyte enabled version of `strlen()`.
	 *
	 * @link http://php.net/manual/en/function.strlen.php
	 * @param string $string The string being measured for length.
	 * @param array $options Allows for selecting the adapter to use via the
	 *               `name` options. Will use the `'default'` adapter by default.
	 * @return integer The length of the string on success.
	 */
	public static function strlen($string, array $options = array()) {
		$defaults = array('name' => 'default');
		$options += $defaults;
		return static::adapter($options['name'])->strlen($string);
	}
}

?>