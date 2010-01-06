<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/*
 * Inflector configuration example.  If your application has custom singular or plural rules, or
 * extra non-ASCII characters to transliterate, you can configure that by uncommenting the lines
 * below.
 */
// use lithium\util\Inflector;
//
// Inflector::rules("plural", array(
// 	'/(s)tatus$/i' => '\1\2tatuses',
// 	'/^(ox)$/i' => '\1\2en',
// 	'/([m|l])ouse$/i' => '\1ice'
// ));
//
// Inflector::rules("uninflectedPlural", array('.*[nrlm]ese', '.*deer', '.*ois', '.*pox'));
//
// Inflector::rules("irregularPlural", array('atlas' => 'atlases', 'brother' => 'brothers'));
//
// Inflector::rules("singular", array(
// 	'/(s)tatuses$/i' => '\1\2tatus',
// 	'/(matr)ices$/i' =>'\1ix','/(vert|ind)ices$/i'
// ));

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
 *     need to specify a scope for each configuration, except for those using the _memory_ or
 *     _gettext_ adapter which handle this internally.
 */
// use lithium\g11n\Catalog;
//
// Catalog::config(array(
// 	'runtime' => array(
// 		'adapter' => 'Memory'
// 	),
// 	'app' => array(
// 		'adapter' => 'Gettext',
// 		'path' => LITHIUM_APP_PATH . '/resources/g11n'
// 	),
// 	'lithium' => array(
// 		'adapter' => 'Gettext',
// 		'path' => LITHIUM_LIBRARY_PATH . '/lithium/g11n/resources'
// 	)
// ));

/**
 * Globalization runtime data.  You can add globalized data during runtime utilizing a
 * configuration set up to use the _memory_ adapter.
 */
// $data = array('root' => function($n) { return $n != 1 ? 1 : 0; });
// Catalog::write('message.plural', $data, array('name' => 'runtime'));

/**
 * Enabling globalization integration.  Classes in the framework are designed with
 * globalization in mind. To enable globalization for these classes we just need to pass
 * the needed data into them.
 */
// use lithium\util\Validator;
// use lithium\util\Inflector;
//
// Validator::add('postalCode',
// 	Catalog::read('validation.postalCode', array('en_US'))
// );
// Inflector::rules('transliterations',
// 	Catalog::read('inflection.transliteration', array('en'))
// );

?>