<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\test\filter;

use lithium\aop\Filters;
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

	public $report;

	/**
	 * Helper array to shorten the methods up a bit.
	 */
	protected $_paths = [
		'complexity' => 'lithium\test\filter\Complexity',
		'testClass' => 'lithium\test\Dispatcher',
		'testClassTest' => 'lithium\tests\cases\test\DispatcherTest'
	];

	/**
	 * Helper array which stores the expected results to clean up the tests.
	 */
	protected $_metrics = [
		'run' => 1,
		'_group' => 1,
		'_report' => 1
	];

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
	 * It tests the cyclomatic complexity of the lithium\test\Dispatcher class and its methods.
	 *
	 * @see lithium\test\filter\Complexity::apply()
	 */
	public function testApply() {
		$group = new Group();
		$group->add($this->_paths['testClassTest']);
		$this->report->group = $group;

		Complexity::apply($this->report, $group->tests());

		$results = array_pop($this->report->results['filters'][$this->_paths['complexity']]);
		$expected = [$this->_paths['testClass'] => $this->_metrics];
		$this->assertEqual($expected, $results);

		Filters::clear($group);
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
		$expected = ['class' => [$this->_paths['testClass'] => 1.0]];
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
		$expected = [$this->_paths['testClass'] => $this->_metrics];
		$this->assertEqual($expected, $results);
	}
}

?>