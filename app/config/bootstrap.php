<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium;

use \lithium\core\Environment;
use \lithium\core\Libraries;

/**
 * This is the path to the class libraries used by your application, and must contain a copy of the
 * Lithium core.  By default, this directory is named 'libraries', and resides in the same
 * directory as your application.  If you use the same libraries in multiple applications, you can
 * set this to a shared path on your server.
 */
define('LITHIUM_LIBRARY_PATH', dirname(dirname(__DIR__)) . '/libraries');

/**
 * This is the path to your application's directory.  It contains all the sub-folders for your
 * application's classes and files.  You don't need to change this unless your webroot folder is
 * stored outside of your app folder.
 */
define('LITHIUM_APP_PATH', dirname(__DIR__));

/**
 * Locate and load Lithium core library files.  Throws a fatal error if the core can't be found.
 * If your Lithium core directory is named something other than 'lithium', change the string below.
 */
if (!include LITHIUM_LIBRARY_PATH . '/lithium/core/Libraries.php') {
	$message  = "Lithium core could not be found.  Check the value of LITHIUM_LIBRARY_PATH in ";
	$message .= "config/bootstrap.php.  It should point to the directory containing your ";
	$message .= "/libraries directory.";
	trigger_error($message, E_USER_ERROR);
}

/**
 * Add the Lithium core library.  This sets default paths and initializes the autoloader.  You
 * generally should not need to override any settings.
 */
Libraries::add('lithium');

/**
 * Optimize default request cycle by loading common classes.  If you're implementing custom
 * request/response or dispatch classes, you can safely remove these.  Actually, you can safely
 * remove them anyway, they're just there to give slightly you better out-of-the-box performance.
 */
require LITHIUM_LIBRARY_PATH . '/lithium/core/Object.php';
require LITHIUM_LIBRARY_PATH . '/lithium/core/StaticObject.php';
require LITHIUM_LIBRARY_PATH . '/lithium/util/Collection.php';
require LITHIUM_LIBRARY_PATH . '/lithium/util/collection/Filters.php';
require LITHIUM_LIBRARY_PATH . '/lithium/util/Inflector.php';
require LITHIUM_LIBRARY_PATH . '/lithium/util/Set.php';
require LITHIUM_LIBRARY_PATH . '/lithium/util/String.php';
require LITHIUM_LIBRARY_PATH . '/lithium/core/Environment.php';
require LITHIUM_LIBRARY_PATH . '/lithium/http/Base.php';
require LITHIUM_LIBRARY_PATH . '/lithium/http/Media.php';
require LITHIUM_LIBRARY_PATH . '/lithium/http/Request.php';
require LITHIUM_LIBRARY_PATH . '/lithium/http/Response.php';
require LITHIUM_LIBRARY_PATH . '/lithium/http/Route.php';
require LITHIUM_LIBRARY_PATH . '/lithium/action/Controller.php';
require LITHIUM_LIBRARY_PATH . '/lithium/action/Dispatcher.php';
require LITHIUM_LIBRARY_PATH . '/lithium/action/Request.php';
require LITHIUM_LIBRARY_PATH . '/lithium/action/Response.php';
require LITHIUM_LIBRARY_PATH . '/lithium/template/View.php';
require LITHIUM_LIBRARY_PATH . '/lithium/template/view/Renderer.php';

/**
 * Add the application.  You can pass a `'path'` key here if this bootstrap file is outside of
 * your main application, but generally you should not need to change any settings.
 */
Libraries::add('app');

/**
 * Add some plugins
 */
// Libraries::add('plugin', 'lithium_docs');

/**
 * This configures your session storage. The Cookie storage adapter must be connected first, since
 * it intercepts any writes where the `'expires'` key is set in the options array.  When creating a
 * new application, it is suggested that you change the value of `'key'` below.
 */

/**
* Session configuration
*/
// use \lithium\storage\Session;
//
// Session::config(array(
// 	'cookie' => array(
// 		'adapter' => 'Cookie',
// 		'name'    => 'AppCookieName',
// 		'expires' => '+5 days',
// 		'domain'  => '',
// 		'path'    => '/',
// 		'filters' => array(
// 			// 'Encryption' => array('key' => '0409448a5206980ab15682c3281c1a3b1fb10c55')
// 		)
// 	),
// 	'default' => array('adapter' => 'Php', 'filters' => array())
// ));

/**
 * To enable admin or plugin routing, uncomment the following lines, and see `app/config/routes.php`
 * to enable the admin routing namespace.
 */
// use \lithium\action\Dispatcher;
//
// Dispatcher::config(array('rules' => array(
// 	'admin' => array('action' => 'admin_{:action}'),
// 	'plugin' => array('controller' => '{:plugin}.{:controller}')
// )));

/**
 * Uncomment to set globalization defaults. A locale consists of a language and
 * an optional territory code i.e. `'en_US'` or `'en'`. For timezone specify
 * a valid timezone identifier i.e. `'America/New_York'` or `'Etc/UTC'`. You may
 * also specify additional sources for retrieving translated and messages and
 * localized data or add rules, formats, messages or lists data right here.
 */
// use \lithium\g11n\G11n;
//
// G11n::locale('en');
// G11n::timezone('Etc/UTC');
// G11n::sources(LITHIUM_APP_PATH . '/extensions/g11n');
// G11n::rules('plural', array('en' => function($n) { return $n != 1 ? 1 : 0; }));

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
// 	'runtime' => array('adapter' => 'Memory'),
// 	'app' => array('adapter' => 'Gettext', 'path' => LITHIUM_APP_PATH . '/resources/po'),
// 	'lithium' => array('adapter' => 'Gettext', 'path' => LITHIUM_LIBRARY_PATH . '/lithium/resources/po')
// ));

/**
 * Globalization runtime data.  You can add globalized data during runtime utilizing a
 * configuration set up to use the _memory_ adapter.
 */
// $data = array('en' => function($n) { return $n != 1 ? 1 : 0; });
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

/**
 * Your custom code goes here.
 */

?>