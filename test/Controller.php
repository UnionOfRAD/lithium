<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use lithium\test\Dispatcher;
use lithium\core\Libraries;
use lithium\test\Group;
use lithium\util\Set;
use lithium\net\http\Router;

/**
 * The Test Controller for running the html version of the test suite
 *
 */
class Controller extends \lithium\core\Object {

	/**
	 * Saved context.
	 *
	 * @var array
	 */
	protected $_context = array();

	/**
	 * Magic method to make Controller callable.
	 *
	 * @see lithium\action\Dispatcher::_callable()
	 * @param object $request A \lithium\action\Request object.
	 * @param array $dispatchParams Array of params after being parsed by router.
	 * @param array $options Some basic options for this controller.
	 * @return string
	 * @filter
	 */
	public function __invoke($request, $dispatchParams, array $options = array()) {
		$dispatchParamsDefaults = array('args' => array());
		$dispatchParams += $dispatchParamsDefaults;
		$defaults = array('format' => 'html', 'timeout' => 0);
		$options += (array) $request->query + $defaults;
		$params = compact('request', 'dispatchParams', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) {
			$request = $params['request'];
			$options = $params['options'];
			$params = $params['dispatchParams'];
			set_time_limit((integer) $options['timeout']);
			$group = join('\\', (array) $params['args']);

			if ($group === "all") {
				$group = Group::all();
				$options['title'] = 'All Tests';
			}

			$self->invokeMethod('_saveCtrlContext');
			$report = Dispatcher::run($group, $options);
			$self->invokeMethod('_restoreCtrlContext');

			$filters = Libraries::locate('test.filter');
			$menu = Libraries::locate('tests', null, array(
				'filter' => '/cases|integration|functional/',
				'exclude' => '/mocks/'
			));
			sort($menu);
			$menu = Set::expand(array_combine($menu, $menu), array('separator' => "\\"));
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