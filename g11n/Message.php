<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\g11n;

use lithium\aop\Filters;
use lithium\core\Environment;
use lithium\util\Text;
use lithium\g11n\Catalog;

/**
 * The `Message` class is concerned with an aspect of globalizing static message strings
 * throughout the framework and applications.  When referring to message globalization the
 * phrase of "translating a message" is widely used. This leads to the assumption that it's
 * a single step process whereas it's a multi step one. A short description of each step is
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
class Message {

	/**
	 * Holds cached message pages generated and used
	 * by `lithium\g11n\Message::_translated()`.
	 *
	 * @var array
	 * @see lithium\g11n\Message::_translated()
	 */
	protected static $_cachedPages = [];

	/**
	 * Translates a message according to the current or provided locale
	 * and into its correct plural form.
	 *
	 * Usage:
	 * ```
	 * Message::translate('Mind the gap.');
	 * Message::translate('house', ['count' => 23]);
	 * ```
	 *
	 * `Text::insert()`-style placeholders may be used within the message
	 * and replacements provided directly within the `options`  argument.
	 *
	 * Example:
	 * ```
	 * Message::translate('I can see {:count} bike.');
	 * Message::translate('This painting is {:color}.', [
	 * 	'color' => Message::translate('silver'),
	 * ]);
	 * ```
	 *
	 * @see lithium\util\Text::insert()
	 * @param string $id The id to use when looking up the translation.
	 * @param array $options Valid options are:
	 *              - `'count'`: Used to determine the correct plural form. You can either pass
	 *                           a signed or unsigned integer, the behavior when passing other types
	 *                           is yet undefined.
	 *                           The count is made absolute before being passed to the pluralization
	 *                           function. This has the effect that that with i.e. an English
	 *                           pluralization function passing `-1` results in a singular
	 *                           translation.
	 *              - `'locale'`: The target locale, defaults to current locale.
	 *              - `'scope'`: The scope of the message.
	 *              - `'context'`: The disambiguating context (optional).
	 *              - `'default'`: Is used as a fall back if `_translated()` returns
	 *                             without a result.
	 *              - `'noop'`: If `true` no whatsoever lookup takes place.
	 * @return string The translation or the value of the `'default'` option if none
	 *                     could be found.
	 */
	public static function translate($id, array $options = []) {
		$defaults = [
			'count' => 1,
			'locale' => Environment::get('locale'),
			'scope' => null,
			'context' => null,
			'default' => null,
			'noop' => false
		];
		$options += $defaults;

		if ($options['noop']) {
			$result = null;
		} else {
			$result = static::_translated($id, abs($options['count']), $options['locale'], [
				'scope' => $options['scope'],
				'context' => $options['context']
			]);
		}

		if ($result = $result ?: $options['default']) {
			return strpos($result, '{:') !== false ? Text::insert($result, $options) : $result;
		}
	}

	/**
	 * Returns an array containing named closures which are aliases for `translate()`.
	 * They can be embedded as content filters in the template layer using a filter for
	 * `Media::_handle()` or be used in other places where needed.
	 *
	 * Usage:
	 * ```
	 * $t('bike');
	 * $tn('bike', 'bikes', 3);
	 * ```
	 *
	 * Using in a method:
	 * ```
	 * public function index() {
	 * 	extract(Message::aliases());
	 * 	$notice = $t('look');
	 * }
	 * ```
	 *
	 * @see lithium\net\http\Media::_handle()
	 * @return array Named aliases (`'t'` and `'tn'`) for translation functions.
	 */
	public static function aliases() {
		$t = function($message, array $options = []) {
			return Message::translate($message, $options + ['default' => $message]);
		};
		$tn = function($message1, $message2, $count, array $options = []) {
			$opts = is_array($count) ? $count : $options + compact('count') + [
				'default' => $count === 1 ? $message1 : $message2
			];
			return Message::translate($message1, $opts);
		};
		return compact('t', 'tn');
	}

	/**
	 * Returns or sets the page cache used for mapping message ids to translations.
	 *
	 * @param array $cache A multidimensional array to use when pre-populating the cache. The
	 *              structure of the array is `scope/locale/id`. If `false`, the cache is cleared.
	 * @return array Returns an array of cached pages, formatted per the description for `$cache`.
	 */
	public static function cache($cache = null) {
		if ($cache === false) {
			static::$_cachedPages = [];
		}
		if (is_array($cache)) {
			static::$_cachedPages += $cache;
		}
		return static::$_cachedPages;
	}

	/**
	 * Retrieves translations through the `Catalog` class by using `$id` as the lookup
	 * key and taking the current or - if specified - the provided locale as well as the
	 * scope into account.  Hereupon the correct plural form is determined by passing the
	 * value of the `'count'` option to a closure.
	 *
	 * @see lithium\g11n\Catalog
	 * @param string $id The lookup key.
	 * @param integer $count Used to determine the correct plural form.
	 * @param string $locale The target locale.
	 * @param array $options Passed through to `Catalog::read()`. Valid options are:
	 *              - `'scope'`: The scope of the message.
	 *              - `'context'`: The disambiguating context.
	 * @return string The translation or `null` if none could be found or the plural
	 *         form could not be determined.
	 * @filter
	 */
	protected static function _translated($id, $count, $locale, array $options = []) {
		$params = compact('id', 'count', 'locale', 'options');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			extract($params);

			if (isset($options['context']) && $options['context'] !== null) {
				$context = $options['context'];
				$id = "{$id}|{$context}";
			}

			if (!isset(static::$_cachedPages[$options['scope']][$locale])) {
				static::$_cachedPages[$options['scope']][$locale] = Catalog::read(
					true, 'message', $locale, $options
				);
			}
			$page = static::$_cachedPages[$options['scope']][$locale];

			if (!isset($page[$id])) {
				return null;
			}
			if (!is_array($page[$id])) {
				return $page[$id];
			}

			if (!isset($page['pluralRule']) || !is_callable($page['pluralRule'])) {
				return null;
			}
			$key = $page['pluralRule']($count);

			if (isset($page[$id][$key])) {
				return $page[$id][$key];
			}
		});
	}
}

?>