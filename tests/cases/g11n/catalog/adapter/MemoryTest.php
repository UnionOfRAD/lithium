<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\g11n\catalog\adapter;

use lithium\g11n\catalog\adapter\Memory;

class MemoryTest extends \lithium\test\Unit {

	public $adapter;

	public function setUp() {
		$this->adapter = new Memory();
	}

	public function tearDown() {
	}

	public function testReadAndWrite() {
		$data = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => 'plural 1'],
				'flags' => ['fuzzy' => true],
				'translated' => [],
				'occurrences' => [
					['file' => 'test.php', 'line' => 1]
				],
				'comments' => [
					'comment 1'
				]
			]
		];
		$result = $this->adapter->write('category', 'ja', 'default', $data);
		$this->assertEqual($data, $this->adapter->read('category', 'ja', 'default'));
	}

	public function testReadNonExistent() {
		$result = $this->adapter->read('messageTemplate', 'root', null);
		$this->assertEmpty($result);
	}
}

?>