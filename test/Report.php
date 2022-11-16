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
use lithium\core\Libraries;
use lithium\util\Inflector;
use lithium\core\AutoConfigurable;
use lithium\core\ClassNotFoundException;
use lithium\template\TemplateException;

/**
 * This `Report` object aggregates tests in a group and allows you to run said tests to
 * obtain the results and stats (passes, fails, exceptions, skips) of the test run.
 *
 * While Lithium already comes with a text-based as well as web-based test interface, you
 * may use or extend the `Report` class to create your own test report functionality. In
 * addition, you can also create your own custom templates for displaying results in a different
 * format, such as json.
 *
 * Example usage, for built-in HTML format:
 *
 * ```
 * $report = new Report([
 *     'title' => 'Test Report Title',
 *     'group' => new Group(['data' => ['lithium\tests\cases\net\http\MediaTest']]),
 *     'format' => 'html'
 * ]);
 *
 * $report->run();
 *
 * // Get the test stats:
 * $report->stats();
 *
 * // Get test results:
 * $report->results
 * ```
 *
 * You may also choose to filter the results of the test runs to obtain additional information.
 * For example, say you wish to calculate the cyclomatic complexity of the classes you are testing:
 *
 * ```
 * $report = new Report([
 *     'title' => 'Test Report Title',
 *     'group' => new Group(['data' => ['lithium\tests\cases\net\http\MediaTest']]),
 *     'filters' => ['Complexity']
 * ]);
 *
 * $report->run();
 *
 * // Get test results, including filter results:
 * $report->results
 * ```
 *
 * @see lithium\test\Group
 * @see lithium\test\filter
 * @see lithium\test\templates
 */
class Report {

	use AutoConfigurable;

	/**
	 * Contains an instance of `lithium\test\Group`, which contains all unit tests to be executed
	 * this test run.
	 *
	 * @see lithium\test\Group
	 * @var object
	 */
	public $group = null;

	/**
	 * Title of the group being run.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Group and filter results.
	 *
	 * @var array
	 */
	public $results = ['group' => [], 'filters' => []];

	/**
	 * Start and end timers.
	 *
	 * @var array
	 */
	public $timer = ['start' => null, 'end' => null];

	/**
	 * An array key on fully-namespaced class names of the filter with options to be
	 * applied for the filter as the value
	 *
	 * @var array
	 */
	protected $_filters = [];

	/**
	 * Constructor.
	 *
	 * @param array $config Options array for the test run. Valid options are:
	 *        - `'group'`: The test group with items to be run.
	 *        - `'filters'`: An array of filters that the test output should be run through.
	 *        - `'format'`: The format of the template to use, defaults to `'txt'`.
	 *        - `'reporter'`: The reporter to use.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'title' => null,
			'group' => null,
			'filters' => [],
			'format' => 'txt',
			'reporter' => null
		];
		$this->_autoConfig($config + $defaults, []);
		$this->_autoInit($config);
	}

	/**
	 * Initializer.
	 *
	 * @return void
	 */
	protected function _init() {
		$this->group = $this->_config['group'];
		$this->title = $this->_config['title'] ?: $this->_config['title'];
		$this->_filters = $this->filters($this->_config['filters']);
	}

	/**
	 * Runs tests.
	 *
	 * @return void
	 */
	public function run() {
		$tests = $this->group->tests();

		foreach ($this->filters() as $filter => $options) {
			$this->results['filters'][$filter] = [];
			$tests = $filter::apply($this, $tests, $options['apply']) ?: $tests;
		}
		$this->results['group'] = $tests->run([
			'reporter' => $this->_config['reporter']
		]);

		foreach ($this->filters() as $filter => $options) {
			$this->results['filters'][$filter] = $filter::analyze($this, $options['analyze']);
		}
	}

	/**
	 * Collects Results from the test filters and aggregates them.
	 *
	 * @param string $class Classname of the filter for which to aggregate results.
	 * @param array $results Array of the filter results for
	 *              later analysis by the filter itself.
	 * @return void
	 */
	public function collect($class, $results) {
		$this->results['filters'][$class][] = $results;
	}

	/**
	 * Return statistics from the test runs.
	 *
	 * @return array
	 */
	public function stats() {
		$results = (array) $this->results['group'];
		$defaults = [
			'asserts' => 0,
			'passes' => [],
			'fails' => [],
			'exceptions' => [],
			'errors' => [],
			'skips' => []
		];
		$stats = array_reduce($results, function($stats, $result) use ($defaults) {
			$stats = (array) $stats + $defaults;
			$result = empty($result[0]) ? [$result] : $result;
			foreach ($result as $response) {
				if (empty($response['result'])) {
					continue;
				}
				$result = $response['result'];

				if (in_array($result, ['fail', 'exception'])) {
					$response = array_merge(
						['class' => 'unknown', 'method' => 'unknown'], $response
					);
					$stats['errors'][] = $response;
				}
				unset($response['file'], $response['result']);

				if (in_array($result, ['pass', 'fail'])) {
					$stats['asserts']++;
				}
				if (in_array($result, ['pass', 'fail', 'exception', 'skip'])) {
					$stats[Inflector::pluralize($result)][] = $response;
				}
			}
			return $stats;
		});
		$stats = (array) $stats + $defaults;
		$count = array_map(
			function($value) { return is_array($value) ? count($value) : $value; }, $stats
		);
		$success = $count['passes'] === $count['asserts'] && $count['errors'] === 0;
		return compact('stats', 'count', 'success');
	}

	/**
	 * Renders the test output (e.g. layouts and filter templates).
	 *
	 * @param string $template name of the template (i.e. `'layout'`).
	 * @param string|array $data array from `_data()` method.
	 * @return string
	 * @filter
	 */
	public function render($template, $data = []) {
		$config = $this->_config;

		if ($template === 'stats' && !$data) {
			$data = $this->stats();
		}
		$template = Libraries::locate('test.templates', $template, [
			'filter' => false, 'type' => 'file', 'suffix' => ".{$config['format']}.php"
		]);

		if ($template === null) {
			$message = "Templates for format `{$config['format']}` not found in `test/templates`.";
			throw new TemplateException($message);
		}
		$params = compact('template', 'data', 'config');

		return Filters::run(__CLASS__, __FUNCTION__, $params, function($params) {
			extract($params['data']);
			ob_start();
			include $params['template'];
			return ob_get_clean();
		});
	}

	/**
	 * Getter/setter for report test filters.
	 *
	 * @param array $filters A set of filters, mapping the filter class names, to their
	 *        corresponding array of options. When not provided, simply returns current
	 *        set of filters.
	 * @return array The current set of filters.
	 */
	public function filters(array $filters = []) {
		foreach ($filters as $filter => $options) {
			if (!$class = Libraries::locate('test.filter', $filter)) {
				throw new ClassNotFoundException("`{$class}` is not a valid test filter.");
			}
			$this->_filters[$class] = $options + [
				'name' => strtolower(join('', array_slice(explode("\\", $class), -1))),
				'apply' => [],
				'analyze' => []
			];
		}
		return $this->_filters;
	}
}

?>