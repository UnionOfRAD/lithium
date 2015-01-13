<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter;

use lithium\storage\cache\adapter\Memory;

class MemoryTest extends \lithium\test\Unit {

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
		$keys = array($key => $data);
		$expiry = null;

		$result = $this->Memory->write($keys, $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($keys, $result);
		$this->assertEqual($this->Memory->cache, array($key => $data));
	}

	public function testMultiWriteAndRead() {
		$keys = array('write1' => 'value1', 'write2' => 'value2');
		$expiry = null;

		$result = $this->Memory->write($keys, $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($keys, $result);
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => null
		);
		$result = $this->Memory->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => null,
			'key2' => 'data2'
		);
		$result = $this->Memory->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$keys = array(
			'key1' => null,
			'key2' => null
		);
		$result = $this->Memory->write($keys);
		$this->assertTrue($result);
	}

	public function testWriteAndDelete() {
		$key = 'key_to_delete';
		$data = 'some data to be deleted';
		$keys = array($key);
		$expiry = null;

		$result = $this->Memory->write(array($key => $data), $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->delete($keys);
		$this->assertTrue($result);

		$keys = array('non_existent');
		$result = $this->Memory->delete($keys);
		$this->assertFalse($result);
	}

	public function testWriteAndClear() {
		$key = 'key_to_clear';
		$data = 'data to be cleared';
		$keys = array($key);
		$expiry = null;

		$result = $this->Memory->write(array($key => $data), $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$key2 = 'key2_to_clear';
		$data2 = 'data to be cleared';

		$result = $this->Memory->write(array($key2 => $data2), $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->clear();
		$this->assertTrue($result);
		$this->assertEqual(array(), $this->Memory->cache);

		$result = $this->Memory->write(array($key => $data), $expiry);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->clear();
		$this->assertTrue($result);
		$this->assertEqual(array(), $this->Memory->cache);
	}

	public function testIncrement() {
		$key = 'incremental';
		$data = 5;
		$keys = array($key => $data);
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
		$keys = array($key => $data);
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
		$keys = array($key);

		$expected = array();
		$result = $this->Memory->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testClean() {
		$result = $this->Memory->clean();
		$this->assertFalse($result);
	}
}

?>