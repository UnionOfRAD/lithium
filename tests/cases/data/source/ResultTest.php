<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use lithium\data\source\Result;
use lithium\tests\mocks\data\model\database\MockResult;

class ResultTest extends \lithium\test\Unit {

	public function testIteration() {
		$iterator = new MockResult(array(
			'records' => array("one", "two", "three", "four")
		));
		$result = array();
		$expected = array(array(0, "one"), array(1, "two"), array(2, "three"), array(3, "four"));

		foreach ($iterator as $key => $val) {
			$result[] = array($key, $val);
		}
		$this->assertEqual($expected, $result);
	}

	public function testIterationWithPeek() {
		$records = array("one", "two", "three", "four");
		$iterator = new MockResult(compact('records'));
		$map = array(
			"one" => "two",
			"two" => "three",
			"three" => "four",
			"four" => false
		);
		$result = array();

		foreach ($iterator as $key => $val) {
			$result[] = $val;
			$this->assertEqual($iterator->peek(), $map[$val]);
		}
		$this->assertEqual($records, $result);
	}
}

?>