<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command;

use \lithium\core\Libraries;
use \lithium\test\Group;
use \lithium\test\Dispatcher;
use \lithium\analysis\Inspector;

/**
 * Runs a given set of tests and outputs the results.
 */
class Test extends \lithium\console\Command {

	/**
	 * Path to the test case in dot notation.
	 *
	 * For example:
	 * {{{
	 * lithium test --case=console.CommandTest
	 * }}}
	 *
	 * @var string
	 */
	public $case = null;

	/**
	 * Path to test group in dot notation.
	 *
	 * For example:
	 * {{{
	 * lithium test --group=lithium.tests.cases.console
	 * }}}
	 *
	 * @var string
	 */
	public $group = null;

	/**
	 * Filters.
	 *
	 * For example:
	 * {{{
	 * lithium test --case=lithium.tests.cases.core.ObjectTest --filters=Coverage
	 * }}}
	 *
	 * @var string
	 */
	public $filters = array();

	/**
	 * Runs tests. Will provide a list of available tests if none are given.
	 * Test cases should be given in dot notation.
	 *
	 * Case example:
	 * {{{
	 * lithium test --case=lithium.tests.cases.core.ObjectTest
	 * }}}
	 *
	 * Group example:
	 * {{{
	 * lithium test --group=lithium.tests.cases.core
	 * }}}
	 *
	 * @return void
	 */
	public function run() {
		$this->header('Test');

		if ($this->_getTests() != true) {
			return 0;
		}
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

		$run = $this->case ?: $this->group;
		$run = '\\' . str_replace('.', '\\', $run);
		$this->out(sprintf('Running `%s`... ', $run), false);

		$report = Dispatcher::run($run, array(
			'filters' => $this->filters,
			'reporter' => 'console',
			'format' => 'txt'
		));
		$this->out('done.', 2);
		$this->out('{:heading1}Results{:end}', 0);
		$this->out($report->render('stats'));

		foreach ($report->filters() as $filter => $options) {
			$data = $report->results['filters'][$filter];
			$this->out($report->render($options['name'], compact('data')));
		}

		$this->hr();
		$this->nl();
	}

	/**
	 * Shows which classes are un-tested.
	 *
	 * @return void
	 */
	public function missing() {
		$this->header('Classes with no test case');

		$classes = Libraries::find(true, array(
			'recursive' => true,
			'exclude' => '/\w+Test$|webroot|index$|^app\\\\config|^app\\\\views/'
		));
		$tests = Group::all();
		$classes = array_diff($classes, $tests);

		sort($classes);
		$this->out($classes);
	}

	/**
	 * Show included files.
	 *
	 * @return void
	 */
	public function included() {
		$this->header('Included Files');
		$base = dirname(dirname(dirname(dirname(__DIR__))));
		$files = str_replace($base, '', get_included_files());
		sort($files);
		$this->out($files);
	}

	/**
	 * Provide a list of test cases and accept input as case to run.
	 *
	 * @return void
	 */
	protected function _getTests() {
		while (empty($this->case) && empty($this->group)) {
			$tests = Libraries::locate('tests', null, array(
				'filter' => '/cases|integration|functional/',
				'exclude' => '/mocks/'
			));
			$tests = str_replace('\\', '.', $tests);

			foreach ($tests as $key => $test) {
				$this->out(++$key . ". " . $test);
			}
			$number = $this->in("Choose a test case. (q to quit)");

			if (isset($tests[--$number])) {
				$this->case = $tests[$number];
			}

			if ($number == 'q') {
				return 0;
			}
		}
		return 1;
	}
}

?>