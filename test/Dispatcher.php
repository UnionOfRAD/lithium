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
use lithium\util\Set;
use lithium\core\Libraries;
use lithium\core\Environment;

/**
 * The Lithium Test Dispatcher
 *
 * This Dispatcher is used exclusively for the purpose of running, organizing and compiling
 * statistics for the built-in Lithium test suite.
 */
class Dispatcher {

	/**
	 * Composed classes used by the Dispatcher.
	 *
	 * @var array Key/value array of short identifier for the fully-namespaced class.
	 */
	protected static $_classes = [
		'group' => 'lithium\test\Group',
		'report' => 'lithium\test\Report'
	];

	/**
	 * Runs a test group or a specific test file based on the passed
	 * parameters.
	 *
	 * @param string $group If set, this test group is run. If not set, a group test may
	 *        also be run by passing the 'group' option to the $options parameter.
	 * @param array $options Options array for the test run. Valid options are:
	 *        - `'case'`: The fully namespaced test case to be run.
	 *        - `'group'`: The fully namespaced test group to be run.
	 *        - `'filters'`: An array of filters that the test output should be run through.
	 *        - `'format'`: The format of the template to use, defaults to `'txt'`.
	 *        - `'reporter'`: The reporter to use.
	 * @return object A Report object.
	 * @filter
	 */
	public static function run($group = null, array $options = []) {
		$defaults = [
			'title' => $group,
			'filters' => [],
			'format' => 'txt',
			'reporter' => null
		];
		$options += $defaults;
		$isCase = is_string($group) && preg_match('/Test$/', $group);
		$items = ($isCase) ? [new $group()] : (array) $group;

		$options['filters'] = array_map(function($v) {
			return (array) $v;
		}, Set::normalize($options['filters']));

		$group = static::_group($items);
		$report = static::_report($group, $options);

		return Filters::run(get_called_class(), __FUNCTION__, compact('report'), function($params) {
			$environment = Environment::get();
			Environment::set('test');

			$params['report']->run();

			Environment::set($environment);
			return $params['report'];
		});
	}

	/**
	 * Creates the group class based
	 *
	 * @see lithium\test\Dispatcher::$_classes
	 * @param array $data Array of cases or groups.
	 * @return object Group object constructed with `$data`.
	 */
	protected static function _group($data) {
		return Libraries::instance('test', static::$_classes['group'], compact('data'));
	}

	/**
	 * Creates the test report class based on either the passed test case or the
	 * passed test group.
	 *
	 * @see lithium\test\Dispatcher::$_classes
	 * @param string $group
	 * @param array $options Options array passed from Dispatcher::run(). Should contain
	 *        one of 'case' or 'group' keys.
	 * @return object Group object constructed with the test case or group passed in $options.
	 */
	protected static function _report($group, $options) {
		return Libraries::instance('test', static::$_classes['report'], compact('group') + $options);
	}
}

?>