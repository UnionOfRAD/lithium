<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\g11n;

use lithium\core\Libraries;
use lithium\g11n\Catalog;
use lithium\util\Validator;

class ResourcesValidatorTest extends \lithium\test\Integration {

	protected $_backup = [];

	public function setUp() {
		$this->_backup['catalogConfig'] = Catalog::config();
		Catalog::config([
			'lithium' => [
				'adapter' => 'Php',
				'path' => Libraries::get('lithium', 'path') . '/g11n/resources/php'
			]
		]);
	}

	public function tearDown() {
		Catalog::reset();
		Validator::reset();
		Catalog::config($this->_backup['catalogConfig']);
	}

	public function testDaDk() {
		Validator::add(Catalog::read('lithium', 'validation', 'da_DK'));

		$this->assertTrue(Validator::isSsn('123456-1234'));
		$this->assertFalse(Validator::isSsn('12345-1234'));
	}

	public function testDeBe() {
		Validator::add(Catalog::read('lithium', 'validation', 'de_BE'));

		$this->assertTrue(Validator::isPostalCode('1234'));
		$this->assertTrue(Validator::isPostalCode('1234'));
		$this->assertFalse(Validator::isPostalCode('0123'));
	}

	public function testDeDe() {
		Validator::add(Catalog::read('lithium', 'validation', 'de_DE'));

		$this->assertTrue(Validator::isPostalCode('12345'));
		$this->assertFalse(Validator::isPostalCode('123456'));
	}

	public function testEnCa() {
		Validator::add(Catalog::read('lithium', 'validation', 'en_CA'));

		$this->assertTrue(Validator::isPhone('(401) 321-9876'));

		$this->assertTrue(Validator::isPostalCode('M5J 2G8'));
		$this->assertTrue(Validator::isPostalCode('H2X 3X5'));
	}

	public function testEnGb() {
		Validator::add(Catalog::read('lithium', 'validation', 'en_GB'));

		$this->assertTrue(Validator::isPostalCode('M1 1AA'));
		$this->assertTrue(Validator::isPostalCode('M60 1NW'));
		$this->assertTrue(Validator::isPostalCode('CR2 6XH'));
		$this->assertTrue(Validator::isPostalCode('DN55 1PT'));
		$this->assertTrue(Validator::isPostalCode('W1A 1HQ'));
		$this->assertTrue(Validator::isPostalCode('EC1A 1BB'));
		$this->assertTrue(Validator::isPostalCode('FK7 0AQ'));
		$this->assertTrue(Validator::isPostalCode('FK8 2ET'));
		$this->assertTrue(Validator::isPostalCode('FK8 1EB'));
		$this->assertTrue(Validator::isPostalCode('EH1 1QX'));
		$this->assertFalse(Validator::isPostalCode('EH1-1QX'));
		$this->assertFalse(Validator::isPostalCode('EH11QX'));
		$this->assertFalse(Validator::isPostalCode('FEH1 1QX'));
	}

	public function testEnUs() {
		Validator::add(Catalog::read('lithium', 'validation', 'en_US'));

		$this->assertTrue(Validator::isPhone('(401) 321-9876'));

		$this->assertTrue(Validator::isPostalCode('11201'));
		$this->assertTrue(Validator::isPostalCode('11201-0456'));

		$this->assertTrue(Validator::isSsn('478-36-4120'));
		$this->assertFalse(Validator::isSsn('478-36-41200'));
		$this->assertFalse(Validator::isSsn('478364120'));
	}

	public function testFrBe() {
		Validator::add(Catalog::read('lithium', 'validation', 'fr_BE'));

		$this->assertTrue(Validator::isPostalCode('1234'));
		$this->assertTrue(Validator::isPostalCode('1234'));
		$this->assertFalse(Validator::isPostalCode('0123'));
	}

	public function testFrCa() {
		Validator::add(Catalog::read('lithium', 'validation', 'fr_CA'));

		$this->assertTrue(Validator::isPhone('(401) 321-9876'));

		$this->assertTrue(Validator::isPostalCode('M5J 2G8'));
		$this->assertTrue(Validator::isPostalCode('H2X 3X5'));
	}

	public function testItIt() {
		Validator::add(Catalog::read('lithium', 'validation', 'it_IT'));

		$this->assertTrue(Validator::isPostalCode('12345'));
		$this->assertFalse(Validator::isPostalCode('123456'));
	}

	public function testNlBe() {
		Validator::add(Catalog::read('lithium', 'validation', 'nl_BE'));

		$this->assertTrue(Validator::isPostalCode('1234'));
		$this->assertTrue(Validator::isPostalCode('1234'));
		$this->assertFalse(Validator::isPostalCode('0123'));
	}

	public function testNlNl() {
		Validator::add(Catalog::read('lithium', 'validation', 'nl_NL'));

		$this->assertTrue(Validator::isSsn('123456789'));
		$this->assertFalse(Validator::isSsn('12345678'));
	}
}

?>