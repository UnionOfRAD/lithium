<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n;

use \lithium\core\Environment;
use \lithium\util\String;
use \lithium\g11n\Locale;
use \lithium\g11n\Catalog;

class Message extends \lithium\core\StaticObject {

	/**
	 * Returns the translation for a message ID.  Translates messages according to current locale.
	 * You can use this method either for translating a single message or for messages with a plural
	 * form. The provided message will be used as a fall back if it isn't translateable. You may
	 * also use `String::insert()`-style placeholders within message strings and provide
	 * replacements as a separate option.
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
	 *               Used as the message ID.
	 * @param array $options Allowed keys are:
	 *              - `'plural'`: Used as a fall back if needed.
	 *              - `'count'`: Used to determine the correct plural form.
	 *              - `'replacements'`: An array with replacements for placeholders.
	 *              - `'locale'`: The target locale, defaults to current locale.
	 *              - `'scope'`: The scope of the message.
	 * @return string
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
	 * Retrieves a translated message version of a message ID.
	 *
	 * @param string $id The singular form of the message.
	 * @param string $locale The target locale.
	 * @param integer $count Used to determine the correct plural form (optional).
	 * @param string $scope The scope of the message ID (optional).
	 * @return string|void The translated message or `null` if `$singular` is not
	 *         translateable or a plural rule couldn't be found.
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

?`>