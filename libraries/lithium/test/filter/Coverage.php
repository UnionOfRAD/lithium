<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\filter;

use \lithium\core\Libraries;
use \lithium\util\String;
use \lithium\util\Collection;
use \lithium\analysis\Inspector;

/**
 * Runs code coverage analysis for the executed tests.
 */
class Coverage extends \lithium\test\Filter {

	/**
	 * Takes an instance of an object (usually a Collection object) containing test
	 * instances. Attaches code coverage filtering to test cases.
	 *
	 * @see lithium\test\filter\Coverage::collect()
	 * @param object $report Instance of Report which is calling apply.
	 * @param array $options Options for how code coverage should be applied. These options are
	 *              also passed to `Coverage::collect()` to determine how to aggregate results. See
	 *              the documentation for `collect()` for further options.  Options affecting this
	 *              method are:
	 *              -'method': The name of method to attach to, defaults to 'run'.
	 * @return object|void Returns the instance of `$tests` with code coverage analysis
	 *                     triggers applied.
	 */
	public static function apply($report, $options = array()) {
		$tests = $report->group->tests();
		$defaults = array('method' => 'run');
		$options += $defaults;
		$m = $options['method'];
		$filter = function($self, $params, $chain) use ($report, $options) {
			xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
			$chain->next($self, $params, $chain);
			$results = xdebug_get_code_coverage();
			xdebug_stop_code_coverage();
			$report->collect(__CLASS__, array($self->subject() => $results));
		};
		$tests->invoke('applyFilter', array($m, $filter));
		return $tests;
	}

	/**
	 * Analyzes code coverage results collected from XDebug, and performs coverage density analysis.
	 *
	 * @param object $report The report instance running this filter and aggregating results
	 * @param array $classes A list of classes to analyze coverage on. By default, gets all
	 *              defined subclasses of lithium\test\Unit which are currently in memory.
	 * @return array|void Returns an array indexed by file and line, showing the number of
	 *                    instances each line was called.
	 */
	public static function analyze($report, $classes = array()) {
		$filterResults = static::collect($report->results['filters'][__CLASS__]);
		$classes = $classes ?: array_filter(get_declared_classes(), function($class) {
			return (!is_subclass_of($class, 'lithium\test\Unit'));
		});
		$classes = array_values(array_intersect((array) $classes, array_keys($filterResults)));
		$densities = $result = array();

		foreach ($classes as $class) {
			$classMap = array($class => Libraries::path($class));
			$densities += static::_density($filterResults[$class], $classMap);
		}
		$executableLines = array();

		if (!empty($classes)) {
			$executableLines = array_combine($classes, array_map(
				function($cls) { return Inspector::executable($cls, array('public' => false)); },
				$classes
			));
		}

		foreach ($densities as $class => $density) {
			$executable = $executableLines[$class];
			$covered = array_intersect(array_keys($density), $executable);
			$uncovered = array_diff($executable, $covered);
			$percentage = round(count($covered) / (count($executable) ?: 1), 4) * 100;
			$result[$class] = compact('class', 'executable', 'covered', 'uncovered', 'percentage');
		}
		return $result;
	}

	/**
	 * Returns data to be output by a reporter.
	 *
	 * @param string $format I.e. `'html'` or `'text'`.
	 * @param array $analysis The results of the analysis.
	 * @return string|void
	 */
	public static function output($format, $analysis) {
		if (empty($analysis)) {
			return null;
		}
		$output = null;
		$aggregate = array('covered' => 0, 'executable' => 0);

		foreach ($analysis as $class => $coverage) {
			$out = array();
			$file = Libraries::path($class);
			$output .= static::stats($format, $class, $coverage);

			$aggregate['covered'] += count($coverage['covered']);
			$aggregate['executable'] += count($coverage['executable']);

			$uncovered = array_flip($coverage['uncovered']);
			$contents = explode("\n", file_get_contents($file));
			array_unshift($contents, ' ');
			$count = count($contents);

			for ($i = 1; $i <= $count; $i++) {
				if (isset($uncovered[$i])) {
					if (!isset($out[$i - 2])) {
						$out[$i - 2] = array(
							'class' => 'ignored',
							'data' => '...'
						);
					}
					if (!isset($out[$i - 1])) {
						$out[$i - 1] = array(
							'class' => 'covered',
							'data' => $contents[$i - 1]
						);
					}
					$out[$i] = array(
						'class' => 'uncovered',
						'data' => $contents[$i]
					);

					if (!isset($uncovered[$i + 1])) {
						$out[$i + 1] = array(
							'class' => 'covered',
							'data' => $contents[$i + 1]
						);
					}
				} elseif (isset($out[$i - 1]) && $out[$i - 1]['data'] !== '...'
						&& !isset($out[$i]) && !isset($out[$i + 1])) {
					$out[$i] = array(
						'class' => 'ignored',
						'data' => '...'
					);
				}
			}
			$data = array();

			foreach ($out as $line => $row) {
				$row['line'] = $line;
				$data[] = static::_format($format, 'row', $row);
			}
			if (!empty($data)) {
				$output .= static::_format($format, 'file', compact('file', 'data'));
			}
		}
		return $output;
	}

	/**
	 * Collects code coverage analysis results from `xdebug_get_code_coverage()`.
	 *
	 * @param array $filterResults An array of results arrays from `xdebug_get_code_coverage()`.
	 * @param array $options Set of options defining how results should be collected.
	 * @return array The packaged filter results.
	 * @see lithium\test\Coverage::analyze()
	 * @todo Implement $options['merging']
	 */
	public static function collect($filterResults, $options = array()) {
		$defaults = array('merging' => 'class');
		$options += $defaults;
		$packagedResults = array();

		foreach ($filterResults as $results) {
			$class = key($results);
			$results = $results[$class];
			foreach ($results as $file => $lines) {
				unset($results[$file][0]);
			}

			switch ($options['merging']) {
				case 'class':
				default:
					if (!isset($packagedResults[$class])) {
						$packagedResults[$class] = array();
					}
					$packagedResults[$class][] = $results;
				break;
			}
		}

		return $packagedResults;
	}

	/**
	 * Returns header for stats.
	 *
	 * @param string $format I.e. `'html'` or `'text'`.
	 * @param string $class
	 * @param array $analysis The results of the analysis.
	 * @return string|void
	 */
	public static function stats($format, $class, $analysis) {
		$covered = count($analysis['covered']) . ' of ' . count($analysis['executable']);

		if ($format == 'html') {
			$title = "{$class}: {$covered} lines covered (<em>{$analysis['percentage']}%</em>)";
			return '<h4 class="coverage">' . $title . '</h4>';
		}
	}

	/**
	 * Reduces the results of multiple XDebug code coverage runs into a single 2D array of the
	 * aggregate line coverage density per file.
	 *
	 * @param array $runs An array containing multiple runs of raw XDebug coverage data, where
	 *              each array key is a file name, and it's value is XDebug's coverage
	 *              data for that file.
	 * @param array $classMap An optional map with class names as array keys and corresponding file
	 *              names as values. Used to filter the returned results, and will cause the array
	 *              keys of the results to be class names instead of file names.
	 * @return array
	 */
	protected static function _density($runs, $classMap = array()) {
		$results = array();

		foreach ($runs as $run) {
			foreach ($run as $file => $coverage) {
				$file = str_replace('\\', '/', $file);

				if (!empty($classMap)) {
					if (!$class = array_search($file, $classMap)) {
						continue;
					}
					$file = $class;
				}
				if (!isset($results[$file])) {
					$results[$file] = array();
				}
				$coverage = array_filter($coverage, function($line) { return ($line === 1); });

				foreach ($coverage as $line => $isCovered) {
					if (!isset($results[$file][$line])) {
						$results[$file][$line] = 0;
					}
					$results[$file][$line]++;
				}
			}
		}
		return $results;
	}

	/**
	 * Returns row or file in specified format
	 *
	 * @param string $format [required] html,text
	 * @param string $type [required] row, file
	 * @param array $data [required] from output
	 * @return string
	 */
	protected static function _format($format, $type, $data) {
		if ($format === 'html') {
			if ($type == 'file') {
				return sprintf(
					'<div class="code-coverage-results"><h4 class="name">%s</h4>%s</div>',
					$data['file'], join("\n", $data['data'])
				);
			}
			return sprintf(
				'<div class="code-line %s">
					<span class="line-num">%d</span>
					<span class="content">%s</span>
				</div>', $data['class'], $data['line'], htmlspecialchars(
					str_replace("\t", "    ", $data['data'])
				)
			);
		} elseif ($format === 'text') {
			if ($type == 'file') {
				return sprintf("%s\n\n%s", $data['file'], join("\n", $data['data']));
			}
			return sprintf("%s: line %d\n%s\n\n",
				$data['class'], $data['line'], str_replace("\t", "    ", $data['data'])
			);
		}
	}
}

?>