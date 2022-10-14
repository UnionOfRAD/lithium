<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\cache\strategy;

use lithium\storage\cache\strategy\Base64;

class Base64Test extends \lithium\test\Unit {

	public $Base64;

	public function setUp() {
		$this->Base64 = new Base64();
	}

	public function testWrite() {
		$data = 'a test string';
		$result = $this->Base64->write($data);
		$expected = base64_encode($data);
		$this->assertEqual($expected, $result);
	}

	public function testRead() {
		$expected = 'a test string';
		$encoded = base64_encode($expected);
		$result = $this->Base64->read($encoded);
		$this->assertEqual($expected, $result);
	}
}

?>