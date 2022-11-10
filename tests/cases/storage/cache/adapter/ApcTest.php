<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\cache\adapter;

use lithium\storage\Cache;
use lithium\storage\cache\adapter\Apc;

class ApcTest extends \lithium\test\Unit {

	public $Apc;

	/**
	 * Skip the test if APC extension is unavailable.
	 */
	public function skip() {
		$this->skipIf(!Apc::enabled(), 'APC is either not loaded or not enabled.');
	}

	public function setUp() {
		apcu_clear_cache();
		$this->Apc = new Apc();
	}

	public function tearDown() {
		apcu_clear_cache();
		unset($this->Apc);
	}

	public function testEnabled() {
		$apc = $this->Apc;
		$this->assertTrue($apc::enabled());
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$keys = [$key => $data];
		$expiry = '+5 seconds';

		$result = $this->Apc->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = $data;
		$result = apcu_fetch($key);
		$this->assertEqual($expected, $result);

		$result = apcu_delete($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$keys = [$key => $data];
		$expiry = '+1 minute';

		$expected = $keys;
		$result = $this->Apc->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = $data;
		$result = apcu_fetch($key);
		$this->assertEqual($expected, $result);

		$result = apcu_delete($key);
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
		$apc = new Apc(['expiry' => '+5 seconds']);
		$keys = ['key1' => 'data1'];
		$expiry = null;

		$result = $apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apcu_exists('key1');
		$this->assertTrue($result);
	}

	public function testWriteNoExpiry() {
		$keys = ['key1' => 'data1'];

		$apc = new Apc(['expiry' => null]);
		$expiry = null;
		$result = $apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apcu_exists('key1');
		$this->assertTrue($result);

		apcu_delete('key1');

		$apc = new Apc(['expiry' => Cache::PERSIST]);
		$expiry = Cache::PERSIST;

		$result = $apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apcu_exists('key1');
		$this->assertTrue($result);

		apcu_delete('key1');

		$apc = new Apc();
		$expiry = Cache::PERSIST;
		$result = $apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apcu_exists('key1');
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
		$keys = ['key1' => 'data1'];
		$expiry = '+5 seconds';
		$this->Apc->write($keys, $expiry);

		$result = apcu_exists('key1');
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
		$keys = ['key1' => 'data1'];
		$expiry = 5;
		$this->Apc->write($keys, $expiry);

		$result = apcu_exists('key1');
		$this->assertTrue($result);
	}

	public function testWriteMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];
		$result = $this->Apc->write($keys, $expiry);
		$this->assertTrue($result);

		$result = apcu_fetch(array_keys($keys));
		$this->assertEqual($keys, $result);

		$result = apcu_delete(array_keys($keys));
		$this->assertEqual([], $result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$keys = [$key];

		$result = apcu_store($key, $data, 60);
		$this->assertTrue($result);

		$expected = [$key => $data];
		$result = $this->Apc->read($keys);
		$this->assertEqual($expected, $result);

		$result = apcu_delete($key);
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$keys = [$key];

		$result = apcu_store($key, $data, 60);
		$this->assertTrue($result);

		$expected = [$key => $data];
		$result = $this->Apc->read($keys);
		$this->assertEqual($expected, $result);

		$result = apcu_delete($key);
		$this->assertTrue($result);
	}

	public function testReadMulti() {
		$keys = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];
		apcu_store($keys, null, 60);

		$expected = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];
		$keys = [
			'key1',
			'key2',
			'key3'
		];
		$result = $this->Apc->read($keys);
		$this->assertEqual($expected, $result);

		$result = apcu_delete($keys);
		$this->assertEqual([], $result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$keys = [$key];

		$expected = [];
		$result = $this->Apc->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testReadWithScope() {
		$adapter = new Apc(['scope' => 'primary']);

		apcu_store('primary:key1', 'test1', 60);
		apcu_store('key1', 'test2', 60);

		$keys = ['key1'];
		$expected = ['key1' => 'test1'];
		$result = $adapter->read($keys);
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null
		];
		$result = $this->Apc->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Apc->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null,
			'key2' => 'data2'
		];
		$result = $this->Apc->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Apc->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$keys = [
			'key1' => null,
			'key2' => null
		];
		$result = $this->Apc->write($keys);
		$this->assertTrue($result);
	}

	public function testWriteAndReadArray() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => ['foo' => 'bar']
		];
		$result = $this->Apc->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->Apc->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteWithScope() {
		$adapter = new Apc(['scope' => 'primary']);

		$keys = ['key1' => 'test1'];
		$expiry = '+1 minute';
		$adapter->write($keys, $expiry);

		$expected = 'test1';
		$result = apcu_fetch('primary:key1');
		$this->assertEqual($expected, $result);

		$result = apcu_fetch('key1');
		$this->assertFalse($result);
	}

	public function testDelete() {
		$key = 'delete_key';
		$data = 'data to delete';
		$keys = [$key];

		$result = apcu_store($key, $data, 60);
		$this->assertTrue($result);

		$result = $this->Apc->delete($keys);
		$this->assertTrue($result);
	}

	public function testDeleteMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];
		apcu_store($keys, null, 60);

		$keys = [
			'key1',
			'key2',
			'key3'
		];
		$result = $this->Apc->delete($keys);
		$this->assertTrue($result);

		$result = apcu_delete($keys);
		$this->assertEqual($keys, $result);
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$data = 'data to delete';
		$keys = [$key];

		$result = $this->Apc->delete($keys);
		$this->assertFalse($result);
	}

	public function testDeleteWithScope() {
		$adapter = new Apc(['scope' => 'primary']);

		apcu_store('primary:key1', 'test1', 60);
		apcu_store('key1', 'test2', 60);

		$keys = ['key1'];
		$expected = ['key1' => 'test1'];
		$adapter->delete($keys);

		$result = apcu_exists('key1');
		$this->assertTrue($result);

		$result = apcu_exists('primary:key1');
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$keys = [$key => $data];
		$expiry = '+5 seconds';

		$result = $this->Apc->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = $data;
		$result = apcu_fetch($key);
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

		$result = apcu_store($key1, 'data that will no longer exist', 60);
		$this->assertTrue($result);

		$result = apcu_store($key2, 'more dead data', 60);
		$this->assertTrue($result);

		$result = $this->Apc->clear();
		$this->assertTrue($result);

		$this->assertFalse(apcu_fetch($key1));
		$this->assertFalse(apcu_fetch($key2));
	}

	public function testDecrement() {
		$key = 'decrement';
		$value = 10;

		$result = apcu_store($key, $value, 60);
		$this->assertTrue($result);

		$result = $this->Apc->decrement($key);
		$this->assertEqual($value - 1, $result);

		$result = apcu_fetch($key);
		$this->assertEqual($value - 1, $result);

		$result = apcu_delete($key);
		$this->assertTrue($result);
	}

	public function testDecrementNonIntegerValue() {
		$key = 'non_integer';
		$value = 'no';

		$result = apcu_store($key, $value, 60);
		$this->assertTrue($result);

		$this->Apc->decrement($key);

		$result = apcu_fetch($key);
		$this->assertEqual('no', $result);

		$result = apcu_delete($key);
		$this->assertTrue($result);
	}

	public function testDecrementWithScope() {
		$adapter = new Apc(['scope' => 'primary']);

		apcu_store('primary:key1', 1, 60);
		apcu_store('key1', 1, 60);

		$adapter->decrement('key1');

		$expected = 1;
		$result = apcu_fetch('key1');
		$this->assertEqual($expected, $result);

		$expected = 0;
		$result = apcu_fetch('primary:key1');
		$this->assertEqual($expected, $result);
	}

	public function testIncrement() {
		$key = 'increment';
		$value = 10;

		$result = apcu_store($key, $value, 60);
		$this->assertTrue($result);

		$result = $this->Apc->increment($key);
		$this->assertEqual($value + 1, $result);

		$result = apcu_fetch($key);
		$this->assertEqual($value + 1, $result);

		$result = apcu_delete($key);
		$this->assertTrue($result);
	}

	public function testIncrementNonIntegerValue() {
		$key = 'non_integer_increment';
		$value = 'yes';

		$result = apcu_store($key, $value, 60);
		$this->assertTrue($result);

		$this->Apc->increment($key);

		$result = apcu_fetch($key);
		$this->assertEqual('yes', $result);

		$result = apcu_delete($key);
		$this->assertTrue($result);
	}

	public function testIncrementWithScope() {
		$adapter = new Apc(['scope' => 'primary']);

		apcu_store('primary:key1', 1, 60);
		apcu_store('key1', 1, 60);

		$adapter->increment('key1');

		$expected = 1;
		$result = apcu_fetch('key1');
		$this->assertEqual($expected, $result);

		$expected = 2;
		$result = apcu_fetch('primary:key1');
		$this->assertEqual($expected, $result);
	}
}

?>