<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter;

use \lithium\storage\cache\adapter\Apc;

class ApcTest extends \lithium\test\Unit {

	/**
	 * Skip the test if APC extension is unavailable.
	 *
	 * @return void
	 */
	public function skip() {
		$extensionExists = extension_loaded('apc');
		$message = 'The apc extension is not installed.';
		$this->skipIf(!$extensionExists, $message);
	}

	public function setUp() {
		apc_clear_cache('user');
		$this->Apc = new Apc();
	}

	public function tearDown() {
		apc_clear_cache('user');
		unset($this->Apc);
	}

	public function testEnabled() {
		$apc = $this->Apc;
		$this->assertTrue($apc::enabled());
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->Apc->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertTrue($result);

		$result = apc_fetch($key);
		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$expiry = '+1 minute';
		$time = strtotime($expiry);

		$closure = $this->Apc->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertTrue($result);

		$result = apc_fetch($key);
		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testWriteMulti() {
		$expiry = '+1 minute';
		$time = strtotime($expiry);
		$key = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$data = null;

		$closure = $this->Apc->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Apc, $params, null);

		$this->assertEqual(array(), $result);

		$result = apc_fetch(array_keys($key));
		$this->assertEqual($key, $result);

		$result = apc_delete(array_keys($key));
		$this->assertEqual(array(), $result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = apc_store($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->Apc->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = apc_store($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->Apc->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;

		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testReadMulti() {
		$expiry = '+1 minute';
		$time = strtotime($expiry);
		$key = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$data = null;

		$closure = $this->Apc->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Apc, $params, null);
		$this->assertEqual(array(), $result);

		$expected = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$result = apc_fetch(array_keys($key));
		$this->assertEqual($expected, $result);

		$result = apc_delete(array_keys($key));
		$this->assertEqual(array(), $result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$closure = $this->Apc->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertFalse($result);
	}

	public function testDelete() {
		$key = 'delete_key';
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$result = apc_store($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->Apc->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertTrue($result);
	}

	public function testDeleteMulti() {
		$expiry = '+1 minute';
		$time = strtotime($expiry);
		$key = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$data = null;

		$closure = $this->Apc->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Apc, $params, null);
		$this->assertEqual(array(), $result);

		$expected = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$result = apc_delete(array_keys($key));
		$this->assertEqual(array(), $result);
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$closure = $this->Apc->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->Apc->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertTrue($result);

		$result = apc_fetch($key);
		$this->assertEqual($expected, $result);

		$closure = $this->Apc->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$closure = $this->Apc->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertTrue($result);
	}

	public function testClear() {
		$key1 = 'key_clear_1';
		$key2 = 'key_clear_2';
		$time = strtotime('+1 minute');

		$result = apc_store($key1, 'data that will no longer exist', $time);
		$this->assertTrue($result);

		$result = apc_store($key2, 'more dead data', $time);
		$this->assertTrue($result);

		$result = $this->Apc->clear();
		$this->assertTrue($result);

		$this->assertFalse(apc_fetch($key1));
		$this->assertFalse(apc_fetch($key2));
	}

	public function testDecrement() {
		$time = strtotime('+1 minute');
		$key = 'decrement';
		$value = 10;

		$result = apc_store($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->Apc->decrement($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertEqual($value - 1, $result);

		$result = apc_fetch($key);
		$this->assertEqual($value - 1, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testDecrementNonIntegerValue() {
		$time = strtotime('+1 minute');
		$key = 'non_integer';
		$value = 'no';

		$result = apc_store($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->Apc->decrement($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);

		$result = apc_fetch($key);
		$this->assertEqual('no', $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testIncrement() {
		$time = strtotime('+1 minute');
		$key = 'increment';
		$value = 10;

		$result = apc_store($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->Apc->increment($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertEqual($value + 1, $result);

		$result = apc_fetch($key);
		$this->assertEqual($value + 1, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testIncrementNonIntegerValue() {
		$time = strtotime('+1 minute');
		$key = 'non_integer_increment';
		$value = 'yes';

		$result = apc_store($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->Apc->increment($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);

		$result = apc_fetch($key);
		$this->assertEqual('yes', $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}
}

?>