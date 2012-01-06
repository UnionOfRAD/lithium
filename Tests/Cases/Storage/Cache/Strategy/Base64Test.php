<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Storage\Cache\Strategy;

use Lithium\Storage\Cache\Strategy\Base64;

class Base64Test extends \Lithium\Test\Unit {

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