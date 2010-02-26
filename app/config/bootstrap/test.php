<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;
use lithium\action\Dispatcher;
use lithium\test\Dispatcher as TestDispatcher;

Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
	list($isTest, $test) = explode('/', $params['request']->url, 2) + array("", "");
	$request = $params['request'];
	if ($isTest === "test") {
		return function() use ($test, $request) {
			$group = "\\" . str_replace("/", "\\", $test);

			if ($group == "\\all") {
				$group = Libraries::locate('tests', null, array(
					'filter' => '/cases|integration|functional/',
					'exclude' => '/mocks/'
				));
				$group = array_map(function($test) {
					$path = explode("\\", $test);
					return "\\" . array_shift($path);
				}, $group);
				$group = array_unique($group);
			}

			$report = TestDispatcher::run($group , $request->query + array(
				'reporter' => 'html',
				'format' => 'html'
			));
			$filters = Libraries::locate('test.filter', null, array(
				'exclude' => '/Base$/'
			));
			$menu = Libraries::locate('tests', null, array(
				'filter' => '/cases|integration|functional/',
				'exclude' => '/mocks/'
			));
			sort($menu);

			$result = compact('request', 'group', 'report', 'filters', 'classes', 'menu');

			return $report->render('layout', $result);
		};
	}

	return $chain->next($self, $params, $chain);
});

?>