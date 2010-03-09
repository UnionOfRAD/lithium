<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\test;

use lithium\test\filter\Coverage;
use lithium\test\Group;
use lithium\test\Report;

class FilterTest extends \lithium\test\Integration {

	public function setUp() {
		$this->report = new Report(array(
			'title' => '\lithium\tests\mocks\test\MockFilterTest',
			'group' => new Group(
				array('items' => array('\lithium\tests\mocks\test\MockFilterClassTest'))
			)
		));
	}

	public function testSingleTest() {
		$this->report->filters = array("Coverage" => "");

		$this->report->run();

		$expected = 40;

		$result = $this->report->results['filters'];
		$percentage = $result['lithium\test\filter\Coverage'];
		$percentage = $percentage['lithium\tests\mocks\test\MockFilterClass'];
		$percentage = $percentage['percentage'];

		$this->assertEqual($expected, $percentage);
	}

	public function testSingleTestWithMultipleFilters() {
		$permutations = array(
			array("Coverage", "Complexity"),
			array("Coverage", "Profiler"),
			array("Coverage", "Affected")

		);
		$this->report->filters = array("Coverage" => "", "Complexity" => "");

		$this->report->run();

		$expected = 40;

		$result = $this->report->results['filters'];
		$percentage = $result['lithium\test\filter\Coverage'];
		$percentage = $percentage['lithium\tests\mocks\test\MockFilterClass'];
		$percentage = $percentage['percentage'];

		$this->assertEqual($expected, $percentage);
	}
}

?>