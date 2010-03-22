<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;
use lithium\action\Dispatcher;
use lithium\test\Controller;

Dispatcher::applyFilter('run', function($self, $params, $chain) {
	list($isTest, $args) = explode('/', $params['request']->url, 2) + array("", "");
	$request = $params['request'];

	if ($isTest === "test") {
		$controller = new Controller();
		$args = str_replace('/', '\\', $args);
		return $controller($request, compact('args'));
	}
	return $chain->next($self, $params, $chain);
});

?>