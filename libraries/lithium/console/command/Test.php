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
 * Runs a given set of unit tests and outputs the results.
 *
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
	 * Group example:
	 * {{{
	 * lithium test --group=lithium.tests.cases.core
	 * }}}
	 *
	 * @return void
	 */
	public function run() {
		if ($this->_getTests() != true) {
			return 0;
		}
		$startBenchmark = microtime(true);

		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

		if (!empty($this->case)) {
			$this->case = '\\' . str_replace('.', '\\', $this->case);
		} elseif (!empty($this->group)) {
			$this->group = '\\' . str_replace('.', '\\', $this->group);
		}

		$report = Dispatcher::run($this->case ?: $this->group, array(
			'filters' => $this->filters, 'reporter' => 'text'
		));

		$this->header($report->title);
		$this->out($report->stats());
		$this->out($report->filters());
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