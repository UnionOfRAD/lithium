<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Test;

use Lithium\Test\Report;
use Lithium\Test\Group;

class ReportTest extends \Lithium\Test\Unit {

	public function testInit() {
		$report = new Report(array(
			'title' => '\Lithium\Tests\Mocks\Test\MockUnitTest',
			'group' => new Group(array('data' => array('\Lithium\Tests\Mocks\Test\MockUnitTest')))
		));
		$report->run();

		$expected = '\Lithium\Tests\Mocks\Test\MockUnitTest';
		$result = $report->title;
		$this->assertEqual($expected, $result);

		$expected = 'testNothing';
		$result = $report->results['group'][0][0]['method'];
		$this->assertEqual($expected, $result);

		$expected = 'pass';
		$result = $report->results['group'][0][0]['result'];
		$this->assertEqual($expected, $result);
	}

	public function testFilters() {
		$report = new Report(array(
			'title' => '\Lithium\Tests\Mocks\Test\MockFilterClassTest',
			'group' => new Group(
				array('data' => array('\Lithium\Tests\Mocks\Test\MockFilterClassTest'))
			),
			'filters' => array("Complexity" => ""),
			'format' => 'html',
			'reporter' => 'Html'
		));

		$expected = array('Lithium\Test\Filter\Complexity' => array(
			'name' => 'complexity', 'apply' => array(), 'analyze' => array()
		));
		$result = $report->filters();
		$this->assertEqual($expected, $result);
	}

	public function testStats() {
		$report = new Report(array(
			'title' => '\Lithium\Tests\Mocks\Test\MockUnitTest',
			'group' => new Group(array('data' => array('\Lithium\Tests\Mocks\Test\MockUnitTest')))
		));
		$report->run();

		$expected = 1;
		$result = $report->stats();
		$this->assertEqual($expected, $result['count']['asserts']);
		$this->assertEqual($expected, $result['count']['passes']);
		$this->assertTrue($result['success']);
	}

	public function testRender() {
		$report = new Report(array(
			'title' => '\Lithium\Tests\Mocks\Test\MockUnitTest',
			'group' => new Group(array('data' => array('\Lithium\Tests\Mocks\Test\MockUnitTest'))),
			'format' => 'html',
			'reporter' => 'Html'
		));
		$report->run();

		$result = $report->render("stats");
		$this->assertPattern("#1.*1.*passes,.*0.*fails.*0.*exceptions#s", $result);
	}

	public function testSingleFilter() {
		$report = new Report(array(
			'title' => '\Lithium\Tests\Mocks\Test\MockFilterClassTest',
			'group' => new Group(array(
				'data' => array('\Lithium\Tests\Mocks\Test\MockFilterClassTest')
			)),
			'filters' => array("Complexity" => "")
		));
		$report->run();

		$class = 'Lithium\Test\Filter\Complexity';
		$result = $report->results['filters'][$class];
		$this->assertTrue(isset($report->results['filters'][$class]));
	}
}

?>
