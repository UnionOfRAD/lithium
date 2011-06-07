<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command;

use lithium\core\Libraries;
use lithium\test\Dispatcher;

/**
 * Runs a given set of tests and outputs the results.
 *
 * @see lithium\test
 */
class Test extends \lithium\console\Command {

	/**
	 * Filters.
	 *
	 * For example:
	 * {{{
	 * lithium test lithium/tests/cases/core/ObjectTest.php --filters=Coverage
	 * lithium test lithium/tests/cases/core/ObjectTest.php --filters=Coverage,Profiler
	 * }}}
	 *
	 * @var string Name of a filter or a comma separated list of filter names. Builtin filters:
	 *      - `Affected`:   Adds tests to the run affected by the classes covered by current tests.
	 *      - `Complexity`: Calculates the cyclomatic complexity of class methods, and shows
	 *                      worst-offenders and statistics.
	 *      - `Coverage`:   Runs code coverage analysis for the executed tests.
	 *      - `Profiler`:   Tracks timing and memory usage information for each test method.
	 */
	public $filters;

	/**
	 * Format to use for rendering results. Any other format than `txt` will
	 * cause the command to enter quiet mode, surpressing headers and any other
	 * decoration.
	 *
	 * @var string Either `txt` or `json`.
	 */
	public $format = 'txt';

	/**
	 * An array of closures, mapped by type, which are set up to handle different test output
	 * formats.
	 *
	 * @var array
	 */
	protected $_handlers = array();

	/**
	 * Initializes the output handlers.
	 *
	 * @see lithium\console\command\Test::$_handlers
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		$self = $this;
		$this->_handlers += array(
			'txt' => function($runner, $path) use ($self) {
				$message = sprintf('Running test(s) in `%s`... ', ltrim($path, '\\'));
				$self->header('Test');
				$self->out($message, array('nl' => false));

				$report = $runner();
				$self->out('done.', 2);
				$self->out('{:heading}Results{:end}', 0);
				$self->out($report->render('stats', $report->stats()));

				foreach ($report->filters() as $filter => $options) {
					$data = $report->results['filters'][$filter];
					$self->out($report->render($options['name'], compact('data')));
				}

				$self->hr();
				$self->nl();
				return $report;
			},
			'json' => function($runner, $path) use ($self) {
				$report = $runner();

				if ($results = $report->filters()) {
					$filters = array();

					foreach ($results as $filter => $options) {
						$filters[$options['name']] = $report->results['filters'][$filter];
					}
				}
				$self->out($report->render('stats', $report->stats() + compact('filters')));
				return $report;
			}
		);
	}

	/**
	 * Runs tests given a path to a directory or file containing tests. The path to the
	 * test(s) may be absolute or relative to the current working directory.
	 *
	 * {{{
	 * li3 test lithium/tests/cases/core/ObjectTest.php
	 * li3 test lithium/tests/cases/core
	 * }}}
	 *
	 * If you are in the working directory of an application or plugin and wish to run all tests,
	 * simply execute the following:
	 *
	 * {{{
	 * li3 test tests/cases
	 * }}}
	 *
	 * @param string $path Absolute or relative path to tests.
	 * @return boolean Will exit with status `1` if one or more tests failed otherwise with `0`.
	 */
	public function run($path = null) {
		if (!$path = $this->_path($path)) {
			return false;
		}
		$handlers = $this->_handlers;

		if (!isset($handlers[$this->format]) || !is_callable($handlers[$this->format])) {
			$this->error(sprintf('No handler for format `%s`... ', $this->format));
			return false;
		}
		$filters = $this->filters ? array_map('trim', explode(',', $this->filters)) : array();
		$params = compact('filters') + array('reporter' => 'console', 'format' => $this->format);

		$runner = function() use ($path, $params) {
			error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
			return Dispatcher::run($path, $params);
		};
		$report = $handlers[$this->format]($runner, $path);
		$stats = $report->stats();
		return $stats['success'];
	}

	/**
	 * Finds a library for given path.
	 *
	 * @param string $path Normalized (to slashes) absolute or relative path.
	 * @return string Returns the library's path on success, or `null` on failure.
	 */
	protected function _library($path) {
		foreach (Libraries::get() as $name => $library) {
			if (strpos($path, $library['path']) !== 0) {
				continue;
			}
			$path = str_replace(array($library['path'], '.php'), null, $path);
			return '\\' . $name . str_replace('/', '\\', $path);
		}
	}

	/**
	 * Validates an absolute or relative path to test cases.
	 *
	 * @param string $path The directory or file path to one or more test cases
	 * @return string Returns a fully-resolved physical path, or `false`, if an error occurs.
	 */
	protected function _path($path) {
		$path = str_replace('\\', '/', $path);

		if (!$path) {
			$this->error('Please provide a path to tests.');
			return false;
		}
		if ($path[0] != '/') {
			$path = $this->request->env('working') . '/' . $path;
		}
		if (!$path = realpath($path)) {
			$this->error('Not a valid path.');
			return false;
		}

		if (!$libraryPath = $this->_library($path)) {
			$this->error("No library registered for path `{$path}`.");
			return false;
		}
		return $libraryPath;
	}
}

?>