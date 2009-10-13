<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * Welcome to the switchboard. This file contains a series of method filters that allow you to
 * intercept different parts of Lithium's request cycle as they happen.  You can apply filters to
 * any object method that has a `@filter` flag in its API documentation.
 * 
 * When applying a filter, you need the name of the method you want to call, along with a *closure*,
 * that defines what you want the filter to do.  All filters take the same 3 parameters: `$self`,
 * `$params`, and `$chain`.
 * 
 * - `$self`: If the filter is applied on an object instance, then `$self` will be that instance. If
 * applied to a static class, then `$self` will be a string containing the fully-namespaced class
 * name.
 * 
 * - `$params`: Contains an associative array of the parameters that are passed into the method. You
 * can modify or inspect these parameters before allowing the method to continue.
 * 
 * - `$chain`: Finally, `$chain` contains the list of filters in line to be executed.  At the bottom
 * of `$chain` is the method itself.  This is why most filters contain a line that looks like
 * `return $chain->next($self, $params, $chain);`.  This passes control to the next filter in the
 * chain, and finally, to the method itself.  This allows you to interact with the return value as
 * well as the parameters.
 */

use \lithium\http\Router;
use \lithium\core\Environment;
use \lithium\action\Dispatcher;

/**
 * Loads application routes before the request is dispatched.  Change this to `include_once` if
 * more than one request cycle is executed per HTTP request.
 * 
 * @see lithium\http\Router
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