<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \Exception;
use \lithium\core\Libraries;
use \lithium\util\Inflector;

/**
 * Report object for running group tests holding results
 *
 * @package default
 */
class Report extends \lithium\core\Object {

	/**
	 * Contains an instance of `lithium\test\Group`, which contains all unit tests to be executed
	 * this test run.
	 *
	 * @var object
	 */
	public $group = null;

	/**
	 * An array of fully-namespaced class names representing the filters to be applied to this test
	 * group.
	 *
	 * @var array
	 */
	public $filters = array();

	/**
	 * Title of the group being run
	 *
	 * @var string
	 */
	public $title = null;

	/**
	 * group and filter results
	 *
	 * @var array
	 */
	public $results = array('group' => array(), 'filters' => array());

	/**
	 * start and end timers
	 *
	 * @var array
	 */
	public $timer = array('start' => null, 'end' => null);

	/**
	 * Construct Report Object
	 *
	 * @param array $config Options array for the test run. Valid options are:
	 *        - 'group': The test group with items to be run.
	 *		  - 'filters': An array of filters that the test output should be run through.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'title' => null,
			'group' => null,
			'filters' => array(),
			'format' => 'txt',
			'reporter' => 'console'
		);
		parent::__construct((array) $config + $defaults);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	protected function _init() {
		$this->group = $this->_config['group'];
		$this->filters = $this->_config['filters'];
		$this->title = $this->_config['title'] ?: $this->_config['title'];
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	public function run() {
		$tests = $this->group->tests();
		$filters = array();

		foreach ($this->filters as $filter => $options) {
			if (!strpos($filter, "\\")) {
				$filter = ucwords($filter);
			}
			if (!$class = Libraries::locate('test.filter', $filter)) {
				throw new Exception("{$class} is not a valid test filter.");
			}
			$options = isset($options['apply']) ? $options['apply'] : array();
			$tests = $class::apply($this, $tests, $options) ?: $tests;
			$filters[] = compact('class', 'options');
		}
		$this->results['group'] = $tests->run();

		foreach ($filters as $filter) {
			if (isset($filter['options']['analyze'])) {
				$filter['options'] = $filter['options']['analyze'];
			} else {
				$filter['options'] = array();
			}
			if (!isset($this->results['filters'][$filter['class']])) {
				$this->results['filters'][$filter['class']] = array();
			}
			$this->results['filters'][$filter['class']] = $filter['class']::analyze(
				$this,
				$filter['options']
			);
		}
	}

	/**
	 * Collects Results from the test filters and aggregates them.
	 *
	 * @param string $class Classname of the filter for which to aggregate results.
	 * @param array $results Array of the filter results for
	 *				later analysis by the filter itself.
	 * @return void
	 */
	public function collect($class, $results) {
		if (!isset($this->results['filters'][$class])) {
			$this->results['filters'][$class] = array();
		}
		$this->results['filters'][$class][] = $results;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	public function stats() {
		$results = (array) $this->results['group'];
		$stats = array_reduce($results, function($stats, $result) {
			$stats = (array) $stats + array(
				'asserts' => 0,
				'passes' => array(),
				'fails' => array(),
				'exceptions' => array(),
				'errors' => array(),
				'skips' => array()
			);
			$result = empty($result[0]) ? array($result) : $result;
			foreach ($result as $response) {
				if (empty($response['result'])) {
					continue;
				}
				$result = $response['result'];

				if (in_array($result, array('fail', 'exception'))) {
					$response = array_merge(
						array('class' => 'unknown', 'method' => 'unknown'), $response
					);
					$stats['errors'][] = $response;
				}
				unset($response['file'], $response['result']);

				if (in_array($result, array('pass', 'fail'))) {
					$stats['asserts']++;
				}
				if (in_array($result, array('pass', 'fail', 'exception', 'skip'))) {
					$stats[Inflector::pluralize($result)][] = $response;
				}
			}
			return $stats;
		});
		$stats = (array) $stats + array(
			'asserts' => null,
			'passes' => array(),
			'fails' => array(),
			'errors' => array(),
			'exceptions' => array(),
			'skips' => array()
		);
		$count = array_map(
			function($value) { return is_array($value) ? count($value) : $value; },
			$stats
		);
		$success = $count['passes'] == $count['asserts'] && $count['errors'] === 0;
		return compact("stats", "count", "success");
	}

	/**
	 * Renders the test output (e.g. layouts and filter templates)
	 *
	 * @param string $template name of the template (eg: layout)
	 * @param string $data array from `_data()` method
	 * @param array $options Array of options (e.g. rendering type)
	 * @return string
	 */
	public function render($template, $data = array()) {
		$config = $this->_config;
		if ($template == "stats") {
			$data = $this->stats();
		}
		$template = Libraries::locate("test.templates.{$config['reporter']}", $template, array(
			'filter' => false, 'type' => 'file', 'suffix' => ".{$config['format']}.php",
		));
		$params = compact('template', 'data', 'config');

		return $this->_filter(__METHOD__, $params, function($self, $params, $chain) {
			extract($params['data']);
			ob_start();
			include $params['template'];
			return ob_get_clean();
		});
	}

	/**
	 * Loops through and renders each filter that currently has stored results
	 *
	 * @param string $separator Optional separator string to join the templates on
	 * @return string|boolean
	 */
	public function filters($separator = null) {
		$output = false;
		foreach ($this->results['filters'] as $filter => $analysis) {
			$filterClass = explode("\\", $filter);
			$filterClass = array_pop($filterClass);
			$output .= $separator . $this->render(strtolower($filterClass), compact('analysis'));
		}

		return $output;
	}
}

?>