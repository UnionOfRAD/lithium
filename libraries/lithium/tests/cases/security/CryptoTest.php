<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\security;

use \lithium\security\Crypto;

class CryptoTest extends \lithium\test\Unit {
	/**
	 * testRandomGenerator method
	 *
	 * @return void
	 **/
	public function testRandomGenerator() {
		$check = array();
		$count = 25;
		for ($i = 0; $i < $count; $i++) {
			$result = Crypto::random(8);
			$this->assertFalse(in_array($result, $check));
			$check[] = $result;
		}
	}
	/**
	 * testRandomGenerator method
	 *
	 * @return void
	 **/
	public function testRandom64Generator() {
		$check = array();
		$count = 25;
		$pattern = "/^[0-9A-Za-z\.\/]{11}$/";
		for ($i = 0; $i < $count; $i++) {
			$result = Crypto::random64(8);
			$this->assertPattern($pattern, $result);
			$this->assertFalse(in_array($result, $check));
			$check[] = $result;
		}
	}

	/**
	 * Tests hash generation using `\security\Crypto::hash()`
	 *
	 * @return void
	 */
	public function testHash() {
		$salt = 'Salt and pepper';
		$value = 'Lithium rocks!';

		$expected = sha1($value);
		$result = Crypto::hash($value, 'sha1');
		$this->assertEqual($expected, $result);

		$result = Crypto::hash($value);
		$this->assertEqual($expected, $result);

		$expected = sha1($salt . $value);
		$result = Crypto::hash($value, 'sha1', $salt);
		$this->assertEqual($expected, $result);

		$expected = md5($value);
		$result = Crypto::hash($value, 'md5');
		$this->assertEqual($expected, $result);

		$expected = md5($salt . $value);
		$result = Crypto::hash($value, 'md5', $salt);
		$this->assertEqual($expected, $result);

		$sha256 = function($value) {
			if (function_exists('mhash')) {
				return bin2hex(mhash(MHASH_SHA256, $value));
			} elseif (function_exists('hash')) {
				return hash('sha256', $value);
			}
			throw new Exception();
		};

		try {
			$expected = $sha256($value);
			$result = Crypto::hash($value, 'sha256');
			$this->assertEqual($expected, $result);

			$expected = $sha256($salt . $value);
			$result = Crypto::hash($value, 'sha256', $salt);
			$this->assertEqual($expected, $result);
		} catch (Exception $e) {
		}

		$string = 'Hash Me';
		$key = 'a very valid key';
		$salt = 'not too much';
		$type = 'sha256';

		$expected = '24f8664f7a7e56f85bd5c983634aaa0b0d3b0e470d7f63494475729cb8b3c6a4ef28398d7cf3';
		$expected .= '780c0caec26c85b56a409920e4af7eef38597861d49fbe31b9a0';

		$result = String::hash($string, compact('key'));
		$this->assertEqual($expected, $result);

		$expected = '35bc1d9a3332e524962909b7ccff6b34ae143f64c48ffa32b5be9312719a96369fbd7ebf6f49';
		$expected .= '09b375135b34e28b063a07b5bd62af165483c6b80dd48a252ddd';

		$result = String::hash($string, compact('salt'));
		$this->assertEqual($expected, $result);

		$expected = 'fa4cfa5c16d7f94e221e1d3a0cb01eadfd6823d68497a5fdcae023d24f557e4a';
		$result = String::hash($string, compact('type', 'key'));
		$this->assertEqual($expected, $result);

		$expected = 'a9050b4f44797bf60262de984ca12967711389cd6c4c4aeee2a739c159f1f667';
		$result = String::hash($string, compact('type'));
		$this->assertEqual($expected, $result);
	}
}

?>