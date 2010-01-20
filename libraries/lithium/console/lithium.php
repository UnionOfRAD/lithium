<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium;

use \lithium\core\Libraries;
use \lithium\console\Dispatcher;

/**
 * Determine if we're in an application context by moving up the directory tree looking for
 * a `config` directory with a `bootstrap.php` file in it.  If no application context is found,
 * just boot up the core framework.
 */

$library = dirname(dirname(__DIR__));
$app = null;
$working = getcwd() ?: __DIR__;

while (!$app && $working) {
	if (file_exists($working . '/config/bootstrap.php')) {
		$app = $working;
	} elseif (file_exists($working . '/app/config/bootstrap.php')) {
		$app = $working . '/app';
	} else {
		$working = ($parent = dirname($working)) != $working ? $parent : false;
	}
}

if ($app) {
	include $app . '/config/bootstrap.php';
} else {
	define('LITHIUM_LIBRARY_PATH', $library);
	define('LITHIUM_APP_PATH', dirname($library) . '/app');

	if (!include LITHIUM_LIBRARY_PATH . '/lithium/core/Libraries.php') {
		$message  = "Lithium core could not be found.  Check the value of `LITHIUM_LIBRARY_PATH` ";
		$message .= "in `config/bootstrap.php`. It should point to the directory containing your ";
		$message .= "`/libraries` directory.";
		trigger_error($message, E_USER_ERROR);
	}
	Libraries::add('lithium');
}

exit(Dispatcher::run()->status);

?>