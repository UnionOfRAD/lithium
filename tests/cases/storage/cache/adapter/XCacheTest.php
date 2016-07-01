<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\cache\adapter;

use lithium\storage\Cache;
use lithium\storage\cache\adapter\XCache;

/**
 * @deprecated
 */
class XCacheTest extends \lithium\test\Unit {

	protected $_backup = null;

	/**
	 * Skip the test if XCache extension is unavailable.
	 */
	public function skip() {
		$extensionExists = (extension_loaded('xcache') && (ini_get('xcache.var_size') !== 0));
		$message = 'The XCache extension is not installed or not configured for userspace caching.';
		$this->skipIf(!$extensionExists, $message);
	}

	/**
	 * Clear the userspace cache
	 */
	public function setUp() {
		 error_reporting(($this->_backup = error_reporting()) & ~E_USER_DEPRECATED);

		for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
			if (xcache_clear_cache(XC_TYPE_VAR, $i) === false) {
				return false;
			}
		}
		$this->XCache = new XCache();
	}

	public function tearDown() {
		unset($this->XCache);
		error_reporting($this->_backup);
	}

	public function testEnabled() {
		$xcache = $this->XCache;
		$this->assertTrue($xcache::enabled());
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$keys = [$key => $data];
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$result = $this->XCache->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = $data;
		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$keys = [$key => $data];
		$expiry = '+1 minute';
		$time = strtotime($expiry);

		$result = $this->XCache->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = $data;
		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testWriteMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];
		$result = $this->XCache->write($keys, $expiry);
		$this->assertTrue($result);

		foreach ($keys as $key => $data) {
			$expected = $data;
			$result = xcache_get($key);
			$this->assertEqual($expected, $result);

			xcache_unset($key);
		}
	}

	public function testWriteExpiryDefault() {
		$xCache = new XCache(['expiry' => '+5 seconds']);
		$key = 'default_key';
		$data = 'value';
		$keys = [$key => $data];
		$time = strtotime('+5 seconds');

		$result = $xCache->write($keys);
		$this->assertTrue($result);

		$expected = $data;
		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testWriteNoExpiry() {
		$keys = ['key1' => 'data1'];

		$adapter = new XCache(['expiry' => null]);
		$expiry = null;

		$result = $adapter->write($keys, $expiry);
		$this->assertTrue($result);

		$result = xcache_isset('key1');
		$this->assertTrue($result);

		xcache_unset('key1');

		$adapter = new XCache(['expiry' => Cache::PERSIST]);
		$expiry = Cache::PERSIST;

		$result = $adapter->write($keys, $expiry);
		$this->assertTrue($result);

		$result = xcache_isset('key1');
		$this->assertTrue($result);

		xcache_unset('key1');

		$adapter = new XCache();
		$expiry = Cache::PERSIST;

		$result = $adapter->write($keys, $expiry);
		$this->assertTrue($result);

		$result = xcache_isset('key1');
		$this->assertTrue($result);
	}

	/**
	 * Tests that an item can be written to the cache using
	 * `strtotime` syntax.
	 *
	 * Note that because of the nature of XCache we cannot test if an item
	 * correctly expires. Expiration checks are done by XCache only on each
	 * _page request_.
	 */
	public function testWriteExpiryExpires() {
		$keys = ['key1' => 'data1'];
		$expiry = '+5 seconds';
		$this->XCache->write($keys, $expiry);

		$result = xcache_isset('key1');
		$this->assertTrue($result);

		xcache_unset('key1');
	}

	/**
	 * Tests that an item can be written to the cache using
	 * TTL syntax.
	 *
	 * Note that because of the nature of XCache we cannot test if an item
	 * correctly expires. Expiration checks are done by XCache only on each
	 * _page request_.
	 */
	public function testWriteExpiryTtl() {
		$keys = ['key1' => 'data1'];
		$expiry = 5;
		$this->XCache->write($keys, $expiry);

		$result = xcache_isset('key1');
		$this->assertTrue($result);

		xcache_unset('key1');

		$keys = ['key1' => 'data1'];
		$expiry = 1;
		$this->XCache->write($keys, $expiry);
	}

	public function testWriteWithScope() {
		$adapter = new XCache(['scope' => 'primary']);

		$keys = ['key1' => 'test1'];
		$expiry = '+1 minute';
		$adapter->write($keys, $expiry);

		$expected = 'test1';
		$result = xcache_get('primary:key1');
		$this->assertEqual($expected, $result);

		$result = xcache_get('key1');
		$this->assertNull($result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$keys = [$key];
		$time = strtotime('+1 minute');

		$result = xcache_set($key, $data, 60);
		$this->assertTrue($result);

		$expected = [$key => $data];
		$result = $this->XCache->read($keys);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$keys = [$key];
		$time = strtotime('+1 minute');

		$result = xcache_set($key, $data, 60);
		$this->assertTrue($result);

		$expected = [$key => $data];
		$result = $this->XCache->read($keys);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$keys = [$key];

		$expected = [];
		$result = $this->XCache->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testReadWithScope() {
		$adapter = new XCache(['scope' => 'primary']);

		xcache_set('primary:key1', 'test1', 60);
		xcache_set('key1', 'test2', 60);

		$keys = ['key1'];
		$expected = ['key1' => 'test1'];
		$result = $adapter->read($keys);
		$this->assertEqual($expected, $result);
	}

	public function testReadMulti() {
		$keys = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];
		foreach ($keys as $key => $data) {
			xcache_set($key, $data, 60);
		}

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
		$result = $this->XCache->read($keys);
		$this->assertEqual($expected, $result);

		foreach ($keys as $key) {
			xcache_unset($key);
		}
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null
		];
		$result = $this->XCache->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->XCache->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null,
			'key2' => 'data2'
		];
		$result = $this->XCache->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->XCache->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$keys = [
			'key1' => null,
			'key2' => null
		];
		$result = $this->XCache->write($keys);
		$this->assertTrue($result);
	}

	public function testDelete() {
		$key = 'delete_key';
		$keys = [$key];
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$result = xcache_set($key, $data, 60);
		$this->assertTrue($result);

		$result = $this->XCache->delete($keys);
		$this->assertTrue($result);
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$data = 'data to delete';
		$keys = [$key];
		$time = strtotime('+1 minute');

		$result = $this->XCache->delete($keys);
		$this->assertFalse($result);
	}

	public function testDeleteWithScope() {
		$adapter = new XCache(['scope' => 'primary']);

		xcache_set('primary:key1', 'test1', 60);
		xcache_set('key1', 'test2', 60);

		$keys = ['key1'];
		$expected = ['key1' => 'test1'];
		$result = $adapter->delete($keys);
		$this->assertEqual($expected, $result);

		$result = xcache_isset('key1');
		$this->assertTrue($result);

		$result = xcache_isset('primary:key1');
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$keys = [$key => $data];
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$result = $this->XCache->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = $data;
		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$keys = [$key];

		$expected = [$key => $data];
		$result = $this->XCache->read($keys);
		$this->assertEqual($expected, $result);

		$result = $this->XCache->delete($keys);
		$this->assertTrue($result);
	}

	public function testClear() {
		$admin = (ini_get('xcache.admin.enable_auth') === "On");
		$this->skipIf($admin, "XCache::clear() test skipped due to authentication.");

		$key1 = 'key_clear_1';
		$key2 = 'key_clear_2';
		$time = strtotime('+1 minute');

		$result = xcache_set($key1, 'data that will no longer exist', $time);
		$this->assertTrue($result);

		$result = xcache_set($key2, 'more dead data', $time);
		$this->assertTrue($result);

		$result = $this->XCache->clear();
		$this->assertTrue($result);

		$this->assertNull(xcache_get($key1));
		$this->assertNull(xcache_get($key2));
	}

	public function testDecrement() {
		$time = strtotime('+1 minute') - time();
		$key = 'decrement';
		$value = 10;

		$result = xcache_set($key, $value, $time);
		$this->assertTrue($result);

		$result = $this->XCache->decrement($key);
		$this->assertEqual($value - 1, $result);

		$result = xcache_get($key);
		$this->assertEqual($value - 1, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testDecrementNonIntegerValue() {
		$time = strtotime('+1 minute') - time();
		$key = 'non_integer';
		$value = 'no';

		$result = xcache_set($key, $value, $time);
		$this->assertTrue($result);

		$this->XCache->decrement($key);

		$result = xcache_get($key);
		$this->assertEqual(-1, $result);

		$this->XCache->decrement($key);

		$result = xcache_get($key);
		$this->assertEqual(-2, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testDecrementWithScope() {
		$adapter = new XCache(['scope' => 'primary']);

		xcache_set('primary:key1', 1, 60);
		xcache_set('key1', 1, 60);

		$adapter->decrement('key1');

		$expected = 1;
		$result = xcache_get('key1');
		$this->assertEqual($expected, $result);

		$expected = 0;
		$result = xcache_get('primary:key1');
		$this->assertEqual($expected, $result);
	}

	public function testIncrement() {
		$time = strtotime('+1 minute') - time();
		$key = 'increment';
		$value = 10;

		$result = xcache_set($key, $value, $time);
		$this->assertTrue($result);

		$result = $this->XCache->increment($key);
		$this->assertEqual($value + 1, $result);

		$result = xcache_get($key);
		$this->assertEqual($value + 1, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testIncrementNonIntegerValue() {
		$time = strtotime('+1 minute');
		$key = 'non_integer_increment';
		$value = 'yes';

		$result = xcache_set($key, $value, $time);
		$this->assertTrue($result);

		$this->XCache->increment($key);

		$result = xcache_get($key);
		$this->assertEqual(1, $result);

		$this->XCache->increment($key);

		$result = xcache_get($key);
		$this->assertEqual(2, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testIncrementWithScope() {
		$adapter = new XCache(['scope' => 'primary']);

		xcache_set('primary:key1', 1, 60);
		xcache_set('key1', 1, 60);

		$adapter->increment('key1');

		$expected = 1;
		$result = xcache_get('key1');
		$this->assertEqual($expected, $result);

		$expected = 2;
		$result = xcache_get('primary:key1');
		$this->assertEqual($expected, $result);
	}
}

?>