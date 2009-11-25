<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \lithium\test\Reporter;
use \lithium\test\Dispatcher;
use \lithium\core\Libraries;

/**
 * Controller for reporting test results in html
 *
 */
class Controller extends \lithium\core\Object {

	/**
	 * undocumented function
	 *
	 * @param string $request
	 * @param string $params
	 * @param string $options
	 * @return void
	 */
	public function __invoke($request, $params, $options = array()) {
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
		$report = Dispatcher::run(null, $request->query + array(
			'reporter' => 'html', 'group' => '\\' . join('\\', $request->args)
		));
		$filters = Libraries::locate('test.filters');
		$classes = Libraries::locate('tests', null, array(
			'filter' => '/cases|integration|functional/'
		));
		$menu = $report->reporter->menu($classes, array('format' => 'html', 'tree' => true));

		$template = Libraries::locate('test.reporter.templates', 'layout', array(
			'filter' => false, 'type' => 'file', 'suffix' => '.html.php',
		));
		include($template);
	}
}

?>