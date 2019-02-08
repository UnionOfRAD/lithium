<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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