<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\test\filter;

use lithium\analysis\Parser;
use lithium\analysis\Inspector;

/**
 * Calculates the cyclomatic complexity of class methods, and shows worst-offenders and statistics.
 */
class Complexity extends \lithium\test\Filter {

	/**
	 * The list of tokens which represent the starting point of a code branch.
	 */
	protected static $_include = [
		'T_CASE', 'T_CATCH', 'T_IF', 'T_FOR',
		'T_FOREACH', 'T_WHILE', 'T_DO', 'T_ELSEIF'
	];

	/**
	 * Takes an instance of an object (usually a Collection object) containing test
	 * instances. Introspects the test subject classes to extract cyclomatic complexity data.
	 *
	 * @param object $report Instance of Report which is calling apply.
	 * @param \lithium\util\Collection $tests The tests to apply this filter on.
	 * @param array $options Additional options to overwrite dependencies.
	 *                       - `'classes'` _array_: Overwrite default classes array.
	 * @return object Returns the instance of `$tests`.
	 */
	public static function apply($report, $tests, array $options = []) {
		$results = [];
		foreach ($tests->invoke('subject') as $class) {
			$results[$class] = [];

			if (!$methods = Inspector::methods($class, 'ranges', ['public' => false])) {
				continue;
			}
			foreach ($methods as $method => $lines) {
				$lines = Inspector::lines($class, $lines);
				$branches = Parser::tokenize(join("\n", (array) $lines), [
					'include' => static::$_include
				]);
				$results[$class][$method] = count($branches) + 1;
				$report->collect(__CLASS__, $results);
			}
		}
		return $tests;
	}

	/**
	 * Analyzes the results of a test run and returns the result of the analysis.
	 *
	 * @param object $report The report instance running this filter and aggregating results
	 * @param array $options Not used.
	 * @return array The results of the analysis.
	 */
	public static function analyze($report, array $options = []) {
		$filterResults = static::collect($report->results['filters'][__CLASS__]);
		$metrics = ['max' => [], 'class' => []];

		foreach ($filterResults as $class => $methods) {
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
	 * Collects raw data aggregated in Report and prepares it for analysis
	 *
	 * @param array $filterResults The results of the filter on the test run.
	 * @return array The packaged results.
	 */
	public static function collect($filterResults) {
		$packagedResults = [];

		foreach ($filterResults as $result) {
			foreach ($result as $class => $method) {
				if (!isset($packagedResults[$class])) {
					$packagedResults[$class] = [];
				}
				$classResult = (array) $result[$class];
				$packagedResults[$class] = array_merge($classResult, $packagedResults[$class]);
			}
		}

		return $packagedResults;
	}
}

?>