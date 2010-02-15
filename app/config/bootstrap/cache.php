<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\storage\Cache;
use lithium\storage\cache\adapter\Apc;
use lithium\core\Libraries;
use lithium\action\Dispatcher;

Cache::config(array(
	'default' => array(
		'adapter' => '\lithium\storage\cache\adapter\\' . (Apc::enabled() ? 'Apc' : 'File')
	)
));

Dispatcher::applyFilter('run', function($self, $params, $chain) {
	if ($cache = Cache::read('default', 'core.libraryCache')) {
		$cache = (array) unserialize($cache) + Libraries::cache();
		Libraries::cache($cache);
	}
	$result = $chain->next($self, $params, $chain);

	if ($cache != Libraries::cache()) {
		Cache::write('default', 'core.libraryCache', serialize(Libraries::cache()), '+1 day');
	}
	return $result;
});

?>