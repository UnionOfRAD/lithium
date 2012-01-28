<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n\multibyte\adapter;

use lithium\g11n\multibyte\adapter\Iconv;

class IconvTest extends \lithium\test\Unit {

	public function skip() {
		$this->skipIf(!Iconv::enabled(), 'The `Iconv` adapter is not enabled.');
	}

	public function testStrlen() {
		$adapter = new Iconv();

		$data = 'äbc';
		$result = $adapter->strlen($data);
		$expected = 3;
		$this->assertEqual($expected, $result);
	}

	public function testStrlenAscii() {
		$adapter = new Iconv();

		$data = 'abc';
		$result = $adapter->strlen($data);
		$expected = 3;
		$this->assertEqual($expected, $result);
	}

	public function testStrlenEmptyish() {
		$adapter = new Iconv();

		$data = '';
		$result = $adapter->strlen($data);
		$expected = 0;
		$this->assertEqual($expected, $result);

		$data = ' ';
		$result = $adapter->strlen($data);
		$expected = 1;
		$this->assertEqual($expected, $result);

		$data = false;
		$result = $adapter->strlen($data);
		$expected = 0;
		$this->assertEqual($expected, $result);

		$data = null;
		$result = $adapter->strlen($data);
		$expected = 0;
		$this->assertEqual($expected, $result);

		$data = 0;
		$result = $adapter->strlen($data);
		$expected = 1;
		$this->assertEqual($expected, $result);

		$data = '0';
		$result = $adapter->strlen($data);
		$expected = 1;
		$this->assertEqual($expected, $result);
	}

	public function testStrlenInvalid() {
		$adapter = new Iconv();

		$this->expectException('/Detected an incomplete multibyte character in input string/');
		$data = "ab\xe9";
		$result = $adapter->strlen($data);
	}
}

?>