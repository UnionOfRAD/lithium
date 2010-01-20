<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \lithium\util\Set;
use \lithium\util\Inflector;
use \lithium\core\Libraries;

/**
 * The Lithium Test Dispatcher
 *
 * This Dispatcher is used exclusively for the purpose of running, organizing and compiling
 * statistics for the built-in Lithium test suite.
 */
class Dispatcher extends \lithium\core\StaticObject {

	/**
	 * Composed classes used by the Dispatcher.
	 *
	 * @var array Key/value array of short identifier for the fully-namespaced class.
	 */
	protected static $_classes = array(
		'group' => '\lithium\test\Group',
		'report' => '\lithium\test\Report'
	);

	/**
	 * Runs a test group or a specific test file based on the passed
	 * parameters.
	 *
	 * @param string $group If set, this test group is run. If not set, a group test may
	 *        also be run by passing the 'group' option to the $options parameter.
	 * @param array $options Options array for the test run. Valid options are:
	 *        - 'case': The fully namespaced test case to be run.
	 *        - 'group': The fully namespaced test group to be run.
	 *        - 'filters': An array of filters that the test output should be run through.
	 * @return array A compact array of the title, an array of the results, as well
	 *         as an additional array of the results after the $options['filters']
	 *         have been applied.
	 */
	public static function run($group = null, $options = array()) {
		$defaults = array(
			'title' => $group,
			'filters' => array(),
			'reporter' => 'text'
		);
		$options += $defaults;

		$items = (array) $group;
		$isCase = preg_match('/Test$/', $group);

		if ($isCase) {
			$items = array(new $group());
		}
		$options['filters'] = Set::normalize($options['filters']);
		$group = static::_group($items);
		$report = static::_report($group, $options);
		$report->run();
		return $report;
	}

	/**
	 * Creates the group class based
	 *
	 * @param array $items array of cases or groups
	 * @return object Group object constructed with $items
	 * @see \lithium\test\Dispatcher::$_classes
	 */
	protected static function _group($items) {
		$group = Libraries::locate('test', static::$_classes['group']);
		$class = new $group(compact('items'));
		return $class;
	}

	/**
	 * Creates the test report class based on either the passed test case or the
	 * passed test group.
	 *
	 * @param string $group
	 * @param array $options Options array passed from Dispatcher::run(). Should contain
	 *        one of 'case' or 'group' keys.
	 * @return object Group object constructed with the test case or group passed in $options.
	 * @see \lithium\test\Dispatcher::$_classes
	 */
	protected static function _report($group, $options) {
		$report = Libraries::locate('test', static::$_classes['report']);
		$class = new $report(compact('group') + $options);
		return $class;
	}
}

?>