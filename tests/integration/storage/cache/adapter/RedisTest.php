<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\storage\cache\adapter;

use Exception;
use Redis as RedisCore;
use lithium\storage\Cache;
use lithium\storage\cache\adapter\Redis;

class RedisTest extends \lithium\test\Integration {

	public $redis;

	protected $_redis;

	public function __construct(array $config = []) {
		$defaults = [
			'host' => '127.0.0.1',
			'port' => 6379
		];
		parent::__construct($config + $defaults);
	}

	/**
	 * Skip the test if the Redis extension is unavailable.
	 *
	 * @link https://github.com/facebook/hhvm/commit/34de1a279b3b66458ff980d12ef347022f2def04
	 */
	public function skip() {
		$this->skipIf(!Redis::enabled(), 'The redis extension is not installed.');
		$this->skipIf(
			defined('HHVM_VERSION') && version_compare(HHVM_VERSION, '3.10', '<'),
			'Cannot reliably run redis test on HHVM < 3.110 due to HHVM bug.'
		);

		$redis = new RedisCore();
		$cfg = $this->_config;

		try {
			$result = $redis->connect($cfg['host'], $cfg['port']);
		} catch (Exception $e) {
			$result = false;
		}
		$msg = "redis-server does not appear to be running.";
		$this->skipIf(!$result, $msg);
		unset($redis);
	}

	public function setUp() {
		$this->_redis = new RedisCore();
		$this->_redis->connect($this->_config['host'], $this->_config['port']);
		$this->redis = new Redis();
	}

	public function tearDown() {
		$this->_redis->flushdb();
	}

	public function testEnabled() {
		$redis = $this->redis;
		$this->assertTrue($redis::enabled());
	}

	public function testInit() {
		$redis = new Redis();
		$this->assertTrue($redis->connection instanceof RedisCore);
	}

	public function testHostPort() {
		$this->assertNotException('RedisException', function() {
			$redis = new Redis();
			$redis->info();
		});

		$this->assertNotException('RedisException', function() {
			$redis = new Redis(['host' => '127.0.0.1']);
			$redis->info();
		});
	}

	public function testHostSocket() {
		$socketExists = file_exists('/tmp/redis.sock');
		$this->skipIf(!$socketExists, 'Redis is not listening on socket');
		$this->assertNotException('RedisException', function() {
			$redis = new Redis(['host' => '/tmp/redis.sock']);
			$redis->info();
		});
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$keys = [$key => $data];
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$expected = $keys;
		$result = $this->redis->write($keys, $expiry);
		$this->assertEqual($expected, $result);

		$expected = $data;
		$result = $this->_redis->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_redis->ttl($key);
		$this->assertTrue($result == 5 || $result == 4);

		$result = $this->_redis->delete($key);
		$this->assertEqual(1, $result);

		$key = 'another_key';
		$data = 'more_data';
		$keys = [$key => $data];
		$expiry = '+1 minute';
		$time = strtotime($expiry);

		$expected = $keys;
		$result = $this->redis->write($keys, $expiry);
		$this->assertEqual($expected, $result);

		$expected = $data;
		$result = $this->_redis->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_redis->ttl($key);
		$this->assertTrue($result == 60 || $result == 59);

		$result = $this->_redis->delete($key);
		$this->assertEqual(1, $result);
	}

	public function testWriteExpiryDefault() {
		$redis = new Redis(['expiry' => '+5 seconds']);
		$key = 'default_key';
		$data = 'value';
		$keys = [$key => $data];
		$time = strtotime('+5 seconds');

		$expected = $data;
		$result = $redis->write($keys);
		$this->assertEqual($expected, $result);

		$result = $this->_redis->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_redis->ttl($key);
		$this->assertTrue($result == 5 || $result == 4);

		$result = $this->_redis->delete($key);
		$this->assertEqual(1, $result);
	}

	public function testWriteNoExpiry() {
		$key = 'default_key';
		$data = 'value';
		$keys = [$key => $data];

		$redis = new Redis(['expiry' => null]);
		$expiry = null;

		$result = $redis->write($keys, $expiry);
		$this->assertTrue($result);

		$result = $this->_redis->exists($key);
		$this->assertTrue($result);

		$expected = -1;
		$result = $this->_redis->ttl($key);
		$this->assertEqual($expected, $result);

		$this->_redis->delete($key);

		$redis = new Redis(['expiry' => Cache::PERSIST]);
		$expiry = Cache::PERSIST;

		$result = $redis->write($keys, $expiry);
		$this->assertTrue($result);

		$result = $this->_redis->exists($key);
		$this->assertTrue($result);

		$expected = -1;
		$result = $this->_redis->ttl($key);
		$this->assertEqual($expected, $result);

		$this->_redis->delete($key);

		$redis = new Redis();
		$expiry = Cache::PERSIST;

		$result = $redis->write($keys, $expiry);
		$this->assertTrue($result);

		$result = $this->_redis->exists($key);
		$this->assertTrue($result);

		$expected = -1;
		$result = $this->_redis->ttl($key);
		$this->assertEqual($expected, $result);

		$this->_redis->delete($key);
	}

	public function testWriteExpiryExpires() {
		$keys = ['key1' => 'data1'];
		$expiry = '+5 seconds';
		$this->redis->write($keys, $expiry);

		$result = $this->_redis->exists('key1');
		$this->assertTrue($result);

		$this->_redis->delete('key1');

		$keys = ['key1' => 'data1'];
		$expiry = '+1 second';
		$this->redis->write($keys, $expiry);

		sleep(2);

		$result = $this->_redis->exists('key1');
		$this->assertFalse($result);
	}

	public function testWriteExpiryTtl() {
		$keys = ['key1' => 'data1'];
		$expiry = 5;
		$this->redis->write($keys, $expiry);

		$result = $this->_redis->exists('key1');
		$this->assertTrue($result);

		$this->_redis->delete('key1');

		$keys = ['key1' => 'data1'];
		$expiry = 1;
		$this->redis->write($keys, $expiry);

		sleep(2);

		$result = $this->_redis->exists('key1');
		$this->assertFalse($result);
	}

	public function testWriteWithScope() {
		$adapter = new Redis(['scope' => 'primary']);

		$keys = ['key1' => 'test1'];
		$expiry = '+1 minute';
		$adapter->write($keys, $expiry);

		$expected = 'test1';
		$result = $this->_redis->get('primary:key1');
		$this->assertEqual($expected, $result);

		$result = $this->_redis->get('key1');
		$this->assertFalse($result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$keys = [$key];

		$result = $this->_redis->set($key, $data);
		$this->assertTrue($result);

		$expected = [$key => $data];
		$result = $this->redis->read($keys);
		$this->assertEqual($expected, $result);

		$result = $this->_redis->delete($key);
		$this->assertEqual(1, $result);

		$key = 'another_read_key';
		$data = 'read data';
		$keys = [$key];
		$time = strtotime('+1 minute');
		$expiry = $time - time();

		$result = $this->_redis->set($key, $data, $expiry);
		$this->assertTrue($result);

		$result = $this->_redis->ttl($key);
		$this->assertTrue($result == $expiry || $result == $expiry - 1);


		$expected = [$key => $data];
		$result = $this->redis->read($keys);
		$this->assertEqual($expected, $result);

		$result = $this->_redis->delete($key);
		$this->assertEqual(1, $result);
	}

	public function testMultiRead() {
		$data = ['key1' => 'value1', 'key2' => 'value2'];
		$result = $this->_redis->mset($data);
		$this->assertTrue($result);

		$expected = $data;
		$result = $this->redis->read(array_keys($data));
		$this->assertEqual($expected, $result);

		foreach ($data as $k => $v) {
			$result = $this->_redis->delete($k);
			$this->assertEqual(1, $result);
		}
	}

	public function testMultiWrite() {
		$keys = ['key1' => 'value1', 'key2' => 'value2'];
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$result = $this->redis->write($keys, $expiry);
		$this->assertTrue($result);

		$result = $this->_redis->getMultiple(array_keys($keys));
		$expected = array_values($keys);
		$this->assertEqual($expected, $result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$keys = [$key];

		$expected = [];
		$result = $this->redis->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null
		];
		$result = $this->redis->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->redis->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null,
			'key2' => 'data2'
		];
		$result = $this->redis->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->redis->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$keys = [
			'key1' => '',
			'key2' => 'data2'
		];
		$result = $this->redis->write($keys);
		$this->assertTrue($result);
	}

	public function testReadWithScope() {
		$adapter = new Redis(['scope' => 'primary']);

		$this->_redis->set('primary:key1', 'test1', 60);
		$this->_redis->set('key1', 'test2', 60);

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

		$result = $this->_redis->set($key, $data);
		$this->assertTrue($result);

		$result = $this->redis->delete($keys);
		$this->assertTrue($result);

		$this->assertEqual(0, $this->_redis->delete($key));
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$keys = [$key];

		$result = $this->redis->delete($keys);
		$this->assertFalse($result);
	}

	public function testDeleteWithScope() {
		$adapter = new Redis(['scope' => 'primary']);

		$this->_redis->set('primary:key1', 'test1', 60);
		$this->_redis->set('key1', 'test2', 60);

		$keys = ['key1'];
		$expected = ['key1' => 'test1'];
		$adapter->delete($keys);

		$result = (boolean) $this->_redis->get('key1');
		$this->assertTrue($result);

		$result = $this->_redis->get('primary:key1');
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$keys = [$key => $data];
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$expected = $keys;
		$result = $this->redis->write($keys, $expiry);
		$this->assertEqual($expected, $result);

		$expected = $data;
		$result = $this->_redis->get($key);
		$this->assertEqual($expected, $result);

		$expected = $keys;
		$result = $this->redis->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$result = $this->redis->delete(array_keys($keys));
		$this->assertTrue($result);

		$this->assertFalse($this->_redis->get($key));
	}

	public function testClear() {
		$result = $this->_redis->set('key', 'value');
		$this->assertTrue($result);

		$result = $this->_redis->set('another_key', 'value');
		$this->assertTrue($result);

		$result = $this->redis->clear();
		$this->assertTrue($result);

		$this->assertFalse($this->_redis->get('key'));
		$this->assertFalse($this->_redis->get('another_key'));
	}

	public function testDecrement() {
		$key = 'decrement';
		$value = 10;

		$result = $this->_redis->set($key, $value);
		$this->assertTrue($result);

		$result = $this->redis->decrement($key);
		$this->assertEqual($value - 1, $result);

		$result = $this->_redis->get($key);
		$this->assertEqual($value - 1, $result);

		$result = $this->_redis->delete($key);
		$this->assertEqual(1, $result);
	}

	public function testDecrementNonIntegerValue() {
		$key = 'non_integer';
		$value = 'no';

		$result = $this->_redis->set($key, $value);
		$this->assertTrue($result);

		$result = $this->redis->decrement($key);
		$this->assertFalse($result);

		$result = $this->_redis->get($key);
		$this->assertEqual($value, $result);

		$result = $this->_redis->delete($key);
		$this->assertEqual(1, $result);
	}

	public function testDecrementWithScope() {
		$adapter = new Redis(['scope' => 'primary']);

		$this->_redis->set('primary:key1', 1, 60);
		$this->_redis->set('key1', 1, 60);

		$adapter->decrement('key1');

		$expected = 1;
		$result = $this->_redis->get('key1');
		$this->assertEqual($expected, $result);

		$expected = 0;
		$result = $this->_redis->get('primary:key1');
		$this->assertEqual($expected, $result);
	}

	public function testIncrement() {
		$key = 'increment';
		$value = 10;

		$result = $this->_redis->set($key, $value);
		$this->assertTrue($result);

		$result = $this->redis->increment($key);
		$this->assertEqual($value + 1, $result);

		$result = $this->_redis->get($key);
		$this->assertEqual($value + 1, $result);

		$result = $this->_redis->delete($key);
		$this->assertEqual(1, $result);
	}

	public function testIncrementNonIntegerValue() {
		$key = 'non_integer_increment';
		$value = 'yes';

		$result = $this->_redis->set($key, $value);
		$this->assertTrue($result);

		$result = $this->redis->increment($key);
		$this->assertFalse($result);

		$result = $this->_redis->get($key);
		$this->assertEqual($value, $result);

		$result = $this->_redis->delete($key);
		$this->assertEqual(1, $result);
	}

	public function testIncrementWithScope() {
		$adapter = new Redis(['scope' => 'primary']);

		$this->_redis->set('primary:key1', 1, 60);
		$this->_redis->set('key1', 1, 60);

		$adapter->increment('key1');

		$expected = 1;
		$result = $this->_redis->get('key1');
		$this->assertEqual($expected, $result);

		$expected = 2;
		$result = $this->_redis->get('primary:key1');
		$this->assertEqual($expected, $result);
	}

	public function testMethodDispatch() {
		$this->_redis->flushdb();
		$this->_redis->set('some_key', 'somevalue');

		$result = $this->redis->keys('*');
		$this->assertEqual($result, ['some_key'], 'redis method dispatch failed');

		$result = $this->redis->info();
		$this->assertInternalType('array', $result, 'redis method dispatch failed');
	}

	public function testRespondsTo() {
		$this->assertTrue($this->redis->respondsTo('bgsave'));
		$this->assertTrue($this->redis->respondsTo('dbSize'));
		$this->assertFalse($this->redis->respondsTo('foobarbaz'));
	}

	public function testRespondsToParentCall() {
		$this->assertTrue($this->redis->respondsTo('applyFilter'));
		$this->assertFalse($this->redis->respondsTo('fooBarBaz'));
	}
}

?>