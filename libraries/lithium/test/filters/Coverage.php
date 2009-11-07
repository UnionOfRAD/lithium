<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\filters;

use \lithium\core\Libraries;
use \lithium\util\String;
use \lithium\util\Collection;
use \lithium\util\reflection\Inspector;

/**
 * Runs code coverage analysis for the executed tests.
 */
class Coverage extends \lithium\core\StaticObject {

	/**
	 * Collects coverage analysis results from Xdebug.
	 */
	protected static $_results = array();

	/**
	 * Takes an instance of an object (usually a Collection object) containing unit test case
	 * instances.  Attaches code coverage filtering to test cases.
	 *
	 * @param object $object Instance of Collection containing instances of lithium\test\Unit
	 * @param array $options Options for how code coverage should be applied. These options are
	 *              also passed to `Coverage::collect()` to determine how to aggregate results. See
	 *              the documentation for `collect()` for further options.  Options affecting this
	 *              method are:
	 *              -'method': The name of method to attach to, defaults to 'run'.
	 * @return object Returns the instance of $object with code coverage analysis triggers applied.
	 * @see lithium\test\filters\Coverage::collect()
	 */
	public static function apply($object, $options = array()) {
		$defaults = array('method' => 'run');
		$options += $defaults;
		$m = $options['method'];

		$object->invoke('applyFilter', array($m, function($self, $params, $chain) use ($options) {
			xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
			$chain->next($self, $params, $chain);
			$results = xdebug_get_code_coverage();
			xdebug_stop_code_coverage();
			Coverage::collect($self->subject(), $results, $options);
		}));
		return $object;
	}

	/**
	 * Collects code coverage analysis results from `xdebug_get_code_coverage()`.
	 *
	 * @param string $class Class name that these test results correspond to.
	 * @param array $results A results array from `xdebug_get_code_coverage()`.
	 * @param array $options Set of options defining how results should be collected.
	 * @return void
	 * @see lithium\test\Coverage::analyze()
	 * @todo Implement $options['merging']
	 */
	public static function collect($class, $results, $options = array()) {
		$defaults = array('merging' => 'class');
		$options += $defaults;

		foreach ($results as $file => $lines) {
			unset($results[$file][0]);
		}

		switch ($options['merging']) {
			case 'class':
			default:
				if (!isset(static::$_results[$class])) {
					static::$_results[$class] = array();
				}
				static::$_results[$class][] = $results;
			break;
		}
	}

	/**
	 * Analyzes code coverage results collected from XDebug, and performs coverage density analysis.
	 *
	 * @param array $classes Optional. A list of classes to analyze coverage on.  By default, gets
	 *              all defined subclasses of lithium\test\Unit which are currently in memory.
	 * @return array Returns an array indexed by file and line, showing the number of instances
	 *               each line was called.
	 */
	public static function analyze($results, $classes = array()) {
		$classes = $classes ?: array_filter(get_declared_classes(), function($class) {
			return (!is_subclass_of($class, 'lithium\test\Unit'));
		});
		$classes = array_values(array_intersect((array)$classes, array_keys(static::$_results)));
		$densities = $result = array();

		foreach ($classes as $class) {
			$classMap = array($class => Libraries::path($class));
			$densities += static::_density(static::$_results[$class], $classMap);
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
	 * Reduces the results of multiple XDebug code coverage runs into a single 2D array of the
	 * aggregate line coverage density per file.
	 *
	 * @param array $results An array containing multiple runs of raw XDebug coverage data, where
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
	 * Outputs the coverage analysis to a specific format
	 *
	 * @param string $format [required] html,txt
	 * @param array $data [required] from Coverage::analysis()
	 * @return string
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
				} elseif (isset($out[$i - 1]) && $out[$i - 1]['data'] !== '...' && !isset($out[$i]) && !isset($out[$i + 1])) {
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
	 * Returns header for stats
	 *
	 * @param string $format [required] html,txt
	 * @param string $class [required] class name
	 * @param array $data [required] from output
	 * @return string
	 */
	public static function stats($format, $class, $data) {
		$covered = count($data['covered']) . ' of ' . count($data['executable']);

		if ($format == 'html') {
			$title = "{$class}: {$covered} lines covered (<em>{$data['percentage']}%</em>)";
			return '<h4 class="coverage">' . $title . '</h4>';
		}
	}

	/**
	 * Returns row or file in specified format
	 *
	 * @param string $format [required] html,txt
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
		}

		if ($format === 'txt') {
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