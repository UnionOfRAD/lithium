<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter;

use \lithium\storage\cache\adapter\Redis;

class RedisTest extends \lithium\test\Unit {

	/**
	 * Skip the test if the Redis extension is unavailable.
	 *
	 * @return void
	 */
	public function skip() {
		$extensionExists = extension_loaded('redis');
		$message = 'The redis extension is not installed.';
		$this->skipIf(!$extensionExists, $message);

		$R = new \Redis();
		$result = null;
		try {
			$R->connect('127.0.0.1', 6379);
		} catch (\Exception $e) {
			$message = 'redis-server does not appear to be running on 127.0.0.1:6379';
			$result = $R->info();
			$this->skipIf(empty($result), $message);
		}
		unset($R);
	}

	public function setUp() {
		$this->server = array('host' => '127.0.0.1', 'port' => 6379);
		$this->_Redis = new \Redis();
		$this->_Redis->connect($this->server['host'], $this->server['port']);
		$this->Redis = new Redis();
	}

	public function tearDown() {
		$this->_Redis->flushdb();
	}

	public function testEnabled() {
		$redis = $this->Redis;
		$this->assertTrue($redis::enabled());
	}

	public function testInit() {
		$Redis = new Redis();
		$this->assertTrue($Redis::$connection instanceof \Redis);
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->Redis->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Redis, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->ttl($key);
		$this->assertEqual($time - time(), $result);

		$result = $this->_Redis->delete($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$expiry = '+1 minute';
		$time = strtotime($expiry);

		$closure = $this->Redis->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Redis, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->ttl($key);
		$this->assertEqual($time - time(), $result);

		$result = $this->_Redis->delete($key);
		$this->assertTrue($result);
	}

	public function testWriteDefaultCacheExpiry() {
		$Redis = new Redis(array('expiry' => '+5 seconds'));
		$key = 'default_key';
		$data = 'value';
		$time = strtotime('+5 seconds');

		$closure = $Redis->write($key, $data);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data');
		$result = $closure($Redis, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->ttl($key);
		$this->assertEqual($time - time(), $result);

		$result = $this->_Redis->delete($key);
		$this->assertTrue($result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';

		$result = $this->_Redis->set($key, $data);
		$this->assertTrue($result);

		$closure = $this->Redis->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->delete($key);
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = $this->_Redis->set($key, $data);
		$this->assertTrue($result);

		$result = $this->_Redis->ttl($key);
		$this->assertTrue($result);

		$closure = $this->Redis->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->delete($key);
		$this->assertTrue($result);
	}

	public function testMultiRead() {
		$data = array('key1' => 'value1', 'key2' => 'value2');
		$result = $this->_Redis->mset($data);
		$this->assertTrue($result);

		$closure = $this->Redis->read(array_keys($data));
		$this->assertTrue(is_callable($closure));

		$params = array('key' => array_keys($data));
		$result = $closure($this->Redis, $params, null);
		$expected = array_values($data);
		$this->assertEqual($expected, $result);

		foreach ($data as $k => $v) {
			$result = $this->_Redis->delete($k);
			$this->assertTrue($result);
		}
	}

	public function testMultiWrite() {
		$key = array('key1' => 'value1', 'key2' => 'value2');
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->Redis->write($key, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = array('key' => $key, 'data' => $expiry, 'expiry' => null);
		$result = $closure($this->Redis, $params, null);
		$expected = array('key1' => true, 'key2' => true);
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->getMultiple(array_keys($key));
		$expected = array_values($key);
		$this->assertEqual($expected, $result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$closure = $this->Redis->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$this->assertFalse($result);

	}

	public function testDelete() {
		$key = 'delete_key';
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$result = $this->_Redis->set($key, $data);
		$this->assertTrue($result);

		$closure = $this->Redis->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$this->assertTrue($result);

		$this->assertFalse($this->_Redis->delete($key));
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$closure = $this->Redis->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->Redis->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Redis, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Redis->get($key);
		$this->assertEqual($expected, $result);

		$closure = $this->Redis->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$closure = $this->Redis->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$this->assertTrue($result);

		$this->assertFalse($this->_Redis->get($key));
	}

	public function testClear() {
		$result = $this->_Redis->set('key', 'value');
		$this->assertTrue($result);

		$result = $this->_Redis->set('another_key', 'value');
		$this->assertTrue($result);

		$result = $this->Redis->clear();
		$this->assertTrue($result);

		$this->assertFalse($this->_Redis->get('key'));
		$this->assertFalse($this->_Redis->get('another_key'));
	}

	public function testDecrement() {
		$key = 'decrement';
		$value = 10;

		$result = $this->_Redis->set($key, $value);
		$this->assertTrue($result);

		$closure = $this->Redis->decrement($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$this->assertEqual($value - 1, $result);

		$result = $this->_Redis->get($key);
		$this->assertEqual($value - 1, $result);

		$result = $this->_Redis->delete($key);
		$this->assertTrue($result);
	}

	public function testDecrementNonIntegerValue() {
		$key = 'non_integer';
		$value = 'no';

		$result = $this->_Redis->set($key, $value);
		$this->assertTrue($result);

		$closure = $this->Redis->decrement($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$this->assertFalse($result);

		$result = $this->_Redis->get($key);
		$this->assertEqual($value, $result);

		$result = $this->_Redis->delete($key);
		$this->assertTrue($result);
	}

	public function testIncrement() {
		$key = 'increment';
		$value = 10;

		$result = $this->_Redis->set($key, $value);
		$this->assertTrue($result);

		$closure = $this->Redis->increment($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$this->assertEqual($value + 1, $result);

		$result = $this->_Redis->get($key);
		$this->assertEqual($value + 1, $result);

		$result = $this->_Redis->delete($key);
		$this->assertTrue($result);
	}

	public function testIncrementNonIntegerValue() {
		$key = 'non_integer_increment';
		$value = 'yes';

		$result = $this->_Redis->set($key, $value);
		$this->assertTrue($result);

		$closure = $this->Redis->increment($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Redis, $params, null);
		$this->assertFalse($result);

		$result = $this->_Redis->get($key);
		$this->assertEqual($value, $result);

		$result = $this->_Redis->delete($key);
		$this->assertTrue($result);
	}
}

?>