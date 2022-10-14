<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\cache\adapter;

use lithium\storage\cache\adapter\Memory;

class MemoryTest extends \lithium\test\Unit {

	public $Memory;

	public function setUp() {
		$this->Memory = new Memory();
	}

	public function tearDown() {
		unset($this->Memory);
	}

	public function testEnabled() {
		$memory = $this->Memory;
		$this->assertTrue($memory::enabled());
	}

	public function testWriteAndRead() {
		$key = 'key';
		$data = 'data';
		$keys = [$key => $data];
		$expiry = null;

		$result = $this->Memory->write($keys, $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($keys, $result);
		$this->assertEqual($this->Memory->cache, [$key => $data]);
	}

	public function testMultiWriteAndRead() {
		$keys = ['write1' => 'value1', 'write2' => 'value2'];
		$expiry = null;

		$result = $this->Memory->write($keys, $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($keys, $result);
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null
		];
		$result = $this->Memory->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null,
			'key2' => 'data2'
		];
		$result = $this->Memory->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$keys = [
			'key1' => null,
			'key2' => null
		];
		$result = $this->Memory->write($keys);
		$this->assertTrue($result);
	}

	public function testWriteAndDelete() {
		$key = 'key_to_delete';
		$data = 'some data to be deleted';
		$keys = [$key];
		$expiry = null;

		$result = $this->Memory->write([$key => $data], $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->delete($keys);
		$this->assertTrue($result);

		$keys = ['non_existent'];
		$result = $this->Memory->delete($keys);
		$this->assertFalse($result);
	}

	public function testWriteAndClear() {
		$key = 'key_to_clear';
		$data = 'data to be cleared';
		$keys = [$key];
		$expiry = null;

		$result = $this->Memory->write([$key => $data], $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$key2 = 'key2_to_clear';
		$data2 = 'data to be cleared';

		$result = $this->Memory->write([$key2 => $data2], $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->clear();
		$this->assertTrue($result);
		$this->assertEqual([], $this->Memory->cache);

		$result = $this->Memory->write([$key => $data], $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->clear();
		$this->assertTrue($result);
		$this->assertEqual([], $this->Memory->cache);
	}

	public function testIncrement() {
		$key = 'incremental';
		$data = 5;
		$keys = [$key => $data];
		$expiry = null;

		$result = $this->Memory->write($keys, $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->increment($key);
		$this->assertEqual($data + 1, $result);

		$result = $this->Memory->increment($key, 2);
		$this->assertEqual($data + 3, $result);
	}

	public function testIncrementNotExistent() {
		$key = 'incremental_not_existent';

		$result = $this->Memory->increment($key);
		$this->assertFalse($result);
	}

	public function testDecrement() {
		$key = 'decrement';
		$data = 5;
		$keys = [$key => $data];
		$expiry = null;

		$result = $this->Memory->write($keys, $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->decrement($key);
		$this->assertEqual($data - 1, $result);

		$result = $this->Memory->decrement($key, 2);
		$this->assertEqual($data - 3, $result);
	}

	public function testDecrementNotExistent() {
		$key = 'decrement_not_existent';

		$result = $this->Memory->decrement($key);
		$this->assertFalse($result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$keys = [$key];

		$expected = [];
		$result = $this->Memory->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testClean() {
		$result = $this->Memory->clean();
		$this->assertFalse($result);
	}
}

?>