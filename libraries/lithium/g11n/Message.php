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
 * The `Message` class is concerned with aspects of the globalization of static message strings
 * throughout the framework.
 *
 * Often the phrase of "translating a message" is  used for referring to globalization of messages
 * which leads to the false assumption that this is a single step, whereas it is a multi-step
 * process.
 *
 *  1. Marking messages as translateable.
 *  2. Extracting marked messages, creating a message template.
 *  3. Translating messages, storing the translation.
 *  4. Retrieving the translation for a message.
 *
 * This class provides methods for the first and the last step of the process. The second
 * one is dealt with by the `Catalog` class (see the description for `Message::translate()` for
 * more information). The actual translation of messages by translators happens outside of the
 * framework using external applications.
 */
class Message extends \lithium\core\StaticObject {

	/**
	 * This method serves two purposes.
	 *
	 * For one it is used to mark transalateable messages, which can be extracted by the `Catalog`
	 * class using the `Code` adapter for creating message template files. Since the marked messages
	 * will be later translated by others it is important to keep a few best practices in mind.
	 *
	 *   1. Use entire English sentences (as it gives context).
	 *   2. Split paragraphs into multiple messages.
	 *   3. Instead of string concatenation utilize `String::insert()`-style format strings.
	 *   4. Avoid to embed markup into the messages.
	 *   5. Do not escape i.e. quotation marks where possible.
	 *
	 * The other purpose it serves is to return the translation of a message according to
	 * the current or provided locale and (if applicable) plural form.  The method can be used for
	 * both single message or messages with a plural form. The provided message will be used as a
	 * fall back if it isn't translateable. You may also use `String::insert()`-style place holders
	 * within message strings and provide replacements as a separate option.
	 *
	 * Usage:
	 * {{{
	 * Message::translate('Mind the gap.');
	 * Message::translate('house', array(
	 * 	'plural' => 'houses', 'count' => 23
	 * ));
	 * Message::translate('Your {:color} paintings are looking just great.', array(
	 * 	'replacements' => array('color' => 'silver'),
	 * 	'locale' => 'de'
	 * ));
	 * }}}
	 *
	 * @param string $singular Either a single or the singular form of the message.
	 * @param array $options Allowed keys are:
	 *        - `'count'`: Used to determine the correct plural form.
	 *        - `'locale'`: The target locale, defaults to current locale.
	 *        - `'plural'`: Used as a fall back if needed.
	 *        - `'replacements'`: An array with replacements for place holders.
	 *        - `'scope'`: The scope of the message.
	 * @return string
	 *
	 * @see lithium\console\command\g11n\Extract
	 * @see lithium\g11n\catalog\adapter\Code
	 */
	public static function translate($singular, $options = array()) {
		$defaults = array(
			'plural' => null,
			'count' => 1,
			'replacements' => array(),
			// 'locale' => Environment::get('G11n.locale')
			'locale' => 'de',
			'scope' => null
		);
		extract($options + $defaults);

		if (!$translated = static::_translated($singular, $locale, $count, $scope)) {
			$translated = $count == 1 ? $singular : $plural;
		}
		return String::insert($translated, $replacements);
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