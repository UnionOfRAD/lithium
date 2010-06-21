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
	 * An array key on fully-namespaced class names of the filter with options to be
	 * applied for the filter as the value
	 *
	 * @var array
	 */
	protected $_filters = array();

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
			'reporter' => 'console',
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	protected function _init() {
		$this->group = $this->_config['group'];
		$this->title = $this->_config['title'] ?: $this->_config['title'];
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	public function run() {
		$tests = $this->group->tests();

		foreach ($this->filters() as $filter => $options) {
			$this->results['filters'][$filter] = array();
			$tests = $filter::apply($this, $tests, $options['apply']) ?: $tests;
		}
		$this->results['group'] = $tests->run();

		foreach ($this->filters() as $filter => $options) {
			$this->results['filters'][$filter] = $filter::analyze($this, $options['analyze']);
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
		$this->results['filters'][$class][] = $results;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 */
	public function stats() {
		$results = (array) $this->results['group'];
		$defaults = array(
			'asserts' => 0,
			'passes' => array(),
			'fails' => array(),
			'exceptions' => array(),
			'errors' => array(),
			'skips' => array()
		);
		$stats = array_reduce($results, function($stats, $result) use ($defaults) {
			$stats = (array) $stats + $defaults;
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
		$stats = (array) $stats + $defaults;
		$count = array_map(
			function($value) { return is_array($value) ? count($value) : $value; }, $stats
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

	public function filters(array $filters = array()) {
		if (!empty($this->_filters) && empty($filters)) {
			return $this->_filters;
		}
		$filters += (array) $this->_config['filters'];
		$results = array();
		foreach ($filters as $filter => $options) {
			if (!$class = Libraries::locate('test.filter', $filter)) {
				throw new Exception("{$class} is not a valid test filter.");
			}
			$options['name'] = strtolower(join('', array_slice(explode("\\", $class), -1)));
			$results[$class] = $options + array('apply' => array(), 'analyze' => array());
		}
		return $this->_filters = $results;
	}
}

?>