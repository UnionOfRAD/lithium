<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * Determine if we're in an application context by moving up the directory tree looking for
 * a `config` directory with a `bootstrap.php` file in it.  If no application context is found,
 * just boot up the core framework.
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
		'default' => true
	));
}
exit(lithium\console\Dispatcher::run(new lithium\console\Request())->status);

?>