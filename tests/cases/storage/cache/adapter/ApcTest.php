<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter;

use lithium\storage\Cache;
use lithium\storage\cache\adapter\Apc;

class ApcTest extends \lithium\test\Unit {

	/**
	 * Skip the test if APC extension is unavailable.
	 */
	public function skip() {
		$this->skipIf(!Apc::enabled(), 'APC is either not loaded or not enabled.');
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
		$keys = array($key => $data);
		$expiry = '+5 seconds';

		$result = $this->Apc->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = $data;
		$result = apc_fetch($key);
		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$keys = array($key => $data);
		$expiry = '+1 minute';

		$expected = $keys;
		$result = $this->Apc->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = $data;
		$result = apc_fetch($key);
		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	/**
	 * Tests that an item can be written to the cache using
	 * the default expiration.
	 *
	 * Note that because of the nature of APC we cannot test if an item
	 * correctly expires. Expiration checks are done by APC only on each
	 * _page request_.
	 */
	public function testWriteExpiryDefault() {
		$apc = new Apc(array('expiry' => '+5 seconds'));
		$keys = array('key1' => 'data1');
		$expiry = null;

		$result = $apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apc_exists('key1');
		$this->assertTrue($result);
	}

	public function testWriteNoExpiry() {
		$keys = array('key1' => 'data1');

		$apc = new Apc(array('expiry' => null));
		$expiry = null;
		$result = $apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apc_exists('key1');
		$this->assertTrue($result);

		apc_delete('key1');

		$apc = new Apc(array('expiry' => Cache::PERSIST));
		$expiry = Cache::PERSIST;

		$result = $apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apc_exists('key1');
		$this->assertTrue($result);

		apc_delete('key1');

		$apc = new Apc();
		$expiry = Cache::PERSIST;
		$result = $apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apc_exists('key1');
		$this->assertTrue($result);
	}

	/**
	 * Tests that an item can be written to the cache using
	 * `strtotime` syntax.
	 *
	 * Note that because of the nature of APC we cannot test if an item
	 * correctly expires. Expiration checks are done by APC only on each
	 * _page request_.
	 */
	public function testWriteExpiryExpires() {
		$keys = array('key1' => 'data1');
		$expiry = '+5 seconds';
		$this->Apc->write($keys, $expiry);

		$result = apc_exists('key1');
		$this->assertTrue($result);
	}

	/**
	 * Tests that an item can be written to the cache using
	 * TTL syntax.
	 *
	 * Note that because of the nature of APC we cannot test if an item
	 * correctly expires. Expiration checks are done by APC only on each
	 * _page request_.
	 */
	public function testWriteExpiryTtl() {
		$keys = array('key1' => 'data1');
		$expiry = 5;
		$this->Apc->write($keys, $expiry);

		$result = apc_exists('key1');
		$this->assertTrue($result);
	}

	public function testWriteMulti() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$result = $this->Apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apc_fetch(array_keys($keys));
		$this->assertEqual($keys, $result);

		$result = apc_delete(array_keys($keys));
		$this->assertEqual(array(), $result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$keys = array($key);

		$result = apc_store($key, $data, 60);
		$this->assertTrue($result);

		$expected = array($key => $data);
		$result = $this->Apc->read($keys);
		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$keys = array($key);

		$result = apc_store($key, $data, 60);
		$this->assertTrue($result);

		$expected = array($key => $data);
		$result = $this->Apc->read($keys);
		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testReadMulti() {
		$keys = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		apc_store($keys, null, 60);

		$expected = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$keys = array(
			'key1',
			'key2',
			'key3'
		);
		$result = $this->Apc->read($keys);
		$this->assertEqual($expected, $result);

		$result = apc_delete($keys);
		$this->assertEqual(array(), $result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$keys = array($key);

		$expected = array();
		$result = $this->Apc->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testReadWithScope() {
		$adapter = new Apc(array('scope' => 'primary'));

		apc_store('primary:key1', 'test1', 60);
		apc_store('key1', 'test2', 60);

		$keys = array('key1');
		$expected = array('key1' => 'test1');
		$result = $adapter->read($keys);
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => null
		);
		$result = $this->Apc->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Apc->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => null,
			'key2' => 'data2'
		);
		$result = $this->Apc->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Apc->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$keys = array(
			'key1' => null,
			'key2' => null
		);
		$result = $this->Apc->write($keys);
		$this->assertTrue($result);
	}

	public function testWriteAndReadArray() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => array('foo' => 'bar')
		);
		$result = $this->Apc->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Apc->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteWithScope() {
		$adapter = new Apc(array('scope' => 'primary'));

		$keys = array('key1' => 'test1');
		$expiry = '+1 minute';
		$adapter->write($keys, $expiry);

		$expected = 'test1';
		$result = apc_fetch('primary:key1');
		$this->assertEqual($expected, $result);

		$result = apc_fetch('key1');
		$this->assertFalse($result);
	}

	public function testDelete() {
		$key = 'delete_key';
		$data = 'data to delete';
		$keys = array($key);

		$result = apc_store($key, $data, 60);
		$this->assertTrue($result);

		$result = $this->Apc->delete($keys);
		$this->assertTrue($result);
	}

	public function testDeleteMulti() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		apc_store($keys, null, 60);

		$keys = array(
			'key1',
			'key2',
			'key3'
		);
		$result = $this->Apc->delete($keys);
		$this->assertTrue($result);

		$result = apc_delete($keys);
		$this->assertEqual($keys, $result);
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$data = 'data to delete';
		$keys = array($key);

		$result = $this->Apc->delete($keys);
		$this->assertFalse($result);
	}

	public function testDeleteWithScope() {
		$adapter = new Apc(array('scope' => 'primary'));

		apc_store('primary:key1', 'test1', 60);
		apc_store('key1', 'test2', 60);

		$keys = array('key1');
		$expected = array('key1' => 'test1');
		$adapter->delete($keys);

		$result = apc_exists('key1');
		$this->assertTrue($result);

		$result = apc_exists('primary:key1');
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$keys = array($key => $data);
		$expiry = '+5 seconds';

		$result = $this->Apc->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = $data;
		$result = apc_fetch($key);
		$this->assertEqual($expected, $result);

		$expected = $keys;
		$result = $this->Apc->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$result = $this->Apc->delete(array_keys($keys));
		$this->assertTrue($result);
	}

	public function testClear() {
		$key1 = 'key_clear_1';
		$key2 = 'key_clear_2';

		$result = apc_store($key1, 'data that will no longer exist', 60);
		$this->assertTrue($result);

		$result = apc_store($key2, 'more dead data', 60);
		$this->assertTrue($result);

		$result = $this->Apc->clear();
		$this->assertTrue($result);

		$this->assertFalse(apc_fetch($key1));
		$this->assertFalse(apc_fetch($key2));
	}

	public function testDecrement() {
		$key = 'decrement';
		$value = 10;

		$result = apc_store($key, $value, 60);
		$this->assertTrue($result);

		$result = $this->Apc->decrement($key);
		$this->assertEqual($value - 1, $result);

		$result = apc_fetch($key);
		$this->assertEqual($value - 1, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testDecrementNonIntegerValue() {
		$key = 'non_integer';
		$value = 'no';

		$result = apc_store($key, $value, 60);
		$this->assertTrue($result);

		$this->Apc->decrement($key);

		$result = apc_fetch($key);
		$this->assertEqual('no', $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testDecrementWithScope() {
		$adapter = new Apc(array('scope' => 'primary'));

		apc_store('primary:key1', 1, 60);
		apc_store('key1', 1, 60);

		$adapter->decrement('key1');

		$expected = 1;
		$result = apc_fetch('key1');
		$this->assertEqual($expected, $result);

		$expected = 0;
		$result = apc_fetch('primary:key1');
		$this->assertEqual($expected, $result);
	}

	public function testIncrement() {
		$key = 'increment';
		$value = 10;

		$result = apc_store($key, $value, 60);
		$this->assertTrue($result);

		$result = $this->Apc->increment($key);
		$this->assertEqual($value + 1, $result);

		$result = apc_fetch($key);
		$this->assertEqual($value + 1, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testIncrementNonIntegerValue() {
		$key = 'non_integer_increment';
		$value = 'yes';

		$result = apc_store($key, $value, 60);
		$this->assertTrue($result);

		$this->Apc->increment($key);

		$result = apc_fetch($key);
		$this->assertEqual('yes', $result);

		$result = apc_delete($key);
		$this->assertTrue($result);
	}

	public function testIncrementWithScope() {
		$adapter = new Apc(array('scope' => 'primary'));

		apc_store('primary:key1', 1, 60);
		apc_store('key1', 1, 60);

		$adapter->increment('key1');

		$expected = 1;
		$result = apc_fetch('key1');
		$this->assertEqual($expected, $result);

		$expected = 2;
		$result = apc_fetch('primary:key1');
		$this->assertEqual($expected, $result);
	}
}

?>