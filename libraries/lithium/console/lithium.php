<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium;

use \lithium\core\Libraries;
use \lithium\console\Dispatcher;

/**
 * Determine if we're in an application context by moving up the directory tree, looking for a
 * 'config' directory with a 'bootstrap.php' file in it.  If no application context is found, just
 * boot up the core framework.
 */
$up = function($dir) {
	return (($parent = dirname($dir)) != $dir) ? $parent : false;
};

$current = function($pwd = null) use ($argc, $argv) {
	return ($pwd = array_search('-working', $argv) && $argc > $pwd) ? $argv[$pwd + 1] : __DIR__;
};

$app = null;

for ($dir = $current(); !$app && $dir; $dir = $up($dir)) {
	if (is_dir($dir . '/config') && file_exists($dir . '/config/bootstrap.php')) {
		$app = $dir;
	} else if (is_dir($dir . '/app/config') && file_exists($dir . '/app/config/bootstrap.php')) {
		$app = $dir . '/app';
	}
}

if ($app) {
	include $app . '/config/bootstrap.php';
} else {
	define('LITHIUM_LIBRARY_PATH', dirname(dirname(__DIR__)));
	define('LITHIUM_APP_PATH', dirname(LITHIUM_LIBRARY_PATH) . '/app');
	if (!include LITHIUM_LIBRARY_PATH . '/lithium/core/Libraries.php') {
		$message  = "Lithium core could not be found.  Check the value of LITHIUM_LIBRARY_PATH in ";
		$message .= "config/bootstrap.php.  It should point to the directory containing your ";
		$message .= "/libraries directory.";
		trigger_error($message, E_USER_ERROR);
	}
	Libraries::add('lithium');
}

exit(Dispatcher::run());

?>