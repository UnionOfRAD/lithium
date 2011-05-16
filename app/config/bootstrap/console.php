<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\console\Dispatcher;

Dispatcher::applyFilter('_call', function($self, $params, $chain) {
	$params['callable']->response->styles(array(
		'heading' => '\033[1;30;46m'
	));
	return $chain->next($self, $params, $chain);
});


?>