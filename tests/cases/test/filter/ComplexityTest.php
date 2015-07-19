<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test\filter;

use lithium\test\filter\Complexity;
use lithium\test\Group;
use lithium\test\Report;

/**
 * The `ComplexityTest` class tests the `Complexity` filter which calculates the cyclomatic
 * complexity (also known as McCabe algorithm) of class methods.
 *
 * @see lithium\test\filter\Complexity
 * @link http://en.wikipedia.org/wiki/Cyclomatic_complexity More about cyclomatic complexity.
 */
class ComplexityTest extends \lithium\test\Unit {

	/**
	 * Helper array to shorten the methods up a bit.
	 */
	protected $_paths = array(
		'complexity' => 'lithium\test\filter\Complexity',
		'testClass' => 'lithium\core\StaticObject',
		'testClassTest' => 'lithium\tests\cases\core\StaticObjectTest'
	);

	/**
	 * Helper array which stores the expected results to clean up the tests.
	 */
	protected $_metrics = array(
		'applyFilter' => 5,
		'invokeMethod' => 7,
		'respondsTo' => 1,
		'_instance' => 2,
		'_filter' => 3,
		'_parents' => 2,
		'_stop' => 1
	);

	/**
	 * Set up a new report which will later be used in the tests.
	 *
	 * @see lithium\test\Report
	 */
	public function setUp() {
		$this->report = new Report();
	}

	/**
	 * Tests the `apply` method which provides a high-level interface to the complexity generation.
	 * It tests the cyclomatic complexity of the lithium\core\StaticObject class and its methods.
	 *
	 * @see lithium\test\filter\Complexity::apply()
	 */
	public function testApply() {
		$group = new Group();
		$group->add($this->_paths['testClassTest']);
		$this->report->group = $group;

		Complexity::apply($this->report, $group->tests());
		$results = array_pop($this->report->results['filters'][$this->_paths['complexity']]);
		$expected = array($this->_paths['testClass'] => $this->_metrics);
		$this->assertEqual($expected, $results);
	}

	/**
	 * Tests the `analyze` method which compacts the test results and provides a convenient
	 * summary of the complexity filter (class average and worst offenders).
	 *
	 * @see lithium\test\filter\Complexity::analyze()
	 */
	public function testAnalyze() {
		$group = new Group();
		$group->add($this->_paths['testClassTest']);
		$this->report->group = $group;

		Complexity::apply($this->report, $group->tests());

		$results = Complexity::analyze($this->report);
		$expected = array('class' => array($this->_paths['testClass'] => 3.0));
		foreach ($this->_metrics as $method => $metric) {
			$expected['max'][$this->_paths['testClass'] . '::' . $method . '()'] = $metric;
		}
		$this->assertEqual($expected['max'], $results['max']);
		$result = round($results['class'][$this->_paths['testClass']], 1);
		$this->assertIdentical($expected['class'][$this->_paths['testClass']], $result);
	}

	/**
	 * Tests the `collect` method which takes the raw report data and prepares it for analysis.
	 *
	 * @see lithium\test\filter\Complexity::collect()
	 */
	public function testCollect() {
		$group = new Group();
		$group->add($this->_paths['testClassTest']);
		$this->report->group = $group;

		Complexity::apply($this->report, $group->tests());

		$results = Complexity::collect(
			$this->report->results['filters'][$this->_paths['complexity']]
		);
		$expected = array($this->_paths['testClass'] => $this->_metrics);
		$this->assertEqual($expected, $results);
	}
}

?>