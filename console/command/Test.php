<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console\command;

use lithium\core\Libraries;
use lithium\test\Dispatcher;
use lithium\test\Unit;

/**
 * Runs a given set of tests and outputs the results.
 *
 * @see lithium\test
 */
class Test extends \lithium\console\Command {

	/**
	 * Used as the exit code for errors where no test was mapped to file.
	 */
	const EXIT_NO_TEST = 4;

	/**
	 * List of filters to apply before/during/after test run, separated by commas.
	 *
	 * For example:
	 * ```sh
	 * lithium test lithium/tests/cases/core/LibrariesTest.php --filters=Coverage
	 * lithium test lithium/tests/cases/core/LibrariesTest.php --filters=Coverage,Profiler
	 * ```
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
	 * Enable verbose output especially for the `txt` format.
	 *
	 * @var boolean
	 */
	public $verbose = false;

	/**
	 * Prevent any headers or similar decoration being output.
	 * Good for command calls embedded into other scripts.
	 *
	 * @var boolean
	 */
	public $justAssertions = false;

	/**
	 * An array of closures, mapped by type, which are set up to handle different test output
	 * formats.
	 *
	 * @var array
	 */
	protected $_handlers = [];

	/**
	 * Initializes the output handlers.
	 *
	 * @see lithium\console\command\Test::$_handlers
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		$command = $this;

		$this->_handlers += [
			'txt' => function($runner, $path) use ($command) {
				if (!$command->justAssertions) {
					$command->header('Test');
					$command->out(null, 1);
				}
				$colorize = function($result) {
					switch (trim($result)) {
						case '.':
							return $result;
						case 'pass':
							return "{:green}{$result}{:end}";
						case 'F':
						case 'fail':
							return "{:red}{$result}{:end}";
						case 'E':
						case 'exception':
							return "{:purple}{$result}{:end}";
						case 'S':
						case 'skip':
							return "{:cyan}{$result}{:end}";
						default:
							return "{:yellow}{$result}{:end}";
					}
				};

				if ($command->verbose) {
					$reporter = function($result) use ($command, $colorize) {
						$command->out(sprintf(
							'[%s] on line %4s in %s::%s()',
							$colorize(sprintf('%9s', $result['result'])),
							isset($result['line']) ? $result['line'] : '??',
							isset($result['class']) ? $result['class'] : '??',
							isset($result['method']) ? $result['method'] : '??'
						));
					};
				} else {
					$i = 0;
					$columns = 60;

					$reporter = function($result) use ($command, &$i, $columns, $colorize) {
						$shorten = ['fail', 'skip', 'exception'];

						if ($result['result'] === 'pass') {
							$symbol = '.';
						} elseif (in_array($result['result'], $shorten)) {
							$symbol = strtoupper($result['result'][0]);
						} else {
							$symbol = '?';
						}
						$command->out($colorize($symbol), false);

						$i++;
						if ($i % $columns === 0) {
							$command->out();
						}
					};
				}
				$report = $runner(compact('reporter'));

				if (!$command->justAssertions) {
					$stats = $report->stats();

					$command->out(null, 2);
					$command->out($report->render('result', $stats));
					$command->out($report->render('errors', $stats));

					if ($command->verbose) {
						$command->out($report->render('skips', $stats));
					}

					foreach ($report->filters() as $filter => $options) {
						$data = $report->results['filters'][$filter];
						$command->out($report->render($options['name'], compact('data')));
					}
				}
				return $report;
			},
			'json' => function($runner, $path) use ($command) {
				$report = $runner();
				$filters = [];

				if ($results = $report->filters()) {
					foreach ($results as $filter => $options) {
						$filters[$options['name']] = $report->results['filters'][$filter];
					}
				}
				$command->out($report->render('stats', $report->stats() + compact('filters')));
				return $report;
			}
		];
	}

	/**
	 * Runs tests given a path to a directory or file containing tests. The path to the
	 * test(s) may be absolute or relative to the current working directory.
	 *
	 * ```sh
	 * li3 test lithium/tests/cases/core/LibrariesTest.php
	 * li3 test lithium/tests/cases/core
	 * ```
	 *
	 * If you are in the working directory of an application or plugin and wish to run all tests,
	 * simply execute the following:
	 *
	 * ```sh
	 * li3 test tests/cases
	 * ```
	 *
	 * If you are in the working directory of an application and wish to run a plugin, execute one
	 * of the following:
	 *
	 * ```sh
	 * li3 test libraries/<plugin>/tests/cases
	 * li3 test <plugin>/tests/cases
	 * ```
	 *
	 *
	 * This will run `<library>/tests/cases/<package>/<class>Test.php`:
	 *
	 * ```sh
	 * li3 test <library>/<package>/<class>.php
	 * ```
	 *
	 * @param string $path Absolute or relative path to tests or a file which
	 *                     corresponding test should be run.
	 * @return integer|boolean Will (indirectly) exit with status `1` if one or more tests
	 *         failed otherwise with `0`.
	 */
	public function run($path = null) {
		if (!$path = $this->_path($path)) {
			return false;
		}
		if (!preg_match('/(tests|Test\.php)/', $path)) {
			if (!$path = Unit::get($path)) {
				$this->error('Cannot map path to test path.');
				return static::EXIT_NO_TEST;
			}
		}
		$handlers = $this->_handlers;

		if (!isset($handlers[$this->format]) || !is_callable($handlers[$this->format])) {
			$this->error(sprintf('No handler for format `%s`... ', $this->format));
			return false;
		}
		$filters = $this->filters ? array_map('trim', explode(',', $this->filters)) : [];
		$params = compact('filters') + ['format' => $this->format];
		$runner = function($options = []) use ($path, $params) {
			return Dispatcher::run($path, $params + $options);
		};
		$report = $handlers[$this->format]($runner, $path);
		$stats = $report->stats();
		return $stats['success'];
	}

	/**
	 * Finds a library for given path.
	 *
	 * @param string $path Normalized (to slashes) absolute or relative path.
	 * @return string the name of the library
	 */
	protected function _library($path) {
		$result = null;
		$match = '';
		foreach (Libraries::get() as $name => $library) {
			if (strpos($path, $library['path']) !== 0) {
				continue;
			}
			if (strlen($library['path']) > strlen($match)) {
				$result = $name;
				$match = $library['path'];
			}
		}
		return $result;
	}

	/**
	 * Validates and gets a fully-namespaced class path from an absolute or
	 * relative physical path to a directory or file. The final class path may
	 * be partial in that in doesn't contain the class name.
	 *
	 * This method can be thought of the reverse of `Libraries::path()`.
	 *
	 * ```
	 * lithium/tests/cases/core/LibrariesTest.php -> lithium\tests\cases\core\LibrariesTest
	 * lithium/tests/cases/core                   -> lithium\tests\cases\core
	 * lithium/core/Libraries.php                 -> lithium\core\Libraries
	 * lithium/core/                              -> lithium\core
	 * lithium/core                               -> lithium\core
	 * ```
	 *
	 * @see lithium\core\Libraries::path()
	 * @param string $path The directory of or file path to one or more classes.
	 * @return string Returns a fully-namespaced class path, or `false`, if an error occurs.
	 */
	protected function _path($path) {
		$path = $path ? rtrim(str_replace('\\', '/', $path), '/') : null;

		if (!$path) {
			$this->error('Please provide a path to tests.');
			return false;
		}
		if ($path[0] === '/') {
			$library = $this->_library($path);
		}
		if ($path[0] !== '/') {
			$libraries = array_reduce(Libraries::get(), function($v, $w) {
				$v[] = basename($w['path']);
				return $v;
			});

			$library = $this->_library($this->request->env('working') . '/' . $path);
			$parts = explode('/', str_replace("../", "", $path));
			$plugin = array_shift($parts);

			if ($plugin === 'libraries') {
				$plugin = array_shift($parts);
			}
			if (in_array($plugin, $libraries)) {
				$library = $plugin;
				$path = join('/', $parts);
			}
		}
		if (empty($library)) {
			$this->error("No library found in path `{$path}`.");
			return false;
		}
		if (!$config = Libraries::get($library)) {
			$this->error("Library `{$library}` does not exist.");
			return false;
		}
		$path = str_replace($config['path'], "", $path);
		$realpath = $config['path'] . '/' . $path;

		if (!realpath($realpath)) {
			$this->error("Path `{$realpath}` not found.");
			return false;
		}
		$class = str_replace(".php", "", str_replace('/', '\\', ltrim($path, '/')));
		return $config['prefix'] . $class;
	}
}

?>