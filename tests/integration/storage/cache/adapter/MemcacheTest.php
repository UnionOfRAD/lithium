<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\storage\cache\adapter;

use Memcached;
use lithium\storage\Cache;
use lithium\storage\cache\adapter\Memcache;

class MemcacheTest extends \lithium\test\Integration {

	public $server = null;

	public $memcache = null;

	protected $_conn = null;

	/**
	 * Skip the test if the adapter is enabled. If it is not it means the
	 * libmemcached extension is unavailable. Also checks for a running
	 * Memcached server.
	 */
	public function skip() {
		$this->skipIf(!Memcache::enabled(), 'The `Memcache` adapter is not enabled.');

		$conn = new Memcached();
		$conn->addServer('127.0.0.1', 11211);
		$message = 'The memcached daemon does not appear to be running on 127.0.0.1:11211';
		$result = $conn->getVersion();
		$this->skipIf(!$result || current($result) === '255.255.255', $message);
		unset($conn);
	}

	public function setUp() {
		$this->server = ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100];
		$this->_conn = new Memcached();
		$this->_conn->addServer(
			$this->server['host'], $this->server['port'], $this->server['weight']
		);
		$this->memcache = new Memcache();
	}

	public function tearDown() {
		$this->_conn->flush();
	}

	public function testEnabled() {
		$this->assertTrue(Memcache::enabled());
	}

	public function testSanitzeKeys() {
		$result = $this->memcache->key(['posts for bjœrn']);
		$expected = ['posts_for_bjœrn_fdf03955'];
		$this->assertEqual($expected, $result);

		$result = $this->memcache->key(['posts-for-bjoern']);
		$expected = ['posts-for-bjoern'];
		$this->assertEqual($expected, $result);

		$result = $this->memcache->key(['posts for Helgi Þorbjörnsson']);
		$expected = ['posts_for_Helgi_Þorbjörnsson_c7f8433a'];
		$this->assertEqual($expected, $result);

		$result = $this->memcache->key(['libraries.cache']);
		$expected = ['libraries.cache'];
		$this->assertEqual($expected, $result);

		$key = 'post_';
		for ($i = 0; $i <= 127; $i++) {
			$key .= chr($i);
		}
		$result = $this->memcache->key([$key]);
		$expected  = 'post__________________________________!"#$%&\'()*+,-./0123456789:;';
		$expected .= '<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|';
		$expected .= '}~__38676d3e';
		$expected = [$expected];
		$this->assertEqual($expected, $result);

		$key = str_repeat('0', 300);
		$result = $this->memcache->key([$key]);
		$expected = [str_repeat('0', 241) . '_9e1830ed'];
		$this->assertEqual($expected, $result);
		$this->assertTrue(strlen($result[0]) <= 250);

		$adapter = new Memcache(['scope' => 'foo']);

		$key = str_repeat('0', 300);
		$result = $adapter->key([$key]);
		$expected = [str_repeat('0', 241 - strlen('_foo')) . '_9e1830ed'];
		$this->assertEqual($expected, $result);
		$this->assertTrue(strlen($result[0]) <= 250);
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$keys = [$key => $data];
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$result = $this->memcache->write($keys, $expiry);
		$this->assertEqual($keys, $result);
		$this->assertEqual($data, $this->_conn->get($key));

		$result = $this->_conn->delete($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$keys = [$key => $data];
		$expiry = '+1 minute';
		$time = strtotime($expiry);

		$expected = $keys;
		$result = $this->memcache->write($keys, $expiry);
		$this->assertEqual($expected, $result);

		$expected = $data;
		$result = $this->_conn->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_conn->delete($key);
		$this->assertTrue($result);
	}

	public function testWriteExpiryDefault() {
		$memcache = new Memcache(['expiry' => '+5 seconds']);
		$key = 'default_key';
		$data = 'value';
		$keys = [$key => $data];

		$result = $memcache->write($keys);
		$expected = $keys;
		$this->assertEqual($expected, $result);

		$expected = $data;
		$result = $this->_conn->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_conn->delete($key);
		$this->assertTrue($result);
	}

	public function testWriteNoExpiry() {
		$keys = ['key1' => 'data1'];

		$adapter = new Memcache(['expiry' => null]);
		$expiry = null;

		$result = $adapter->write($keys, $expiry);
		$this->assertTrue($result);

		$result = (boolean) $this->_conn->get('key1');
		$this->assertTrue($result);

		$this->_conn->delete('key1');

		$adapter = new Memcache(['expiry' => Cache::PERSIST]);
		$expiry = Cache::PERSIST;

		$result = $adapter->write($keys, $expiry);
		$this->assertTrue($result);

		$result = (boolean) $this->_conn->get('key1');
		$this->assertTrue($result);

		$this->_conn->delete('key1');

		$adapter = new Memcache();
		$expiry = Cache::PERSIST;

		$result = $adapter->write($keys, $expiry);
		$this->assertTrue($result);

		$result = (boolean) $this->_conn->get('key1');
		$this->assertTrue($result);

		$this->_conn->delete('key1');
	}

	public function testWriteWithExpiry() {
		$keys = ['key1' => 'data1'];
		$expiry = '+5 seconds';
		$this->memcache->write($keys, $expiry);

		$result = (boolean) $this->_conn->get('key1');
		$this->assertTrue($result);

		$this->_conn->delete('key1');
	}

	public function testWriteExpiryExpires() {
		$this->memcache->write(['expire0' => 'data0'], '+1 second');
		$this->memcache->write(['expire1' => 'data1'], 1);

		sleep(3);

		$result = $this->_conn->get('expire0');
		$this->assertFalse($result);

		$result = $this->_conn->get('expire1');
		$this->assertFalse($result);

		$this->_conn->delete('expire0');
		$this->_conn->delete('expire1');
	}

	public function testWriteMulti() {
		$expiry = '+1 seconds';
		$keys = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];

		$result = $this->memcache->write($keys, $expiry);
		$this->assertTrue($result);

		$result = $this->_conn->getMulti(array_keys($keys));
		$expected = $keys;
		$this->assertEqual($expected, $result);

		foreach ($keys as $name => &$value) {
			$result = $this->_conn->delete($name);
			$this->assertTrue($result);
		}
	}

	public function testWriteWithScope() {
		$adapter = new Memcache(['scope' => 'primary']);

		$keys = ['key1' => 'test1'];
		$expiry = '+1 minute';
		$adapter->write($keys, $expiry);

		$expected = 'test1';
		$result = $this->_conn->get('primary:key1');
		$this->assertEqual($expected, $result);

		$result = $this->_conn->get('key1');
		$this->assertFalse($result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$keys = [$key];
		$time = strtotime('+1 minute');

		$result = $this->_conn->set($key, $data, $time);
		$this->assertTrue($result);

		$expected = [$key => $data];
		$result = $this->memcache->read($keys);
		$this->assertEqual($expected, $result);

		$result = $this->_conn->delete($key);
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$keys = [$key];
		$time = strtotime('+1 minute');

		$result = $this->_conn->set($key, $data, $time);
		$this->assertTrue($result);

		$expected = [$key => $data];
		$result = $this->memcache->read($keys);
		$this->assertEqual($expected, $result);

		$result = $this->_conn->delete($key);
		$this->assertTrue($result);
	}

	public function testReadMulti() {
		$expiry = '+1 minute';
		$time = strtotime($expiry);
		$keys = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];

		$result = $this->_conn->setMulti($keys, $time);
		$this->assertTrue($result);

		$result = $this->memcache->read(array_keys($keys));
		$expected = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];
		$this->assertEqual($expected, $result);

		foreach ($keys as $name => &$value) {
			$result = $this->_conn->delete($name);
			$this->assertTrue($result);
		}
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$keys = [$key];

		$expected = [];
		$result = $this->memcache->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testReadWithScope() {
		$adapter = new Memcache(['scope' => 'primary']);

		$this->_conn->set('primary:key1', 'test1', 60);
		$this->_conn->set('key1', 'test2', 60);

		$keys = ['key1'];
		$expected = ['key1' => 'test1'];
		$result = $adapter->read($keys);
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$key = 'delete_key';
		$data = 'data to delete';
		$keys = [$key];
		$time = strtotime('+1 minute');
		$this->_conn->set($key, $data, $time);

		$expected = [$key => $data];
		$result = $this->memcache->read($keys);
		$this->assertEqual($expected, $result);

		$result = $this->memcache->delete($keys);
		$this->assertTrue($result);

		$expected = [];
		$result = $this->memcache->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$keys = [$key];

		$params = compact('keys');
		$result = $this->memcache->delete($keys);
		$this->assertFalse($result);
	}

	public function testDeleteWithScope() {
		$adapter = new Memcache(['scope' => 'primary']);

		$this->_conn->set('primary:key1', 'test1', 60);
		$this->_conn->set('key1', 'test2', 60);

		$keys = ['key1'];
		$expected = ['key1' => 'test1'];
		$adapter->delete($keys);

		$result = (boolean) $this->_conn->get('key1');
		$this->assertTrue($result);

		$result = $this->_conn->get('primary:key1');
		$this->assertFalse($result);
	}

	public function testSimpleConnectionSettings() {
		$test = new Memcache(['host' => '127.0.0.1']);
		$hosts = [['host' => '127.0.0.1', 'port' => 11211]];

		$result = $test->connection->getServerList();
		foreach ($result as &$r) {
			if (isset($r['type'])) {
				unset($r['type']);
			}
			if (isset($r['weight'])) {
				unset($r['weight']);
			}
		}
		unset($r);

		$this->assertEqual($hosts, $result);
	}

	public function testMultiServerConnectionSettings() {
		$test = new Memcache(['host' => [
			'127.0.0.1:11222' => 1,
			'127.0.0.2:11223' => 2,
			'127.0.0.3:11224'
		]]);
		$hosts = [
			['host' => '127.0.0.1', 'port' => 11222],
			['host' => '127.0.0.2', 'port' => 11223],
			['host' => '127.0.0.3', 'port' => 11224]
		];

		$result = $test->connection->getServerList();
		foreach ($result as &$r) {
			if (isset($r['type'])) {
				unset($r['type']);
			}
			if (isset($r['weight'])) {
				unset($r['weight']);
			}
		}
		unset($r);

		$this->assertEqual($hosts, $result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$keys = [$key => $data];
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$result = $this->memcache->write($keys, $expiry);
		$this->assertEqual($data, $result);
		$this->assertEqual($data, $this->_conn->get($key));


		$expected = $keys;
		$result = $this->memcache->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$result = $this->memcache->delete(array_keys($keys));
		$this->assertTrue($result);

		$this->assertFalse($this->_conn->get($key));
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null
		];
		$result = $this->memcache->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->memcache->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null,
			'key2' => 'data2'
		];
		$result = $this->memcache->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->memcache->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$keys = [
			'key1' => null,
			'key2' => null
		];
		$result = $this->memcache->write($keys);
		$this->assertTrue($result);
	}

	public function testClear() {
		$time = strtotime('+1 minute');

		$result = $this->_conn->set('key', 'value', $time);
		$this->assertTrue($result);

		$result = $this->_conn->set('another_key', 'value', $time);
		$this->assertTrue($result);

		$result = $this->memcache->clear();
		$this->assertTrue($result);

		$this->assertFalse($this->_conn->get('key'));
		$this->assertFalse($this->_conn->get('another_key'));
	}

	public function testDecrement() {
		$time = strtotime('+1 minute');
		$key = 'decrement';
		$value = 10;

		$result = $this->_conn->set($key, $value, $time);
		$this->assertTrue($result);

		$result = $this->memcache->decrement($key);
		$this->assertEqual($value - 1, $result);

		$result = $this->_conn->get($key);
		$this->assertEqual($value - 1, $result);

		$result = $this->_conn->delete($key);
		$this->assertTrue($result);
	}

	public function testDecrementWithScope() {
		$adapter = new Memcache(['scope' => 'primary']);

		$this->_conn->set('primary:key1', 1, 60);
		$this->_conn->set('key1', 1, 60);

		$adapter->decrement('key1');

		$expected = 1;
		$result = $this->_conn->get('key1');
		$this->assertEqual($expected, $result);

		$expected = 0;
		$result = $this->_conn->get('primary:key1');
		$this->assertEqual($expected, $result);
	}

	public function testIncrement() {
		$time = strtotime('+1 minute');
		$key = 'increment';
		$value = 10;

		$this->assertTrue($this->_conn->set($key, $value, $time));

		$result = $this->memcache->increment($key);
		$this->assertEqual($value + 1, $result);
		$this->assertEqual($value + 1, $this->_conn->get($key));

		$result = $this->_conn->delete($key);
		$this->assertTrue($result);
	}

	public function testIncrementWithScope() {
		$adapter = new Memcache(['scope' => 'primary']);

		$this->_conn->set('primary:key1', 1, 60);
		$this->_conn->set('key1', 1, 60);

		$adapter->increment('key1');

		$expected = 1;
		$result = $this->_conn->get('key1');
		$this->assertEqual($expected, $result);

		$expected = 2;
		$result = $this->_conn->get('primary:key1');
		$this->assertEqual($expected, $result);
	}
}

?>