<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * Welcome to Lithium!  This front-controller file is the gateway to your application. It is
 * responsible for intercepting requests, and handing them off to the Dispatcher for processing.
 *
 * If you're sharing a single Lithium core install or other libraries among multiple
 * applications, you may need to manually set things like LITHIUM_LIBRARY_PATH. You can do that in
 * app/config/bootstrap.php, which is loaded below:
 */
require dirname(__DIR__) . '/config/bootstrap.php';

/**
 * Dispatch a new request with the default settings.
 */
echo lithium\action\Dispatcher::run();

?>