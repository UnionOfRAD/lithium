<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\cache\strategy;

use lithium\storage\cache\strategy\Json;

class JsonTest extends \lithium\test\Unit {

	public $Json;

	public function setUp() {
		$this->Json = new Json();
	}

	public function testWrite() {
		$data = ['some' => 'data'];
		$result = $this->Json->write($data);
		$expected = json_encode($data);
		$this->assertEqual($expected, $result);
	}

	public function testRead() {
		$expected = ['some' => 'data'];
		$encoded = json_encode($expected);
		$result = $this->Json->read($encoded);
		$this->assertEqual($expected, $result);
	}
}

?>