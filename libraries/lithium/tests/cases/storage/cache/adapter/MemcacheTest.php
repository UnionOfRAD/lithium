<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter;

use \lithium\storage\cache\adapter\Memcache;

class MemcacheTest extends \lithium\test\Unit {

	/**
	 * Skip the test if Memcached extension is unavailable.
	 *
	 * @return void
	 */
	public function skip() {
		$extensionExists = extension_loaded('memcached');
		$message = 'The libmemcached extension is not installed.';
		$this->skipIf(!$extensionExists, $message);

		$M = new \Memcached();
		$M->addServer('127.0.0.1', 11211);
		$message = 'The memcached daemon does not appear to be running on 127.0.0.1:11211';
		$result = $M->getVersion();
		$this->skipIf(empty($result), $message);
		unset($M);
	}

	public function setUp() {
		$this->server = array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 100);
		$this->_Memcached = new \Memcached();
		$this->_Memcached->addServer(
			$this->server['host'], $this->server['port'], $this->server['weight']
		);
		$this->Memcache = new Memcache();
	}

	public function tearDown() {
		$this->_Memcached->flush();
	}

	public function testEnabled() {
		$memcache = $this->Memcache;
		$this->assertTrue($memcache::enabled());
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->Memcache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Memcache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->delete($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$expiry = '+1 minute';
		$time = strtotime($expiry);

		$closure = $this->Memcache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Memcache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->delete($key);
		$this->assertTrue($result);
	}

	public function testWriteDefaultCacheExpiry() {
		$Memcache = new Memcache(array('expiry' => '+5 seconds'));
		$key = 'default_key';
		$data = 'value';

		$closure = $Memcache->write($key, $data);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data');
		$result = $closure($Memcache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->get($key);
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->delete($key);
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

		$closure = $this->Memcache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Memcache, $params, null);
		$this->assertTrue($result);

		$result = $this->_Memcached->getMulti(array_keys($key));
		$expected = $key;
		$this->assertEqual($expected, $result);

		foreach ($key as $name => &$value) {
			$result = $this->_Memcached->delete($name);
			$this->assertTrue($result);
		}
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = $this->_Memcached->set($key, $data, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->delete($key);
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = $this->_Memcached->set($key, $data, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->delete($key);
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

		$result = $this->_Memcached->setMulti($key, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->read(array_keys($key));
		$this->assertTrue(is_callable($closure));

		$params = array('key' => array_keys($key));
		$result = $closure($this->Memcache, $params, null);
		$expected = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$this->assertEqual($expected, $result);

		foreach ($key as $name => &$value) {
			$result = $this->_Memcached->delete($name);
			$this->assertTrue($result);
		}
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$closure = $this->Memcache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$this->assertFalse($result);

	}

	public function testDelete() {
		$key = 'delete_key';
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$result = $this->_Memcached->set($key, $data, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$this->assertTrue($result);
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$closure = $this->Memcache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->Memcache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Memcache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->get($key);
		$this->assertEqual($expected, $result);

		$closure = $this->Memcache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$closure = $this->Memcache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$this->assertTrue($result);

		$this->assertFalse($this->_Memcached->get($key));
	}

	public function testClear() {
		$time = strtotime('+1 minute');

		$result = $this->_Memcached->set('key', 'value', $time);
		$this->assertTrue($result);

		$result = $this->_Memcached->set('another_key', 'value', $time);
		$this->assertTrue($result);

		$result = $this->Memcache->clear();
		$this->assertTrue($result);

		$this->assertFalse($this->_Memcached->get('key'));
		$this->assertFalse($this->_Memcached->get('another_key'));
	}

	public function testDecrement() {
		$time = strtotime('+1 minute');
		$key = 'decrement';
		$value = 10;

		$result = $this->_Memcached->set($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->decrement($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$this->assertEqual($value - 1, $result);

		$result = $this->_Memcached->get($key);
		$this->assertEqual($value - 1, $result);

		$result = $this->_Memcached->delete($key);
		$this->assertTrue($result);
	}

	public function testDecrementNonIntegerValue() {
		$time = strtotime('+1 minute');
		$key = 'non_integer';
		$value = 'no';

		$result = $this->_Memcached->set($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->decrement($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);

		$result = $this->_Memcached->get($key);
		$this->assertEqual(0, $result);

		$result = $this->_Memcached->delete($key);
		$this->assertTrue($result);
	}

	public function testIncrement() {
		$time = strtotime('+1 minute');
		$key = 'increment';
		$value = 10;

		$result = $this->_Memcached->set($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->increment($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$this->assertEqual($value + 1, $result);

		$result = $this->_Memcached->get($key);
		$this->assertEqual($value + 1, $result);

		$result = $this->_Memcached->delete($key);
		$this->assertTrue($result);
	}

	public function testIncrementNonIntegerValue() {
		$time = strtotime('+1 minute');
		$key = 'non_integer_increment';
		$value = 'yes';

		$result = $this->_Memcached->set($key, $value, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->increment($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);

		$result = $this->_Memcached->get($key);
		$this->assertEqual(0, $result);

		$result = $this->_Memcached->delete($key);
		$this->assertTrue($result);
	}
}

?>