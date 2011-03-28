<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

use lithium\test\Dispatcher;
use lithium\util\Collection;

class DispatcherTest extends \lithium\test\Unit {

	public function testRunDefaults() {
		$report = Dispatcher::run();
		$this->assertTrue(is_a($report, '\lithium\test\Report'));

		$result = $report->group;
		$this->assertTrue(is_a($result, '\lithium\test\Group'));
	}

	public function testRunWithReporter() {
		$report = Dispatcher::run(null, array('reporter' => 'html'));
		$this->assertTrue(is_a($report, '\lithium\test\Report'));

		$result = $report->group;
		$this->assertTrue(is_a($result, '\lithium\test\Group'));
	}

	public function testRunCaseWithString() {
		$report = Dispatcher::run('\lithium\tests\mocks\test\MockUnitTest');

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

	public function testRunGroupWithString() {
		$report = Dispatcher::run('\lithium\tests\mocks\test');

		$expected = '\lithium\tests\mocks\test';
		$result = $report->title;
		$this->assertEqual($expected, $result);

		$expected = new Collection(array(
			'data' => array(
				new \lithium\tests\mocks\test\cases\MockSkipThrowsException(),
				new \lithium\tests\mocks\test\cases\MockTest(),
				new \lithium\tests\mocks\test\cases\MockTestErrorHandling()
			)
		));
		$result = $report->group->tests();
		$this->assertEqual($expected, $result);

		$expected = 'testNothing';
		$result = $report->results['group'][1][0]['method'];
		$this->assertEqual($expected, $result);

		$expected = 'pass';
		$result = $report->results['group'][1][0]['result'];
		$this->assertEqual($expected, $result);
	}
}

?>