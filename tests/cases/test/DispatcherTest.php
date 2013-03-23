<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

use lithium\test\Dispatcher;
use lithium\util\Collection;
use lithium\tests\mocks\test\cases\MockTest;
use lithium\tests\mocks\test\cases\MockErrorHandlingTest;
use lithium\tests\mocks\test\cases\MockSkipThrowsExceptionTest;
use lithium\tests\mocks\test\cases\MockSetUpThrowsExceptionTest;
use lithium\tests\mocks\test\cases\MockTearDownThrowsExceptionTest;

class DispatcherTest extends \lithium\test\Unit {

	public function testRunDefaults() {
		$report = Dispatcher::run();
		$this->assertInstanceOf('lithium\test\Report', $report);

		$result = $report->group;
		$this->assertInstanceOf('lithium\test\Group', $result);
	}

	public function testRunWithReporter() {
		$report = Dispatcher::run(null, array(
			'reporter' => function($info) {
				return $info;
			}
		));
		$this->assertInstanceOf('lithium\test\Report', $report);

		$result = $report->group;
		$this->assertInstanceOf('lithium\test\Group', $result);
	}

	public function testRunCaseWithString() {
		$report = Dispatcher::run('lithium\tests\mocks\test\MockUnitTest');

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

	public function testRunGroupWithString() {
		$report = Dispatcher::run('lithium\tests\mocks\test');

		$expected = 'lithium\tests\mocks\test';
		$result = $report->title;
		$this->assertEqual($expected, $result);

		$expected = new Collection(array('data' => array(
			new MockErrorHandlingTest(),
			new MockSetUpThrowsExceptionTest(),
			new MockSkipThrowsExceptionTest(),
			new MockTearDownThrowsExceptionTest(),
			new MockTest()
		)));
		$result = $report->group->tests();
		$this->assertEqual($expected, $result);
		$expected = 'testNothing';
		$result = $report->results['group'][3][0]['method'];
		$this->assertEqual($expected, $result);

		$expected = 'pass';
		$result = $report->results['group'][3][0]['result'];
		$this->assertEqual($expected, $result);
	}
}

?>