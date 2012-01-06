<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Test;

use Lithium\Test\Dispatcher;
use Lithium\Util\Collection;

class DispatcherTest extends \Lithium\Test\Unit {

	public function testRunDefaults() {
		$report = Dispatcher::run();
		$this->assertTrue(is_a($report, '\Lithium\Test\Report'));

		$result = $report->group;
		$this->assertTrue(is_a($result, '\Lithium\Test\Group'));
	}

	public function testRunWithReporter() {
		$report = Dispatcher::run(null, array('reporter' => 'html'));
		$this->assertTrue(is_a($report, '\Lithium\Test\Report'));

		$result = $report->group;
		$this->assertTrue(is_a($result, '\Lithium\Test\Group'));
	}

	public function testRunCaseWithString() {
		$report = Dispatcher::run('\Lithium\Tests\Mocks\Test\MockUnitTest');

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

	public function testRunGroupWithString() {
		$report = Dispatcher::run('\Lithium\Tests\Mocks\Test');

		$expected = '\Lithium\Tests\Mocks\Test';
		$result = $report->title;
		$this->assertEqual($expected, $result);

		$expected = new Collection(array(
			'data' => array(
				new \Lithium\Tests\Mocks\Test\Cases\MockSkipThrowsException(),
				new \Lithium\Tests\Mocks\Test\Cases\MockTest(),
				new \Lithium\Tests\Mocks\Test\Cases\MockTestErrorHandling()
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