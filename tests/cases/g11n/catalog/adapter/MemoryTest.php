<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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