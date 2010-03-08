<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\strategy;

use \lithium\storage\cache\strategy\Encoder;

class EncoderTest extends \lithium\test\Unit {

	public function setUp() {
		$this->Encoder = new Encoder();
	}

	public function testWrite() {
		$data = 'a test string';
		$result = $this->Encoder->write($data);
		$expected = base64_encode($data);
		$this->assertEqual($expected, $result);
	}

	public function testRead() {
		$expected = 'a test string';
		$encoded = base64_encode($expected);
		$result = $this->Encoder->read($encoded);
		$this->assertEqual($expected, $result);
	}
}

?>