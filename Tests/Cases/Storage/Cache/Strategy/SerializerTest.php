<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Storage\Cache\Strategy;

use Lithium\Storage\Cache\Strategy\Serializer;

class SerializerTest extends \Lithium\Test\Unit {

	public function setUp() {
		$this->Serializer = new Serializer();
	}

	public function testWrite() {
		$data = array('some' => 'data');
		$result = $this->Serializer->write($data);
		$expected = serialize($data);
		$this->assertEqual($expected, $result);
	}

	public function testRead() {
		$encoded = 'a:1:{s:4:"some";s:4:"data";}';
		$expected = unserialize($encoded);
		$result = $this->Serializer->read($encoded);
		$this->assertEqual($expected, $result);
	}
}

?>