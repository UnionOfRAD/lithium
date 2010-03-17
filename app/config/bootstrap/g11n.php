<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium;

use \lithium\core\Environment;
use \lithium\g11n\Locale;
use \lithium\g11n\Catalog;
use \lithium\g11n\Message;
use \lithium\util\Inflector;
use \lithium\util\Validator;
use \lithium\net\http\Media;
use \lithium\action\Dispatcher as ActionDispatcher;
use \lithium\console\Dispatcher as ConsoleDispatcher;

/**
 * Sets the default timezone used by all date/time functions.
 */
date_default_timezone_set('UTC');

/**
 * Adds globalization specific settings to the environment.
 *
 * The settings for the current locale, time zone and currency are kept as environment
 * settings. This allows for _centrally_ switching, _transparently_ setting and retrieving
 * globalization related settings.
 *
 * The environment settings are:
 * - `'locale'` The effective locale. Defaults to `'en'`.
 * - `'availableLocales'` Application locales available. Defaults to `array('en')`.
 */
Environment::set('production', array(
	'locale' => 'en',
	'availableLocales' => array('en')
));
Environment::set('development', array(
	'locale' => 'en',
	'availableLocales' => array('en')
));
Environment::set('test', array(
	'locale' => 'en',
	'availableLocales' => array('en')
));

/**
 * Globalization (g11n) catalog configuration.  The catalog allows for obtaining and
 * writing globalized data. Each configuration can be adjusted through the following settings:
 *
 *   - `'adapter' The name of a supported adapter. The builtin adapters are _memory_ (a
 *     simple adapter good for runtime data and testing), _gettext_, _cldr_ (for
 *     interfacing with Unicode's common locale data repository) and _code_ (used mainly for
 *     extracting message templates from source code).
 *
 *   - `'path'` All adapters with the exception of the _memory_ adapter require a directory
 *     which holds the data.
 *
 *   - `'scope'` If you plan on using scoping i.e. for accessing plugin data separately you
 *     need to specify a scope for each configuration, except for those using the _memory_,
 *     _php_ or _gettext_ adapter which handle this internally.
 */
Catalog::config(array(
	'runtime' => array(
		'adapter' => 'Memory'
	),
// 	'app' => array(
// 		'adapter' => 'Gettext',
// 		'path' => LITHIUM_APP_PATH . '/resources/g11n'
// 	),
	'lithium' => array(
		'adapter' => 'Php',
		'path' => LITHIUM_LIBRARY_PATH . '/lithium/g11n/resources/php'
	)
) + Catalog::config());

/**
 * Integration with `Inflector`.
 */
// Inflector::rules('transliteration', Catalog::read('inflection.transliteration', 'en'));

/*
 * Inflector configuration examples.  If your application has custom singular or plural rules, or
 * extra non-ASCII characters to transliterate, you can configure that by uncommenting the lines
 * below.
 */
// Inflector::rules('singular', array('rules' => array('/rata/' => '\1ratus')));
// Inflector::rules('singular', array('irregular' => array('foo' => 'bar')));
//
// Inflector::rules('plural', array('rules' => array('/rata/' => '\1ratum')));
// Inflector::rules('plural', array('irregular' => array('bar' => 'foo')));
//
// Inflector::rules('transliteration', array('/É|Ê/' => 'E'));
//
// Inflector::rules('uninflected', 'bord');
// Inflector::rules('uninflected', array('bord', 'baird'));


/**
 * Integration with `View`. Embeds message translation aliases into the `View`
 * class (or other content handler, if specified) when content is rendered. This
 * enables translation functions, i.e. `<?=$t("Translated content"); ?>`.
 */
Media::applyFilter('_handle', function($self, $params, $chain) {
	$params['handler'] += array('outputFilters' => array());
	$params['handler']['outputFilters'] += Message::aliases();
	return $chain->next($self, $params, $chain);
});

/**
 * Integration with `Validator`. You can load locale dependent rules into the `Validator`
 * by specifying them manually or retrieving them with the `Catalog` class.
 */
Validator::add('phone', Catalog::read('validation.phone', 'en_US'));
Validator::add('postalCode', Catalog::read('validation.postalCode', 'en_US'));
Validator::add('ssn', Catalog::read('validation.ssn', 'en_US'));

/**
 * Intercepts dispatching processes in order to set the effective locale by using
 * the locale of the request or if that is not available retrieving a locale preferred
 * by the client.
 */
ActionDispatcher::applyFilter('_callable', function($self, $params, $chain) {
	$request = $params['request'];
	$controller = $chain->next($self, $params, $chain);

	if (!$request->locale) {
		$request->params['locale'] = Locale::preferred($request);
	}
	Environment::set(Environment::get(), array('locale' => $request->locale));
	return $controller;
});
ConsoleDispatcher::applyFilter('_callable', function($self, $params, $chain) {
	$request = $params['request'];
	$command = $chain->next($self, $params, $chain);

	if (!$request->locale) {
		$request->params['locale'] = Locale::preferred($request);
	}
	Environment::set(Environment::get(), array('locale' => $request->locale));
	return $command;
});

?>