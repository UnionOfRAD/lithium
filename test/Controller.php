<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\test;

use lithium\aop\Filters;
use lithium\test\Dispatcher;
use lithium\core\Libraries;
use lithium\test\Group;
use lithium\util\Set;
use lithium\net\http\Router;
use lithium\core\AutoConfigurable;

/**
 * The Test Controller for running the html version of the test suite
 *
 */
class Controller {

	use AutoConfigurable;

	/**
	 * Saved context.
	 *
	 * @var array
	 */
	protected $_context = [];

	/**
	 * Magic method to make Controller callable.
	 *
	 * @see lithium\action\Dispatcher::_callable()
	 * @param \lithium\action\Request $request
	 * @param array $dispatchParams Array of params after being parsed by router.
	 * @param array $options Some basic options for this controller.
	 * @return string
	 * @filter
	 */
	public function __invoke($request, $dispatchParams, array $options = []) {
		$dispatchParamsDefaults = ['args' => []];
		$dispatchParams += $dispatchParamsDefaults;
		$defaults = ['format' => 'html', 'timeout' => 0];
		$options += (array) $request->query + $defaults;
		$params = compact('request', 'dispatchParams', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$request = $params['request'];
			$options = $params['options'];
			$params = $params['dispatchParams'];
			set_time_limit((integer) $options['timeout']);
			$group = join('\\', (array) $params['args']);

			if ($group === "all") {
				$group = Group::all();
				$options['title'] = 'All Tests';
			}

			$this->_saveCtrlContext();
			$report = Dispatcher::run($group, $options);
			$this->_restoreCtrlContext();

			$filters = Libraries::locate('test.filter');
			$menu = Libraries::locate('tests', null, [
				'filter' => '/cases|integration|functional/',
				'exclude' => '/mocks/'
			]);
			sort($menu);
			$menu = Set::expand(array_combine($menu, $menu), ['separator' => "\\"]);
			$result = compact('request', 'report', 'filters', 'menu');
			return $report->render('layout', $result);
		});
	}

	protected function _saveCtrlContext() {
		$this->_context['scope'] = Router::scope(false);
		$this->_context['routes'] = Router::get();
		$this->_context['scopes'] = Router::attached();
		Router::reset();
	}

	protected function _restoreCtrlContext() {
		Router::reset();
		foreach ($this->_context['routes'] as $scope => $routes) {
			Router::scope($scope, function() use ($routes) {
				foreach ($routes as $route) {
					Router::connect($route);
				}
			});
		}
		foreach ($this->_context['scopes'] as $scope => $attachment) {
			Router::attach($scope, $attachment);
		}
		Router::scope($this->_context['scope']);
	}
}

?>