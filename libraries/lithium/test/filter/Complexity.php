<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\filter;

use \lithium\analysis\Parser;
use \lithium\analysis\Inspector;

/**
 * Calculates the cyclomatic complexity of class methods, and shows worst-offenders and statistics.
 */
class Complexity extends \lithium\test\filter\Base {

	/**
	 * Collects complexity analysis results for classes/methods.
	 */
	protected static $_results = array();

	/**
	 * The list of tokens which represent the starting point of a code branch.
	 */
	protected static $_include = array(
		'T_CASE', 'T_DEFAULT', 'T_CATCH', 'T_IF', 'T_FOR',
		'T_FOREACH', 'T_WHILE', 'T_DO', 'T_ELSEIF'
	);

	/**
	 * Takes an instance of an object (usually a Collection object) containing test
	 * instances. Introspects the test subject classes to extract cyclomatic complexity data.
	 *
	 * @param object $tests Instance of Collection containing instances of tests.
	 * @param array $options Not used.
	 * @return object|void Returns the instance of `$tests`.
	 */
	public static function apply($tests, $options = array()) {
		foreach ($tests->invoke('subject') as $class) {
			static::$_results[$class] = array();

			if (!$methods = Inspector::methods($class, 'ranges')) {
				continue;
			}

			foreach ($methods as $method => $lines) {
				$lines = Inspector::lines($class, $lines);
				$branches = Parser::tokenize(join("\n", (array) $lines), array(
					'include' => static::$_include
				));
				static::$_results[$class][$method] = count($branches) + 1;
			}
		}
		return $tests;
	}

	/**
	 * Analyzes the results of a test run and returns the result of the analysis.
	 *
	 * @param array $results The results of the test run.
	 * @param array $options Not used.
	 * @return array|void The results of the analysis.
	 */
	public static function analyze($results, $options = array()) {
		$metrics = array('max' => array(), 'class' => array());

		foreach (static::$_results as $class => $methods) {
			if (!$methods) {
				continue;
			}
			$metrics['class'][$class] = array_sum($methods) / count($methods);

			foreach ($methods as $method => $count) {
				$metrics['max']["{$class}::{$method}()"] = $count;
			}
		}

		arsort($metrics['max']);
		arsort($metrics['class']);
		return $metrics;
	}

	/**
	 * Returns data to be output by a reporter.
	 *
	 * @param string $format I.e. `'html'` or `'text'`.
	 * @param array $analysis The results of the analysis.
	 * @return string|void
	 */
	public static function output($format, $analysis) {
		$output = null;

		if ($format == 'html') {
			$output .= '<h3>Cyclomatic Complexity</h3>';
			$output .= '<table class="metrics"><tbody>';

			foreach (array_slice($analysis['max'], 0, 10) as $method => $count) {
				if ($count <= 7) {
					continue;
				}
				$output .= '<tr>';
				$output .= '<td class="metric-name">Worst Offender</th>';
				$output .= '<td class="metric">' . $method . ' - ' . $count . '</td>';
				$output .= '</tr>';
			}

			$output .= '<tr>';
			$output .= '<th colspan="2">Class Averages</th>';
			$output .= '</tr>';

			foreach (array_slice($analysis['class'], 0, 10) as $class => $count) {
				$output .= '<tr>';
				$output .= '<td class="metric-name">' . $class . '</th>';
				$output .= '<td class="metric">' . round($count, 2) . '</td>';
				$output .= '</tr>';
			}
			$output .= '</tbody></table>';
		}
		return $output;
	}
}

?>