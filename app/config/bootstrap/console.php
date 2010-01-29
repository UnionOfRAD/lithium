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
	$params['callable']->response->applyFilter('output', function($self, $params, $chain) {	
		$styles = array(
			/** General **/
			'heading1' => "\033[1;36m",
			'heading2' => "\033[1;34m",
			'heading3' => "\033[1;35m",
			'option'   => "\033[1;33m",
			'command'  => "\033[1;32m",
			/** Colors **/
			'black'  => "\033[0;30m",
			'red'    => "\033[0;31m",
			'green'  => "\033[0;32m",
			'yellow' => "\033[0;33m",
			'blue'   => "\033[0;34m",
			'purple' => "\033[0;35m",
			'cyan'   => "\033[0;36m",
			'white'  => "\033[0;37m",
			'end'    => "\033[0m",
		);
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			array_walk($styles, function(&$value, $key) {
				$value = '';
			});
		}
		$params['string'] = String::insert($params['string'], $styles);
		return $chain->next($self, $params, $chain);
	}); 
	return $chain->next($self, $params, $chain);
});