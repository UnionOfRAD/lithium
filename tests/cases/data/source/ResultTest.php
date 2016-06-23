<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use lithium\data\source\Result;
use lithium\tests\mocks\data\model\database\MockResult;

class ResultTest extends \lithium\test\Unit {

	public function testIteration() {
		$iterator = new MockResult([
			'records' => ["one", "two", "three", "four"]
		]);
		$result = [];
		$expected = [[0, "one"], [1, "two"], [2, "three"], [3, "four"]];

		foreach ($iterator as $key => $val) {
			$result[] = [$key, $val];
		}
		$this->assertEqual($expected, $result);
	}

	public function testIterationWithPeek() {
		$records = ["one", "two", "three", "four"];
		$iterator = new MockResult(compact('records'));
		$map = [
			"one" => "two",
			"two" => "three",
			"three" => "four",
			"four" => false
		];
		$result = [];

		foreach ($iterator as $key => $val) {
			$result[] = $val;
			$this->assertEqual($iterator->peek(), $map[$val]);
		}
		$this->assertEqual($records, $result);
	}
}

?>