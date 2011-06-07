<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * This console front-controller file is the gateway to your application
 * through the command line.  It is responsible for intercepting requests, and
 * handing them off to the `Dispatcher` for processing.
 *
 * Determine if we're in an application context by moving up the directory tree
 * looking for a `config` directory with a `bootstrap.php` file in it.  If no
 * application context is found, just boot up the core framework.
 */
$library = dirname(dirname(__DIR__));
$working = getcwd() ?: __DIR__;
$app = null;

while (!$app && $working) {
	if (file_exists($working . '/config/bootstrap.php')) {
		$app = $working;
	} elseif (file_exists($working . '/app/config/bootstrap.php')) {
		$app = $working . '/app';
	} else {
		$working = ($parent = dirname($working)) != $working ? $parent : false;
	}
}

if ($app && is_dir("{$app}/config/bootstrap") && file_exists("{$app}/webroot/index.php")) {
	include "{$app}/config/bootstrap.php";
	exit(lithium\console\Dispatcher::run(new lithium\console\Request())->status);
}

define('LITHIUM_LIBRARY_PATH', $library);
define('LITHIUM_APP_PATH', $app ? $working : dirname($library) . '/app');

if (!include LITHIUM_LIBRARY_PATH . '/lithium/core/Libraries.php') {
	$message  = "Lithium core could not be found.  Check the value of LITHIUM_LIBRARY_PATH in ";
	$message .= __FILE__ . ".  It should point to the directory containing your ";
	$message .= "/libraries directory.";
	throw new ErrorException($message);
}

lithium\core\Libraries::add('lithium');

if ($app) {
	lithium\core\Libraries::add(basename(LITHIUM_APP_PATH), array(
		'path' => LITHIUM_APP_PATH,
		'default' => true,
		'bootstrap' => !file_exists("{$app}/webroot/index.php")
	));
}

/**
 * The following will dispatch the request and exit with the status code as
 * provided by the `Response` object returned from `run()`.
 *
 * The following will instantiate a new `Request` object and pass it off to the
 * `Dispatcher` class.  By default, the `Request` will automatically aggregate
 * all the server / environment settings, and request content (i.e. options and
 * arguments passed to the command) information.
 *
 * The `Request` is then used by the `Dispatcher` (in conjunction with the
 * `Router`) to determine the correct command to dispatch to. The response
 * information is then encapsulated in a `Response` object, which is returned
 * from the command to the `Dispatcher`.
 *
 * The `Response` object will contain information about the status code which
 * is used as the exit code when ending the execution of this script and
 * returned to the callee.
 *
 * @see lithium\console\Request
 * @see lithium\console\Response
 * @see lithium\console\Dispatcher
 * @see lithium\console\Router
 */
exit(lithium\console\Dispatcher::run(new lithium\console\Request())->status);

?>