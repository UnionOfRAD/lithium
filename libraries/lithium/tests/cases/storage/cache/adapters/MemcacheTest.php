<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapters;

use \lithium\storage\cache\adapters\Memcache;

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

	}

	public function setUp() {
		$this->server = array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 100);


		$this->_Memcached = new \Memcached();
		$this->_Memcached->addServer($this->server['host'], $this->server['port'], $this->server['weight']);

		$this->Memcache = new Memcache();
	}

	public function tearDown() {
		$this->_Memcached->flush();
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

		$result = $this->_Memcached->get($key . '_expires');
		$this->assertEqual($time, $result);

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

		$result = $this->_Memcached->get($key . '_expires');
		$this->assertEqual($time, $result);

		$result = $this->_Memcached->delete($key);
		$this->assertTrue($result);

		$result = $this->_Memcached->delete($key . '_expires');
		$this->assertTrue($result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = $this->_Memcached->set($key . '_expires', $time, $time);
		$this->assertTrue($result);

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

		$result = $this->_Memcached->delete($key . '_expires');
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = $this->_Memcached->set($key, $data, $time);
		$this->assertTrue($result);

		$result = $this->_Memcached->set($key . '_expires', $time, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->_Memcached->delete($key);
		$this->assertTrue($result);

		$result = $this->_Memcached->delete($key . '_expires');
		$this->assertTrue($result);
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

		$result = $this->_Memcached->set($key . '_expires', $time, $time);
		$this->assertTrue($result);

		$closure = $this->Memcache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$this->assertTrue($result);

		$this->assertFalse($this->_Memcached->delete($key));
		$this->assertFalse($this->_Memcached->delete($key . '_expires'));
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

		$result = $this->_Memcached->get($key . '_expires');
		$this->assertEqual($time, $result);

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
		$this->assertFalse($this->_Memcached->get($key . '_expires'));
	}

	public function testExpiredRead() {
		$key = 'expiring_read_key';
		$data = 'expired data';
		$time = strtotime('+1 second');

		$result = $this->_Memcached->set($key . '_expires', $time, $time);
		$this->assertTrue($result);

		$result = $this->_Memcached->set($key, $data, $time);
		$this->assertTrue($result);

		sleep(2);
		$closure = $this->Memcache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memcache, $params, null);
		$this->assertFalse($result);
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
}

?>