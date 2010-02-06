<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\filter;

use lithium\core\Libraries;
use lithium\analysis\Inspector;

/**
 * The `Affected` test filter adds test cases to the tests that are about to be run.
 *
 * Affected test cases are determined by:
 *
 * 1. Looking at the subject of a test case.
 * 2. Searching the class tree for any classes that directly depend on that subject.
 * 3. Assigning test cases to those classes.
 *
 */
class Affected extends \lithium\test\Filter {

	protected static $_cachedDepends = array();

	/**
	 * Takes an instance of an object (usually a Collection object) containing test
	 * instances. Adds affected tests to the test collection.
	 *
	 * @param object $report Instance of Report which is calling apply.
	 * @param array $options Not used.
	 * @return object|void Returns the instance of `$tests`.
	 */
	public static function apply($report, $options = array()) {
		$tests = $report->group->tests();
		$affected = array();
		$testsClasses = $tests->map('get_class', array('collect' => false));

		foreach ($tests as $test) {
			$affected = array_merge($affected, self::_affected($test->subject()));
		}
		$affected = array_unique($affected);

		foreach ($affected as $class) {
			$test = self::_testCaseForClass($class);

			if ($test && !in_array($test, $testsClasses)) {
				$tests[] = new $test();
			}
			$report->collect(__CLASS__, array($class => $test));
		}
		return $tests;
	}

	/**
	 * Analyzes the results of a test run and returns the result of the analysis.
	 *
	 * @param object $report The report instance running this filter and aggregating results
	 * @param array $options
	 * @return array|void The results of the analysis.
	 */
	public static function analyze($report, $options = array()) {
		return $report->results['filters'][__CLASS__];
	}

	public static function output($format, $analysis) {
		$analysis = isset($analysis[0]) ? $analysis[0] : array();
		$output = array();

		if ($format == 'html') {
			$output[] = "<h3>Additional Affected Tests</h3>";
			$output[] = "<ul class=\"metrics\">";

			foreach ($analysis as $class => $test) {
				if ($test) {
					$output[] = "<li>{$test}</li>";
				}
			}
			$output[] = "</ul>";
		} elseif ($format == 'text') {
			$output[] = "Additional Affected Tests";
			$output[] = "-------------------------";

			foreach ($analysis as $class => $test) {
				if ($test) {
					$output[] = " - {$test}";
				}
			}
		}
		return implode("\n", $output);
	}

	/**
	 * Returns all classes directly depending on a given class.
	 *
	 * @param string $dependency The class name to use as a dependency.
	 * @return array Classes having a direct dependency on `$dependency`. May cotain duplicates.
	 */
	protected static function _affected($dependency) {
		$classes = Libraries::find(true, array(
			'recursive' => true,
			'exclude' => '/(tests|webroot|resources|libraries|plugins)/'
		));
		$affected = array();
		$dependency = ltrim($dependency, '\\');

		foreach ($classes as $class) {
			if (isset(static::$_cachedDepends[$class])) {
				$depends = static::$_cachedDepends[$class];
			} else {
				$depends = Inspector::dependencies($class);
				$depends = array_map(function($c) { return ltrim($c, '\\'); }, $depends);
				static::$_cachedDepends[$class] = $depends;
			}

			if (in_array($dependency, $depends)) {
				$affected[] = $class;
			}
		}
		return $affected;
	}

	/**
	 * Returns corresponding test case for a class, ensuring it actually exists.
	 *
	 * @param string $class
	 * @return string|void
	 */
	protected static function _testCaseForClass($class) {
		$parts = explode('\\', $class);

		$library = array_shift($parts);
		$name = array_pop($parts);
		$type = "tests.cases." . implode('.', $parts);

		return Libraries::locate($type, $name, compact('library'));
	}
}

?>