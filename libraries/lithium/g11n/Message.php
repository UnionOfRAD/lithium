<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n;

use \lithium\core\Environment;
use \lithium\util\String;
use \lithium\g11n\Locale;
use \lithium\g11n\Catalog;

/**
 * The `Message` class is concerned with an aspect of globalizing static message strings
 * throughout the framework and applications.  When referring to message globalization the
 * phrase of _translating messages_ is widely used. This leads to the assumption that it's
 * a single step process wheras it' a multi step one. A short description of each step is
 * given here in order to help understanding the purpose of this class through the context
 * of the process as a whole.
 *
 *  1. Marking messages as translateable.  `$t()` and `$tn()` (implemented in the `View`
 *     class) are recognized as message marking and picked up by the extraction parser.
 *
 *  2. Extracting marked messages.  Messages can be extracted through the `g11n`
 *     command which in turn utilizes the `Catalog` class with the builtin `Code`
 *     adapter or other custom adapters which are concerned with extracting
 *     translatable content.
 *
 *  3. Creating a message template from extracted messages.  Templates are created
 *     by the `g11n` command using the `Catalog` class with an adapter for a format
 *     you prefer.
 *
 *  4. Translating messages.  The actual translation of messages by translators
 *     happens outside using external applications.
 *
 *  5. Storing translated messages.  Translations are most often stored by the external
 *     applications itself.
 *
 *  6. Retrieving the translation for a message. See description for `Message::translate()`.
 *
 * @see lithium\template\View
 * @see lithium\g11n\Catalog
 * @see lithium\console\commands\G11n
 * @see lithium\g11n\catalog\adapters\Code
 */
class Message extends \lithium\core\StaticObject {

	/**
	 * Returns the translation of a message according to the current or provided locale
	 * and (if applicable) plural form.  The method can be used for both single message
	 * or messages with a plural form. The provided message will be used as a fall back
	 * if it isn't translateable. You may also use `String::insert()`-style placeholders
	 * within message strings and provide replacements as a separate option.
	 *
	 * Usage:
	 * {{{
	 * Message::translate('Mind the gap.');
	 * Message::translate('house', array(
	 * 	'plural' => 'houses', 'count' => 23
	 * ));
	 * Message::translate('Your {:color} paintings are looking just great.', array(
	 * 	'replace' => array('color' => 'silver'),
	 * 	'locale' => 'de'
	 * ));
	 * }}}
	 *
	 * @param string $singular Either a single or the singular form of the message.
	 * @param array $replace An array with replacements for placeholders.
	 * @param array $options Allowed keys are:
	 *              - `'plural'`: Used as a fall back if needed.
	 *              - `'count'`: Used to determine the correct plural form.
	 *              - `'locale'`: The target locale, defaults to current locale.
	 *              - `'scope'`: The scope of the message.
	 * @return string
	 */
	public static function translate($singular, $replace = array(), $options = array()) {
		$defaults = array(
			'plural' => null,
			'count' => 1,
			// 'locale' => Environment::get('G11n.locale')
			'locale' => 'de',
			'scope' => null
		);
		extract($options + $defaults);

		if (!$translated = static::_translated($singular, $locale, $count, $scope)) {
			$translated = $count == 1 ? $singular : $plural;
		}
		return String::insert($translated, $replace);
	}

	/**
	 * Retrieves the translation for a message ID.  Uses the `Catalog` class to
	 * access translation data and determines the correct plural form (if applicable).
	 *
	 * @param string $id The message ID.
	 * @param string $locale The target locale.
	 * @param integer $count Used to determine the correct plural form.
	 * @param string $scope The scope of the message ID.
	 * @return string|void The translated message or `null` if `$singular` is not
	 *         translateable or a plural rule couldn't be found.
	 * @see lithium\g11n\Catalog
	 * @todo Message pages need caching.
	 */
	protected static function _translated($id, $locale, $count = null, $scope = null) {
		$result = Catalog::read('message.page', $locale, compact('scope'));

		if (empty($result[$locale][$id]['translated'])) {
			return null;
		}
		$translated = $result[$locale][$id]['translated'];

		if (isset($count)) {
			$result = Catalog::read('message.plural', $locale);

			if (!isset($result[$locale])) {
				return null;
			}
			$key = $result[$locale]($count);

			if (isset($translated[$key])) {
				return $translated[$key];
			}
		} else {
			return array_shift($translated);
		}
	}
}

?>