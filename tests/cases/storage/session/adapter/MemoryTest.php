<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\session\adapter;

use lithium\storage\session\adapter\Memory;

class MemoryTest extends \lithium\test\Unit {

	/**
	 * Initializes a new `Memory` adapter.
	 */
	public function setUp() {
		$this->memory = new Memory();
	}

	/**
	 * Unset the memory adapter.
	 */
	public function tearDown() {
		unset($this->memory);
	}

	/**
	 * Tests if a correct (and unique) key is loaded upon request.
	 */
	public function testKey() {
		$key1 = Memory::key();
		$this->assertTrue($key1);

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
		$this->assertTrue($this->memory->isStarted());
	}

	/**
	 * Test if reading from the memory adapter works as expected.
	 */
	public function testRead() {
		$this->memory->read();

		$key = 'read_test';
		$value = 'value to be read';

		$this->memory->_session[$key] = $value;

		$closure = $this->memory->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->memory, $params, null);

		$this->assertIdentical($value, $result);

		$key = 'non-existent';
		$closure = $this->memory->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->memory, $params, null);
		$this->assertNull($result);

		$closure = $this->memory->read();
		$this->assertTrue(is_callable($closure));

		$result = $closure($this->memory, array('key' => null), null);
		$expected = array('read_test' => 'value to be read');
		$this->assertEqual($expected, $result);
	}

	/**
	 * Writes test data into the $_session array.
	 */
	public function testWrite() {
		$key = 'write-test';
		$value = 'value to be written';

		$closure = $this->memory->write($key, $value);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'value');
		$result = $closure($this->memory, $params, null);
		$this->assertEqual($this->memory->_session[$key], $value);
	}

	/**
	 * Checks if the session data is empty on creation.
	 */
	public function testCheck() {
		$this->memory->read();

		$key = 'read';
		$value = 'value to be read';
		$this->memory->_session[$key] = $value;

		$closure = $this->memory->check($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->memory, $params, null);
		$this->assertTrue($result);

		$key = 'does_not_exist';
		$closure = $this->memory->check($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->memory, $params, null);
		$this->assertFalse($result);
	}

	/**
	 * Test key deletion.
	 */
	public function testDelete() {
		$this->memory->read();

		$key = 'delete_test';
		$value = 'value to be deleted';

		$this->memory->_session[$key] = $value;

		$closure = $this->memory->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->memory, $params, null);
		$this->assertFalse($result);

		$key = 'non-existent';
		$closure = $this->memory->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->memory, $params, null);
		$this->assertFalse($result);
	}

	/**
	 * Checks if erasing the whole session array works as expected.
	 */
	public function testClear() {
		$this->memory->_session['foobar'] = 'foo';
		$closure = $this->memory->clear();
		$this->assertTrue(is_callable($closure));
		$result = $closure($this->memory, array(), null);
		$this->assertTrue(empty($this->memory->_session));
	}
}

?>