<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * This bootstrap file contains class configuration for all aspects of globalizing your application,
 * including localization of text, validation rules, setting timezones and character inflections,
 * and identifying a user's locale.
 */
use lithium\core\Libraries;
use lithium\core\Environment;
use lithium\g11n\Locale;
use lithium\g11n\Catalog;
use lithium\g11n\Message;
use lithium\util\Inflector;
use lithium\util\Validator;
use lithium\net\http\Media;
use lithium\action\Dispatcher as ActionDispatcher;
use lithium\console\Dispatcher as ConsoleDispatcher;

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
 *
 *  - `'locale'` The effective locale.
 *  - `'locales'` Application locales available mapped to names. The available locales are used
 *               to negotiate he effective locale, the names can be used i.e. when displaying
 *               a menu for choosing the locale to users.
 */
$locale = 'en';
$locales = array('en' => 'English');

Environment::set('production', compact('locale', 'locales'));
Environment::set('development', compact('locale', 'locales'));
Environment::set('test', array('locale' => 'en', 'locales' => array('en' => 'English')));

/**
 * Globalization (g11n) catalog configuration.  The catalog allows for obtaining and
 * writing globalized data. Each configuration can be adjusted through the following settings:
 *
 *   - `'adapter'` _string_: The name of a supported adapter. The builtin adapters are `Memory` (a
 *     simple adapter good for runtime data and testing), `Php`, `Gettext`, `Cldr` (for
 *     interfacing with Unicode's common locale data repository) and `Code` (used mainly for
 *     extracting message templates from source code).
 *
 *   - `'path'` All adapters with the exception of the `Memory` adapter require a directory
 *     which holds the data.
 *
 *   - `'scope'` If you plan on using scoping i.e. for accessing plugin data separately you
 *     need to specify a scope for each configuration, except for those using the `Memory`,
 *     `Php` or `Gettext` adapter which handle this internally.
 */
Catalog::config(array(
	'runtime' => array(
		'adapter' => 'Memory'
	),
	// 'app' => array(
	// 	'adapter' => 'Gettext',
	// 	'path' => Libraries::get(true, 'resources') . '/g11n'
	// ),
	'lithium' => array(
		'adapter' => 'Php',
		'path' => LITHIUM_LIBRARY_PATH . '/lithium/g11n/resources/php'
	)
) + Catalog::config());

/**
 * Integration with `Inflector`.
 */
// Inflector::rules('transliteration', Catalog::read(true, 'inflection.transliteration', 'en'));

/**
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
foreach (array('phone', 'postalCode', 'ssn') as $name) {
	Validator::add($name, Catalog::read(true, "validation.{$name}", 'en_US'));
}

/**
 * Intercepts dispatching processes in order to set the effective locale by using
 * the locale of the request or if that is not available retrieving a locale preferred
 * by the client.
 */
$setLocale = function($self, $params, $chain) {
	$request = $params['request'];
	$controller = $chain->next($self, $params, $chain);

	if (!$request->locale) {
		$request->params['locale'] = Locale::preferred($request);
	}
	Environment::set(Environment::get(), array('locale' => $request->locale));
	return $controller;
};
ActionDispatcher::applyFilter('_callable', $setLocale);
ConsoleDispatcher::applyFilter('_callable', $setLocale);

?>