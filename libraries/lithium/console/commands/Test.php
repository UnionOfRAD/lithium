<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\commands;

use \lithium\core\Libraries;
use \lithium\test\Group;
use \lithium\test\Dispatcher;
use \lithium\util\reflection\Inspector;

/**
 * Runs a given set unit tests and outputs the results.
 */
class Test extends \lithium\console\Command {

	/**
	 * path to test case in dot notation
	 * example: lithium test -case console.CommandTest
	 *
	 * @var string
	 */
	public $case = null;

	/**
	 * path to test group in dot notation
	 * example: lithium test -group console
	 *
	 * @var string
	 */
	public $group = null;

	/**
	 * filters
	 *
	 * @var string
	 */
	public $filters = array();

	/**
	 * Runs tests. Will provide a list of available tests if none are give
	 * Test cases should be given in dot notation.
	 * case example: lithium test -case lithium.tests.cases.core.ObjectTest
	 * group example: lithium test -group lithium.tests.cases.core
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
			$this->case = 'lithium\tests\cases\\' . str_replace('.', '\\',
				str_replace('lithium.tests.cases.', '', $this->case)
			);
		}
		if (!empty($this->group)) {
			$this->group = '\\' . str_replace('.', '\\',
				str_replace('lithium.tests.cases.', '', $this->group)
			);
		}
		$report = Dispatcher::run(null, array(
			'case' => $this->case, 'group' => $this->group,
			'filters' => $this->filters
		));

		$this->header('Included Files');
		$base = dirname(dirname(dirname(dirname(__DIR__))));
		$files = str_replace($base, '', get_included_files());
		sort($files);
		$this->out($files);

		$this->header($report->title);

		$this->out($report->stats());

		$this->out($report->filters());

		$again = $this->in("Would you like to run this test again?", array(
			'choices' => array('y', 'n'),
			'default' => 'y'
		));
		if ($again == 'y') {
			return $this->run();
		}

		$another = $this->in("Would you like to run another test?", array(
			'choices' => array('y', 'n'),
			'default' => 'y'
		));
		if ($another == 'y') {
			$this->case = $this->group = null;
			return $this->run();
		}
	}
	/**
	 * Shows which classes are un-tested
	 *
	 * @return void
	 */
	public function missing() {
		$tests = Group::all();
		$this->header('Classes with no test case');
		$classFilter = '/\w+Test$|webroot|index$|^app\\\\config|^app\\\\views/';
		$classes = array_diff(
			Libraries::find(true, array('exclude' => $classFilter, 'recursive' => true)),
			$tests
		);
		sort($classes);
		$this->out($classes);
	}

	/**
	 * Provide a list of test cases and accept input as case to run
	 *
	 * @return void
	 */
	protected function _getTests() {
		while (empty($this->case) && empty($this->group)) {
			$tests = Libraries::locate('tests', null, array(
				'filter' => '/cases|integration|functional/'
			));
			$tests = str_replace('\\', '.',
				str_replace('lithium\tests\cases\\', '', $tests)
			);
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