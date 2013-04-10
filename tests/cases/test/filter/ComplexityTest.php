<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test\filter;

use lithium\test\filter\Complexity;
use lithium\test\Report;
use lithium\test\Mocker;
use lithium\util\collection\Mock as CollectionMock;
use lithium\analysis\parser\Mock as ParserMock;
use lithium\analysis\inspector\Mock as InspectorMock;
use lithium\test\group\Mock as GroupMock;

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
		'testClass' => 'FooObject',
		'testClassTest' => 'FooObjectTest'
	);

	/**
	 * Helper array which stores the expected results to clean up the tests.
	 */
	protected $_metrics = array(
		array(
			'FooObject' => array(
				'applyFilter' => 5,
			)
		),
		array(
			'FooObject' => array(
				'invokeMethod' => 8,
				'applyFilter' => 5,
			)
		),
		array(
			'FooObject' => array(
				'invokeMethod' => 8,
				'applyFilter' => 5,
				'_instance' => 2,
			)
		),
		array(
			'FooObject' => array(
				'invokeMethod' => 8,
				'applyFilter' => 5,
				'_instance' => 2,
				'_filter' => 3,
			)
		),
		array(
			'FooObject' => array(
				'invokeMethod' => 8,
				'applyFilter' => 5,
				'_instance' => 2,
				'_filter' => 3,
				'_parents' => 2,
			)
		),
		array(
			'FooObject' => array(
				'invokeMethod' => 8,
				'applyFilter' => 5,
				'_instance' => 2,
				'_filter' => 3,
				'_parents' => 2,
				'_stop' => 1,
			)
		),
	);

	/**
	 * Set up a new report which will later be used in the tests.
	 *
	 * @see lithium\test\Report
	 */
	public function setUp() {
		$this->report = new Report();
		Mocker::register();
		Mocker::overwriteFunction(false);
	}

	public function tearDown() {
		Mocker::overwriteFunction(false);
	}

	/**
	 * Tests the `apply` method which provides a high-level interface to the complexity generation.
	 * It tests the cyclomatic complexity of the FooObject class and its methods.
	 *
	 * @see lithium\test\filter\Complexity::apply()
	 */
	public function testApply() {
		extract($this->_paths);

		$collection = new CollectionMock();
		$group = new GroupMock();
		$group->add($testClassTest);
		$this->report->group = $group;

		InspectorMock::applyFilter('methods', function($self, $params, $chain) {
			return array('foo' => array(1), 'bar' => array(2)); // return 2 methods
		});
		InspectorMock::applyFilter('lines', function($self, $params, $chain) {
			return 'return;'; // return a single return
		});
		ParserMock::applyFilter('tokenize', function($self, $params, $chain) {
			return array(1,2,3); // always return 3 methods
		});
		$group->applyFilter('tests', function($self, $params, $chain) use($collection) {
			return $collection;
		});
		$collection->applyFilter('invoke', function($self, $params, $chain) {
			return array('FooObject');
		});

		Complexity::apply($this->report, $group->tests(), array(
			'classes' => array(
				'parser' => 'lithium\analysis\parser\Mock',
				'inspector' => 'lithium\analysis\inspector\Mock',
			),
		));
		$results = array_pop($this->report->results['filters'][$complexity]);
		$expected = array($testClass => array(
			'foo' => 4,
			'bar' => 4,
		));
		$this->assertEqual($expected, $results);
	}

	/**
	 * Tests the `analyze` method which compacts the test results and provides a convenient
	 * summary of the complexity filter (class average and worst offenders).
	 *
	 * @see lithium\test\filter\Complexity::analyze()
	 */
	public function testAnalyze() {
		extract($this->_paths);

		$group = new GroupMock();
		$group->add($testClassTest);
		$this->report->group = $group;

		$this->report->results['filters'] = array(
			'lithium\test\filter\Complexity' => $this->_metrics,
		);

		$results = Complexity::analyze($this->report);
		$expected = array(
			'class' => array($testClass => 3.5),
			'max' => array(
				'FooObject::invokeMethod()' => 8,
				'FooObject::applyFilter()' => 5,
				'FooObject::_filter()' => 3,
				'FooObject::_parents()' => 2,
				'FooObject::_instance()' => 2,
				'FooObject::_stop()' => 1,
			),
		);
		$this->assertEqual($expected['max'], $results['max']);
		$result = round($results['class'][$testClass], 1);
		$this->assertIdentical($expected['class'][$testClass], $result);
	}

	/**
	 * Tests the `collect` method which takes the raw report data and prepares it for analysis.
	 *
	 * @see lithium\test\filter\Complexity::collect()
	 */
	public function testCollect() {
		extract($this->_paths);

		$group = new GroupMock();
		$group->add($testClassTest);
		$this->report->group = $group;

		$this->report->results['filters'] = array(
			'lithium\test\filter\Complexity' => array(
				array('FooObject' => $this->_metrics),
			),
		);

		$results = Complexity::collect($this->report->results['filters'][$complexity]);
		$expected = array($testClass => $this->_metrics);
		$this->assertEqual($expected, $results);
	}
}

?>