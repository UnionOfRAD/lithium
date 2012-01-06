<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Test;

use Lithium\Test\Dispatcher;
use Lithium\Core\Libraries;
use Lithium\Test\Group;

/**
 * The Test Controller for running the html version of the test suite
 *
 */
class Controller extends \Lithium\Core\Object {

	/**
	 * Magic method to make Controller callable.
	 *
	 * @see Lithium\Action\Dispatcher::_callable()
	 * @param object $request A \Lithium\Action\Request object.
	 * @param array $dispatchParams Array of params after being parsed by router.
	 * @param array $options Some basic options for this controller.
	 * @return string
	 * @filter
	 */
	public function __invoke($request, $dispatchParams, array $options = array()) {
		$dispatchParamsDefaults = array('args' => array());
		$dispatchParams += $dispatchParamsDefaults;
		$defaults = array('reporter' => 'Html', 'format' => 'html', 'timeout' => 0);
		$options += (array) $request->query + $defaults;
		$params = compact('request', 'dispatchParams', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) {
			$request = $params['request'];
			$options = $params['options'];
			$params = $params['dispatchParams'];
			set_time_limit((integer) $options['timeout']);
			$group = join('\\', array_map("ucfirst",(array) $params['args']));

			if ($group === "all") {
				$group = Group::all();
				$options['title'] = 'All Tests';
			}

			$report = Dispatcher::run($group, $options);
			$filters = Libraries::locate('Test.Filter');
			$menu = Libraries::locate('Tests', null, array(
				'filter' => '/\\\(Cases|Integration|Functional)\\\/',
				'exclude' => '/Mocks/'
			));
			sort($menu);

			$result = compact('request', 'report', 'filters', 'menu');
			return $report->render('layout', $result);
		});
	}
}

?>
