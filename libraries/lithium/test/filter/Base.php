<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\filter;

/**
 * `Base` is the base class for all test filters.
 */
class Base extends \lithium\core\StaticObject {

	/**
	 * Takes an instance of an object (usually a Collection object) containing test
	 * instances. Allows for preparing tests before they are run.
	 *
	 * @param object $report Instance of Report which is calling apply.
	 * @param array $options Options for how this filter should be applied.
	 * @return object|void Returns the instance of `$tests`.
	 */
	public static function apply($report, $options = array()) {}

	/**
	 * Analyzes the results of a test run and returns the result of the analysis.
	 *
	 * @param array $results The results of the test run.
	 * @param array $filterResults The results of the filter on the test run.
	 * @param array $options
	 * @return array|void The results of the analysis.
	 */
	public static function analyze($results, $filterResults, $options = array()) {}

	/**
	 * Returns data to be output by a reporter.
	 *
	 * @param string $format I.e. `'html'` or `'text'`.
	 * @param array $analysis The results of the analysis.
	 * @return string|void
	 */
	public static function output($format, $analysis) {}
}

?>