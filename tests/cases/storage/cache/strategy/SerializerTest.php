<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\cache\strategy;

use lithium\storage\cache\strategy\Serializer;

class SerializerTest extends \lithium\test\Unit {

	public $Serializer;

	public function setUp() {
		$this->Serializer = new Serializer();
	}

	public function testWrite() {
		$data = ['some' => 'data'];
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