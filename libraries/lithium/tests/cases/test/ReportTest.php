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

		$expected = "1 / 1 passes\n0 fails and 0 exceptions";
		$result = $report->stats();
		$this->assertEqual($expected, $result);
	}
}

?>