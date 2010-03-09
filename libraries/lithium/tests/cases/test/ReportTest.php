<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

use \lithium\test\Report;
use \lithium\test\Group;
//use \lithium\test\filter\Complexity;

class ReportTest extends \lithium\test\Unit {

	public function testInit() {
		$report = new Report(array(
			'title' => '\lithium\tests\mocks\test\MockUnitTest',
			'group' => new Group(array('items' => array('\lithium\tests\mocks\test\MockUnitTest')))
		));
		$report->run();

		$expected = '\lithium\tests\mocks\test\MockUnitTest';
		$result = $report->title;
		$this->assertEqual($expected, $result);

		$expected = 'testNothing';
		$result = $report->results['group'][0][0]['method'];
		$this->assertEqual($expected, $result);

		$expected = 'pass';
		$result = $report->results['group'][0][0]['result'];
		$this->assertEqual($expected, $result);
	}

	public function testStats() {
		$report = new Report(array(
			'title' => '\lithium\tests\mocks\test\MockUnitTest',
			'group' => new Group(array('items' => array('\lithium\tests\mocks\test\MockUnitTest')))
		));
		$report->run();

		$expected = 1;
		$result = $report->stats();
		$this->assertEqual($expected, $result['count']['asserts']);

		$this->assertEqual($expected, $result['count']['passes']);

		$this->assertTrue($result['success']);
	}

	public function testSingleFilter() {
		$report = new Report(array(
			'title' => '\lithium\tests\mocks\test\MockFilterClassTest',
			'group' => new Group(array('items' => array('\lithium\tests\mocks\test\MockFilterClassTest'))),
			'filters' => array("Complexity" => "")
		));
		$report->run();

		$class = 'lithium\test\filter\Complexity';
		$this->assertNotEqual(null, $report->results['filters'][$class]);
	}

	public function testRender() {
		$report = new Report(array(
			'title' => '\lithium\tests\mocks\test\MockUnitTest',
			'group' => new Group(array('items' => array('\lithium\tests\mocks\test\MockUnitTest'))),
			'format' => 'html',
			'reporter' => 'html'
		));
		$report->run();

		$output = $report->render("stats");

		$this->assertPattern("/1 \/ 1 passes, 0  fails	and 0  exceptions/", $output);
	}

	public function testFilters() {
		$report = new Report(array(
			'title' => '\lithium\tests\mocks\test\MockFilterClassTest',
			'group' => new Group(
				array('items' => array('\lithium\tests\mocks\test\MockFilterClassTest'))
			),
			'filters' => array("Complexity" => ""),
			'format' => 'html',
			'reporter' => 'html'
		));
		$report->run();

		$output = $report->filters();

		$this->assertPattern("/<h3>Cyclomatic Complexity<\/h3>/", $output);
	}
}

?>