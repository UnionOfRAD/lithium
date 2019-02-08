<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\test;

use lithium\test\Report;
use lithium\test\Group;

class ReportTest extends \lithium\test\Unit {

	public function testInit() {
		$report = new Report([
			'title' => 'lithium\tests\mocks\test\MockUnitTest',
			'group' => new Group(['data' => ['lithium\tests\mocks\test\MockUnitTest']])
		]);
		$report->run();

		$expected = 'lithium\tests\mocks\test\MockUnitTest';
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
		$report = new Report([
			'title' => 'lithium\tests\mocks\test\MockFilterClassTest',
			'group' => new Group(
				['data' => ['lithium\tests\mocks\test\MockFilterClassTest']]
			),
			'filters' => ["Complexity" => []],
			'format' => 'html'
		]);

		$expected = ['lithium\test\filter\Complexity' => [
			'name' => 'complexity', 'apply' => [], 'analyze' => []
		]];
		$result = $report->filters();
		$this->assertEqual($expected, $result);
	}

	public function testStats() {
		$report = new Report([
			'title' => 'lithium\tests\mocks\test\MockUnitTest',
			'group' => new Group(['data' => ['lithium\tests\mocks\test\MockUnitTest']])
		]);
		$report->run();

		$expected = 2;
		$result = $report->stats();
		$this->assertEqual($expected, $result['count']['asserts']);
		$this->assertEqual($expected, $result['count']['passes']);
		$this->assertTrue($result['success']);
	}

	public function testRender() {
		$report = new Report([
			'title' => '\lithium\tests\mocks\test\MockUnitTest',
			'group' => new Group(['data' => ['\lithium\tests\mocks\test\MockUnitTest']]),
			'format' => 'txt'
		]);
		$report->run();

		$result = $report->render('result', $report->stats());
		$this->assertPattern('#2.*2.*passes.*0.*fails.*0.*exceptions#s', $result);
	}

	public function testSingleFilter() {
		$report = new Report([
			'title' => 'lithium\tests\mocks\test\MockFilterClassTest',
			'group' => new Group([
				'data' => ['lithium\tests\mocks\test\MockFilterClassTest']
			]),
			'filters' => ["Complexity" => []]
		]);
		$report->run();

		$class = 'lithium\test\filter\Complexity';
		$result = $report->results['filters'][$class];
		$this->assertTrue(isset($report->results['filters'][$class]));
	}
}

?>