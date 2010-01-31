<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \lithium\test\Dispatcher;
use \lithium\core\Libraries;

/**
 * Controller for reporting test results in html
 *
 */
class Controller extends \lithium\core\Object {

	/**
	 * Invoke the `_data()` and `_render()` methods inside of a method filter
	 *
	 * @param string $request
	 * @param string $params
	 * @param string $options
	 * @return void
	 */
	public function __invoke($request, $params, $options = array()) {
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
		$filter = function($self, $params, $chain) {
			try {
				$result = $self->invokeMethod('_data', $params);
				return $self->invokeMethod('_render', array('layout', $result));
			} catch (Exception $e) {
				throw $e;
			}
		};
		echo $this->_filter(__METHOD__, array($request, $params, $options), $filter);
	}

	/**
	 * Base method for gathering data
	 *
	 * @param string $request
	 * @param string $params
	 * @param string $options
	 * @return array request, group, report, filters, classes, menu
	 */
	protected function _data($request, $params, $options) {
		$group = '\\' . join('\\', $request->args);
		$report = Dispatcher::run($group , $request->query + array(
			'reporter' => 'html'
		));
		$filters = Libraries::locate('test.filter', null, array(
			'exclude' => '/Base$/'
		));
		$classes = Libraries::locate('tests', null, array(
			'filter' => '/cases|integration|functional/',
			'exclude' => '/mocks/'
		));
		$menu = $report->reporter->menu($classes, array(
			'request' => $request, 'tree' => true
		));
		return compact('request', 'group', 'report', 'filters', 'classes', 'menu');
	}

	/**
	 * Grab a the `layout.html.php` template and return output
	 *
	 * @param string $template name of the template (eg: layout)
	 * @param string $data array from `_data()` method
	 * @return string
	 */
	protected function _render($template, $data) {
		$template = Libraries::locate('test.reporter.template', $template, array(
			'filter' => false, 'type' => 'file', 'suffix' => '.html.php',
		));
		extract($data);
		ob_start();
		include $template;
		return ob_get_clean();
	}
}

?>