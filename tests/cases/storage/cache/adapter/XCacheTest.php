<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter;

use lithium\storage\Cache;
use lithium\storage\cache\adapter\XCache;

class XCacheTest extends \lithium\test\Unit {

	/**
	 * Skip the test if XCache extension is unavailable.
	 *
	 * @return void
	 */
	public function skip() {
		$extensionExists = (extension_loaded('xcache') && (ini_get('xcache.var_size') !== 0));
		$message = 'The XCache extension is not installed or not configured for userspace caching.';
		$this->skipIf(!$extensionExists, $message);
	}

	/**
	 * Clear the userspace cache
	 *
	 * @return void
	 */
	public function setUp() {
		for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
			if (xcache_clear_cache(XC_TYPE_VAR, $i) === false) {
				return false;
			}
		}
		$this->XCache = new XCache();
	}

	public function tearDown() {
		unset($this->XCache);
	}

	public function testEnabled() {
		$xcache = $this->XCache;
		$this->assertTrue($xcache::enabled());
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$keys = array($key => $data);
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->XCache->write($keys, $expiry);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys', 'expiry');
		$result = $closure($this->XCache, $params);
		$this->assertTrue($result);

		$expected = $data;
		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$keys = array($key => $data);
		$expiry = '+1 minute';
		$time = strtotime($expiry);

		$closure = $this->XCache->write($keys, $expiry);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys', 'expiry');
		$result = $closure($this->XCache, $params);
		$this->assertTrue($result);

		$expected = $data;
		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testWriteMulti() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$closure = $this->XCache->write($keys, $expiry);
		$result = $closure($this->XCache, compact('keys', 'expiry'));
		$this->assertTrue($result);

		foreach ($keys as $key => $data) {
			$expected = $data;
			$result = xcache_get($key);
			$this->assertEqual($expected, $result);

			xcache_unset($key);
		}
	}

	public function testWriteExpiryDefault() {
		$xCache = new XCache(array('expiry' => '+5 seconds'));
		$key = 'default_key';
		$data = 'value';
		$keys = array($key => $data);
		$time = strtotime('+5 seconds');

		$closure = $xCache->write($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($xCache, $params);
		$this->assertTrue($result);

		$expected = $data;
		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testWriteNoExpiry() {
		$keys = array('key1' => 'data1');

		$adapter = new XCache(array('expiry' => null));
		$expiry = null;

		$closure = $adapter->write($keys, $expiry);
		$result = $closure($adapter, compact('keys', 'expiry'));
		$this->assertTrue($result);

		$result = xcache_isset('key1');
		$this->assertTrue($result);

		xcache_unset('key1');

		$adapter = new XCache(array('expiry' => Cache::PERSIST));
		$expiry = Cache::PERSIST;

		$closure = $adapter->write($keys, $expiry);
		$result = $closure($adapter, compact('keys', 'expiry'));
		$this->assertTrue($result);

		$result = xcache_isset('key1');
		$this->assertTrue($result);

		xcache_unset('key1');

		$adapter = new XCache();
		$expiry = Cache::PERSIST;

		$closure = $adapter->write($keys, $expiry);
		$result = $closure($adapter, compact('keys', 'expiry'));
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
		$keys = array('key1' => 'data1');
		$expiry = '+5 seconds';
		$closure = $this->XCache->write($keys, $expiry);
		$closure($this->XCache, compact('keys', 'expiry'));

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
		$keys = array('key1' => 'data1');
		$expiry = 5;
		$closure = $this->XCache->write($keys, $expiry);
		$closure($this->XCache, compact('keys', 'expiry'));

		$result = xcache_isset('key1');
		$this->assertTrue($result);

		xcache_unset('key1');

		$keys = array('key1' => 'data1');
		$expiry = 1;
		$closure = $this->XCache->write($keys, $expiry);
		$closure($this->XCache, compact('keys', 'expiry'));
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$keys = array($key);
		$time = strtotime('+1 minute');

		$result = xcache_set($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->XCache->read($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($this->XCache, $params);
		$expected = array($key => $data);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$keys = array($key);
		$time = strtotime('+1 minute');

		$result = xcache_set($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->XCache->read($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($this->XCache, $params);
		$expected = array($key => $data);
		$this->assertEqual($expected, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$keys = array($key);
		$closure = $this->XCache->read($keys);

		$expected = array();
		$result = $closure($this->XCache, compact('keys'));
		$this->assertIdentical($expected, $result);
	}

	public function testReadMulti() {
		$keys = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		foreach ($keys as $key => $data) {
			xcache_set($key, $data, 60);
		}

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
		$closure = $this->XCache->read($keys);
		$result = $closure($this->XCache, compact('keys'));
		$this->assertEqual($expected, $result);

		foreach ($keys as $key) {
			xcache_unset($key);
		}
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => null
		);
		$result = $this->XCache->write($keys);
		$this->assertTrue($result($this->XCache, compact('keys', 'expiry')));

		$expected = $keys;
		$result = $this->XCache->read(array_keys($keys));
		$this->assertEqual($expected, $result($this->XCache, array('keys' => array_keys($keys))));
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = array(
			'key1' => null,
			'key2' => 'data2'
		);
		$result = $this->XCache->write($keys);
		$this->assertTrue($result($this->XCache, compact('keys', 'expiry')));

		$expected = $keys;
		$result = $this->XCache->read(array_keys($keys));
		$this->assertEqual($expected, $result($this->XCache, array('keys' => array_keys($keys))));

		$keys = array(
			'key1' => null,
			'key2' => null
		);
		$result = $this->XCache->write($keys);
		$this->assertTrue($result($this->XCache, compact('keys', 'expiry')));
	}

	public function testDelete() {
		$key = 'delete_key';
		$keys = array($key);
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$result = xcache_set($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->XCache->delete($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($this->XCache, $params);
		$this->assertTrue($result);
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$data = 'data to delete';
		$keys = array($key);
		$time = strtotime('+1 minute');

		$closure = $this->XCache->delete($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($this->XCache, $params);
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$keys = array($key => $data);
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->XCache->write($keys, $expiry);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys', 'expiry');
		$result = $closure($this->XCache, $params);
		$this->assertTrue($result);

		$expected = $data;
		$result = xcache_get($key);
		$this->assertEqual($expected, $result);

		$keys = array($key);

		$closure = $this->XCache->read($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($this->XCache, $params, null);
		$expected = array($key => $data);
		$this->assertEqual($expected, $result);

		$closure = $this->XCache->delete($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($this->XCache, $params);
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

		$closure = $this->XCache->decrement($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
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

		$closure = $this->XCache->decrement($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);

		$result = xcache_get($key);
		$this->assertEqual(-1, $result);

		$closure = $this->XCache->decrement($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);

		$result = xcache_get($key);
		$this->assertEqual(-2, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}

	public function testIncrement() {
		$time = strtotime('+1 minute') - time();
		$key = 'increment';
		$value = 10;

		$result = xcache_set($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->XCache->increment($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);
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

		$closure = $this->XCache->increment($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);

		$result = xcache_get($key);
		$this->assertEqual(1, $result);

		$closure = $this->XCache->increment($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($this->XCache, $params, null);

		$result = xcache_get($key);
		$this->assertEqual(2, $result);

		$result = xcache_unset($key);
		$this->assertTrue($result);
	}
}

?>