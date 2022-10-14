<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\session\adapter;

use lithium\storage\session\adapter\Memory;

class MemoryTest extends \lithium\test\Unit {

	public $Memory;

	/**
	 * Initializes a new `Memory` adapter.
	 */
	public function setUp() {
		$this->Memory = new Memory();
	}

	/**
	 * Unset the memory adapter.
	 */
	public function tearDown() {
		unset($this->Memory);
	}

	/**
	 * Tests if a correct (and unique) key is loaded upon request.
	 */
	public function testKey() {
		$key1 = Memory::key();
		$this->assertNotEmpty($key1);

		$key2 = Memory::key();
		$this->assertNotEqual($key1, $key2);

		$pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
		$this->assertPattern($pattern, Memory::key());
	}

	/**
	 * This adapter is always enabled by default as it does not rely on any external
	 * dependencies.
	 */
	public function testEnabled() {
		$this->assertTrue(Memory::enabled());
	}

	/**
	 * This adapter is always started when a new object is generated.
	 */
	public function testIsStarted() {
		$this->assertTrue($this->Memory->isStarted());
	}

	/**
	 * Test if reading from the memory adapter works as expected.
	 */
	public function testRead() {
		$writer = $this->Memory->write(null, null);

		$key = 'read_test';
		$value = 'value to be read';

		$writer(compact('key', 'value'));

		$closure = $this->Memory->read($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);

		$this->assertIdentical($value, $result);

		$key = 'non-existent';
		$closure = $this->Memory->read($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertNull($result);

		$closure = $this->Memory->read();
		$this->assertInternalType('callable', $closure);

		$result = $closure(['key' => null], null);
		$expected = ['read_test' => 'value to be read'];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Writes test data into the $_session array.
	 */
	public function testWrite() {
		$reader = $this->Memory->read(null);
		$writer = $this->Memory->write(null, null);

		$key = 'write-test';
		$value = 'value to be written';

		$this->assertInternalType('callable', $writer);

		$params = compact('key', 'value');

		$writer($params);
		$this->assertEqual($reader($params), $value);
	}

	/**
	 * Checks if the session data is empty on creation.
	 */
	public function testCheck() {
		$writer = $this->Memory->write(null, null);

		$key = 'read';
		$value = 'value to be read';

		$writer(compact('key', 'value'));

		$closure = $this->Memory->check($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);

		$key = 'does_not_exist';
		$closure = $this->Memory->check($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertFalse($result);
	}

	/**
	 * Test key deletion.
	 */
	public function testDelete() {
		$writer = $this->Memory->write(null, null);

		$key = 'delete_test';
		$value = 'value to be deleted';

		$writer(compact('key', 'value'));

		$closure = $this->Memory->delete($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);

		$key = 'non-existent';
		$closure = $this->Memory->delete($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);
	}

	/**
	 * Checks if erasing the whole session array works as expected.
	 */
	public function testClear() {
		$reader = $this->Memory->read(null);
		$writer = $this->Memory->write(null, null);

		$writer(['key' => 'foo', 'value' => 'bar']);

		$closure = $this->Memory->clear();
		$this->assertInternalType('callable', $closure);
		$result = $closure([], null);
		$this->assertEmpty($reader(['key' => 'foo']));
	}
}

?>