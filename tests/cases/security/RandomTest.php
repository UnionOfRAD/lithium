<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\security;

use lithium\security\Random;

class RandomTest extends \lithium\test\Unit {

	/**
	 * Tests the random number generator.
	 */
	public function testRandomGenerator() {
		$check = [];
		$count = 25;
		for ($i = 0; $i < $count; $i++) {
			$result = Random::generate(8);
			$this->assertFalse(in_array($result, $check));
			$check[] = $result;
		}
	}

	/**
	 * Tests the random number generator with base64 encoding.
	 */
	public function testRandom64Generator() {
		$check = [];
		$count = 25;
		$pattern = "/^[0-9A-Za-z\.\/]{11}$/";
		for ($i = 0; $i < $count; $i++) {
			$result = Random::generate(8, ['encode' => Random::ENCODE_BASE_64]);
			$this->assertPattern($pattern, $result);
			$this->assertFalse(in_array($result, $check));
			$check[] = $result;
		}
	}
}

?>