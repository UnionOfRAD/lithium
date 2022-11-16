<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\test;

/**
 * `Filter` is the base class for all test filters.
 */
abstract class Filter {

	/**
	 * Takes an instance of an object (usually a Collection object) containing test
	 * instances. Allows for preparing tests before they are run.
	 *
	 * @param object $report Instance of Report which is calling apply.
	 * @param array $tests The test to apply this filter on
	 * @param array $options Options for how this filter should be applied.
	 * @return object Returns the instance of `$tests`.
	 */
	public static function apply($report, $tests, array $options = []) {}

	/**
	 * Analyzes the results of a test run and returns the result of the analysis.
	 *
	 * @param object $report The report instance running this filter and aggregating results
	 * @param array $options
	 * @return array The results of the analysis.
	 */
	public static function analyze($report, array $options = []) {
		return $report->results['filters'][get_called_class()];
	}
}

?>