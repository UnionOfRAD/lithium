<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
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
 * phrase of ""translating a message" is widely used. This leads to the assumption that it's
 * a single step process whereas it' a multi step one. A short description of each step is
 * given here in order to help understanding the purpose of this class through the context
 * of the process as a whole.
 *
 *  1. Marking messages as translatable.  `$t()` and `$tn()` (implemented in `aliases()`)
 *     are recognized as message marking and picked up by the extraction parser.
 *
 *  2. Extracting marked messages.  Messages can be extracted through the `g11n`
 *     command which in turn utilizes the `Catalog` class with the built-in `Code`
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
 * @see lithium\g11n\Catalog
 * @see lithium\console\command\G11n
 * @see lithium\g11n\catalog\adapter\Code
 */
class Message extends \lithium\core\StaticObject {

	/**
	 * Translates a message according to the current or provided locale
	 * and into it's correct plural form.
	 *
	 * Usage:
	 * {{{
	 * Message::translate('Mind the gap.');
	 * Message::translate('house', array('count' => 23));
	 * }}}
	 *
	 * `String::insert()`-style placeholders may be used within the message
	 * and replacements provided directly within the `options`  argument.
	 *
	 * Example:
	 * {{{
	 * Message::translate('I can see {:count} bike.');
	 * Message::translate('This {:color} painting is {:color}.', array(
	 * 	'color' => Message::translate('silver'),
	 * ));
	 * }}}
	 *
	 * @param string $id The id to use when looking up the translation.
	 * @param array $options Valid options are:
	 *              - `'count'`: Used to determine the correct plural form.
	 *              - `'locale'`: The target locale, defaults to current locale.
	 *              - `'scope'`: The scope of the message.
	 *              - `'default'`: Is used as a fall back if `_translated()` returns
	 *                             without a result.
	 *              - `'noop'`: If `true` no whatsoever lookup takes place.
	 * @return string|void The translation or the value of the `'default'` option if none
	 *                     could be found.
	 * @see lithium\util\String::insert()
	 */
	public static function translate($id, $options = array()) {
		$defaults = array(
			'count' => 1,
			'locale' => Environment::get('locale'),
			'scope' => null,
			'default' => null,
			'noop' => false
		);
		extract($options + $defaults);

		if ($noop) {
			$result = null;
		} else {
			$result = static::_translated($id, $count, $locale, compact('scope'));
		}

		if ($result || $default) {
			return String::insert($result ?: $default, $options);
		}
	}

	/**
	 * Returns an array containing named closures which are aliases for `translate()`.
	 * They can be embedded as content filters in the template layer using a filter for
	 * `Media::_handle()` or be used in other places where needed.
	 *
	 * Usage:
	 * {{{
	 * 	$t('bike');
	 * 	$tn('bike', 'bikes', array('count' => 3));
	 * }}}
	 *
	 * Using in a method:
	 * {{{
	 * 	public function index() {
	 * 		extract(Message::aliases());
	 * 		$notice = $t('look');
	 * 	}
	 * }}}
	 *
	 * @return array Named aliases (`'t'` and `'tn'`) for translation functions.
	 * @see lithium\net\http\Media::_handle()
	 */
	public static function aliases() {
		$t = function($message, $options = array()) {
			return Message::translate($message, $options + array('default' => $message));
		};
		$tn = function($message1, $message2, $count, $options = array()) {
			return Message::translate($message1, $options + compact('count') + array(
				'default' => $count == 1 ? $message1 : $message2
			));
		};
		return compact('t', 'tn');
	}

	/**
	 * Retrieves translations through the `Catalog` class by using `$id` as the lookup
	 * key and taking the current or - if specified - the provided locale as well as the
	 * scope into account.  Hereupon the correct plural form is determined by passing the
	 * value of the `'count'` option to a closure.
	 *
	 * @param string $id The lookup key.
	 * @param integer $count Used to determine the correct plural form.
	 * @param string $locale The target locale.
	 * @param array $options Passed through to `Catalog::read()`. Valid options are:
	 *              - `'scope'`: The scope of the message.
	 * @return string|void The translation or `null` if none could be found or the plural
	 *         form could not be determined.
	 * @see lithium\g11n\Catalog
	 * @filter
	 * @todo Message pages need caching.
	 */
	protected static function _translated($id, $count, $locale, $options = array()) {
		$params = compact('id', 'count', 'locale', 'options');

		return static::_filter(__METHOD__, $params, function($self, $params, $chain) {
			extract($params);

			$page = Catalog::read('message', $locale, $options);

			if (!isset($page[$id])) {
				return null;
			}
			$translated = (array) $page[$id];

			if (!isset($page['plural']) || !is_callable($page['plural'])) {
				return null;
			}
			$key = $page['plural']($count);

			if (isset($translated[$key])) {
				return $translated[$key];
			}
		});
	}
}

?>