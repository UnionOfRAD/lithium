<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * Welcome to the switchboard. This file contains a series of method filters that allow you to
 * intercept different parts of Lithium's request cycle as they happen.  You can apply filters to
 * any object method that has a `@filter` flag in its API documentation.
 *
 * For more information on in the filters system, see `lithium\util\collection\Filters`.
 *
 * @see lithium\util\collection\Filters
 */

use \lithium\net\http\Router;
use \lithium\core\Environment;
use \lithium\action\Dispatcher;

/**
 * Loads application routes before the request is dispatched.  Change this to `include_once` if
 * more than one request cycle is executed per HTTP request.
 *
 * @see lithium\net\http\Router
 */
Dispatcher::applyFilter('run', function($self, $params, $chain) {
	include __DIR__ . '/routes.php';
	return $chain->next($self, $params, $chain);
});

/**
 * Intercepts the `Dispatcher` as it finds a controller object, and passes the `'request'` parameter
 * to the `Environment` class to detect which environment the application is running in.
 *
 * @see lithium\action\Request
 * @see lithium\core\Environment
 */
Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
	Environment::set($params['request']);
	return $chain->next($self, $params, $chain);
});

?>