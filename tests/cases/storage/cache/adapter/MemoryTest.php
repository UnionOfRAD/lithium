<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
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

		$closure = $this->Memory->write($keys, $expiry);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys', 'expiry');
		$result = $closure($this->Memory, $params);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$closure = $this->Memory->read($keys);
		$this->assertInternalType('callable', $closure);

		$params = array('keys' => array_keys($keys));
		$result = $closure($this->Memory, $params);
		$this->assertEqual($keys, $result);
		$this->assertEqual($this->Memory->cache, array($key => $data));
	}

	public function testMultiWriteAndRead() {
		$keys = array('write1' => 'value1', 'write2' => 'value2');
		$expiry = null;

		$closure = $this->Memory->write($keys, $expiry);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys', 'expiry');
		$result = $closure($this->Memory, $params);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$closure = $this->Memory->read(array_keys($keys));
		$this->assertInternalType('callable', $closure);

		$params = array('keys' => array_keys($keys));
		$result = $closure($this->Memory, $params);
		$this->assertEqual($keys, $result);
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => null
		);
		$result = $this->Memory->write($keys);
		$this->assertTrue($result($this->Memory, compact('keys', 'expiry')));

		$expected = $keys;
		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($expected, $result($this->Memory, array('keys' => array_keys($keys))));
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => null,
			'key2' => 'data2'
		);
		$result = $this->Memory->write($keys);
		$this->assertTrue($result($this->Memory, compact('keys', 'expiry')));

		$expected = $keys;
		$result = $this->Memory->read(array_keys($keys));
		$this->assertEqual($expected, $result($this->Memory, array('keys' => array_keys($keys))));

		$keys = array(
			'key1' => null,
			'key2' => null
		);
		$result = $this->Memory->write($keys);
		$this->assertTrue($result($this->Memory, compact('keys', 'expiry')));
	}

	public function testWriteAndDelete() {
		$key = 'key_to_delete';
		$data = 'some data to be deleted';
		$keys = array($key);
		$expiry = null;

		$closure = $this->Memory->write(array($key => $data), $expiry);
		$this->assertInternalType('callable', $closure);

		$params = array('keys' => array($key => $data));
		$result = $closure($this->Memory, $params);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$closure = $this->Memory->delete($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($this->Memory, $params, null);
		$this->assertTrue($result);

		$keys = array('non_existent');
		$params = compact('keys');
		$result = $closure($this->Memory, $params, null);
		$this->assertFalse($result);
	}

	public function testWriteAndClear() {
		$key = 'key_to_clear';
		$data = 'data to be cleared';
		$keys = array($key);
		$expiry = null;

		$closure = $this->Memory->write(array($key => $data), $expiry);
		$this->assertInternalType('callable', $closure);

		$params = array('keys' => array($key => $data));
		$result = $closure($this->Memory, $params);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$key2 = 'key2_to_clear';
		$data2 = 'data to be cleared';

		$closure = $this->Memory->write(array($key2 => $data2), $expiry);
		$this->assertInternalType('callable', $closure);

		$params = array('keys' => array($key2 => $data2));
		$result = $closure($this->Memory, $params);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->clear();
		$this->assertTrue($result);
		$this->assertEqual(array(), $this->Memory->cache);

		$closure = $this->Memory->write(array($key => $data), $expiry);
		$this->assertInternalType('callable', $closure);

		$params = array('keys' => array($key => $data));
		$result = $closure($this->Memory, $params, null);
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

		$closure = $this->Memory->write($keys, $expiry);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($this->Memory, $params, null);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$closure = $this->Memory->increment($key);
		$params = compact('key');

		$result = $closure($this->Memory, $params, null);
		$this->assertEqual($data + 1, $result);
	}

	public function testDecrement() {
		$key = 'decrement';
		$data = 5;
		$keys = array($key => $data);
		$expiry = null;

		$closure = $this->Memory->write($keys, $expiry);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($this->Memory, $params, null);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$closure = $this->Memory->decrement($key);
		$params = compact('key');

		$result = $closure($this->Memory, $params, null);
		$this->assertEqual($data - 1, $result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$keys = array($key);
		$closure = $this->Memory->read($keys);

		$expected = array();
		$result = $closure($this->Memory, compact('keys'));
		$this->assertIdentical($expected, $result);
	}

	public function testClean() {
		$result = $this->Memory->clean();
		$this->assertFalse($result);
	}
}

?>