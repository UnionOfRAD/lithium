<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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