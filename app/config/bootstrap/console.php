<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\console\Dispatcher as ConsoleDispatcher;
use \lithium\util\String;

ConsoleDispatcher::applyFilter('_call', function($self, $params, $chain) {
	$params['callable']->response->styles(array(
		'heading' => 'changed'
	));
	return $chain->next($self, $params, $chain);
});