<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\security;

use lithium\security\Hash;

class HashTest extends \lithium\test\Unit {

	public function testHash() {
		$salt = 'Salt and pepper';
		$value = 'Lithium rocks!';

		$expected = sha1($value);
		$result = Hash::calculate($value, array('type' => 'sha1'));
		$this->assertEqual($expected, $result);

		$result = Hash::calculate($value, array('type' => 'sha1') + compact('salt'));
		$this->assertEqual(sha1($salt . $value), $result);
		$this->assertEqual(md5($value), Hash::calculate($value, array('type' => 'md5')));

		$result = Hash::calculate($value, array('type' => 'md5') + compact('salt'));
		$this->assertEqual(md5($salt . $value), $result);

		$sha256 = function($value) {
			if (function_exists('mhash')) {
				return bin2hex(mhash(MHASH_SHA256, $value));
			} elseif (function_exists('hash')) {
				return hash('sha256', $value);
			}
			throw new Exception();
		};

		try {
			$result = Hash::calculate($value, array('type' => 'sha256'));
			$this->assertEqual($sha256($value), $result);

			$result = Hash::calculate($value, array('type' => 'sha256') + compact('salt'));
			$this->assertEqual($sha256($salt . $value), $result);
		} catch (Exception $e) {
		}

		$string = 'Hash Me';
		$key = 'a very valid key';
		$salt = 'not too much';
		$type = 'sha256';

		$expected = '24f8664f7a7e56f85bd5c983634aaa0b0d3b0e470d7f63494475729cb8b3c6a4ef28398d7cf3';
		$expected .= '780c0caec26c85b56a409920e4af7eef38597861d49fbe31b9a0';

		$result = Hash::calculate($string, compact('key'));
		$this->assertEqual($expected, $result);

		$expected = '35bc1d9a3332e524962909b7ccff6b34ae143f64c48ffa32b5be9312719a96369fbd7ebf6f49';
		$expected .= '09b375135b34e28b063a07b5bd62af165483c6b80dd48a252ddd';

		$result = Hash::calculate($string, compact('salt'));
		$this->assertEqual($expected, $result);

		$expected = 'fa4cfa5c16d7f94e221e1d3a0cb01eadfd6823d68497a5fdcae023d24f557e4a';
		$result = Hash::calculate($string, compact('type', 'key'));
		$this->assertEqual($expected, $result);

		$expected = 'a9050b4f44797bf60262de984ca12967711389cd6c4c4aeee2a739c159f1f667';
		$result = Hash::calculate($string, compact('type'));
		$this->assertEqual($expected, $result);
	}

	public function testCompare() {
		$backup = error_reporting();
		error_reporting(E_ALL);

		$this->assertTrue(Hash::compare('Foo', 'Foo'));
		$this->assertFalse(Hash::compare('Foo', 'foo'));
		$this->assertFalse(Hash::compare('Foo', 'Bar'));

		$this->assertTrue(Hash::compare('', ''));
		$this->assertFalse(Hash::compare('', '0'));
		$this->assertFalse(Hash::compare('0', ''));

		$this->assertException('/to be (a )?string/', function() {
			Hash::compare(null, null);
		});
		$this->assertException('/to be (a )?string/', function() {
			Hash::compare(null, '');
		});
		$this->assertException('/to be (a )?string/', function() {
			Hash::compare('', null);
		});

		$this->assertTrue(Hash::compare('1', '1'));
		$this->assertException('/to be (a )?string/', function() {
			Hash::compare('1', 1);
		});
		$this->assertException('/to be (a )?string/', function() {
			Hash::compare(1, '1');
		});

		error_reporting($backup);
	}
}

?>