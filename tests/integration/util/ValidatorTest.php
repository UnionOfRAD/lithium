<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\util;

use lithium\util\Validator;

class ValidatorTest extends \lithium\test\Integration {

	public function skip() {
		$this->skipIf(!$this->_hasNetwork(), 'No network connection.');
	}

	/**
	 * Tests email address validation, with additional hostname lookup
	 */
	public function testEmailDomainCheckGoodMxrr() {
		$this->assertTrue(Validator::isEmail('abc.efg@google.com', null, [
			'deep' => true
		]));
	}

	public function testEmailDomainCheckBadMxrr() {
		$this->assertFalse(Validator::isEmail('abc.efg@foo.invalid', null, [
			'deep' => true
		]));
	}
}

?>