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
	 * cause the command to enter quite mode, surpressing headers and any other
	 * decoration.
	 *
	 * @var string Either `txt` or `json`.
	 */
	public $format = 'txt';

	/**
	 * Runs tests given a path to a directory or file containing tests. The path to the
	 * test(s) may be absolte or relative to the current working directory.
	 *
	 * {{{
	 * lithium test lithium/tests/cases/core/ObjectTest.php
	 * lithium test lithium/tests/cases/core
	 * }}}
	 *
	 * @param string $path Absolute or relative path to tests.
	 * @return boolean Will exit with status `1` if one or more tests failed otherwise with `0`.
	 */
	public function run($path = null) {
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
		$filters = $this->filters ? array_map('trim', explode(',', $this->filters)) : array();

		if (!$libraryPath = $this->_library($path)) {
			$this->error("No library registered for path `{$path}`.");
			return false;
		}
		$path = $libraryPath;

		if ($this->format == 'txt') {
			$this->header('Test');
			$this->out(sprintf('Running test(s) in `%s`... ', $path), array('nl' => false));
		}
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

		$report = Dispatcher::run($path, compact('filters') + array(
			'reporter' => 'console',
			'format' => $this->format
		));
		$stats = $report->stats();

		if ($this->format == 'txt') {
			$this->out('done.', 2);
			$this->out('{:heading}Results{:end}', 0);
		}
		$this->out($report->render('stats', $stats));

		foreach ($report->filters() as $filter => $options) {
			$data = $report->results['filters'][$filter];
			$this->out($report->render($options['name'], compact('data')));
		}

		if ($this->format == 'txt') {
			$this->hr();
			$this->nl();
		}
		return $stats['success'];
	}

	/**
	 * Finds a library for given path.
	 *
	 * @param string $path Normalized (to slashes) absolute or relative path.
	 * @return string|void The library's path on success.
	 */
	protected function _library($path) {
		foreach (Libraries::get() as $name => $library) {
			if (strpos($path, $library['path']) === 0) {
				$path = str_replace(array($library['path'], '.php'), null, $path);
				return '\\' . $name . str_replace('/', '\\', $path);
			}
		}
	}
}

?>