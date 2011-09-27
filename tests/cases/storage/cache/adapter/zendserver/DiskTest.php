<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter\zendserver;

use lithium\storage\cache\adapter\zendserver\Disk;

class DiskTest extends \lithium\test\Unit {

	/**
	 * Skip the test if Zend Server Data Cache extension is unavailable.
	 *
	 * @return void
	 */
	public function skip() {
        echo "<pre>";
		$extensionExists = extension_loaded('Zend Data Cache');
		$message = 'The Zend Data Cache extension is not installed.';
		$this->skipIf(!$extensionExists, $message);
	}

	public function setUp() {
        zend_disk_cache_clear();
		$this->Cache = new Disk();
	}

	public function tearDown() {
		zend_disk_cache_clear();
		unset($this->Cache);
	}

	public function testEnabled() {
		$cache = $this->Cache;
		$this->assertTrue($cache::enabled());
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$expiry = '+5 seconds';

		$closure = $this->Cache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Cache, $params, null);
		$expected = $data;
		$this->assertTrue($result);

		$result = zend_disk_cache_fetch($key);
		$this->assertEqual($expected, $result);

		$result = zend_disk_cache_delete($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$expiry = '+1 minute';

		$closure = $this->Cache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Cache, $params, null);
		$expected = $data;
		$this->assertTrue($result);

		$result = zend_disk_cache_fetch($key);
		$this->assertEqual($expected, $result);

		$result = zend_disk_cache_delete($key);
		$this->assertTrue($result);
	}

	public function testWriteDefaultCacheTime() {
		$Cache = new Disk(array('expiry' => '+5 seconds'));
		$key = 'key';
		$data = 'value';

		$closure = $Cache->write($key, $data);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data');
		$result = $closure($Cache, $params, null);
		$expected = $data;
		$this->assertTrue($result);

		$result = zend_disk_cache_fetch($key);
		$this->assertEqual($expected, $result);

		$result = zend_disk_cache_delete($key);
		$this->assertTrue($result);
	}

	public function testWriteMulti() {
		$expiry = '+1 minute';
		$key = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$data = null;

		$closure = $this->Cache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Cache, $params, null);

		$this->assertEqual(array(), $result);

		$result = zend_disk_cache_fetch(array_keys($key));
		$this->assertEqual($key, $result);

        foreach($key as $k=>$v) {
            $result = zend_disk_cache_delete($k);
            $this->assertTrue($result);
        }
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';

		$result = zend_disk_cache_store($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->Cache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = zend_disk_cache_delete($key);
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';

		$result = zend_disk_cache_store($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->Cache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cache, $params, null);
		$expected = $data;

		$this->assertEqual($expected, $result);

		$result = zend_disk_cache_delete($key);
		$this->assertTrue($result);
	}

	public function testReadMulti() {
		$expiry = '+1 minute';
		$key = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$data = null;

		$closure = $this->Cache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Cache, $params, null);
		$this->assertEqual(array(), $result);

		$expected = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$result = zend_disk_cache_fetch(array_keys($key));
		$this->assertEqual($expected, $result);

        foreach($key as $k=>$v) {
            $result = zend_disk_cache_delete($k);
            $this->assertTrue($result);
        }

	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$closure = $this->Cache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cache, $params, null);
		$this->assertFalse($result);
	}

	public function testDelete() {
		$key = 'delete_key';
		$data = 'data to delete';

		$result = zend_disk_cache_store($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->Cache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cache, $params, null);
		$this->assertTrue($result);
	}

	public function testDeleteMulti() {
		$expiry = '+1 minute';
		$key = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);
		$data = null;

		$closure = $this->Cache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Cache, $params, null);
		$this->assertEqual(array(), $result);

		$expected = array(
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		);

        foreach($key as $k=>$v) {
            $result = zend_disk_cache_delete($k);
            $this->assertTrue($result);
        }

	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$data = 'data to delete';

		$closure = $this->Cache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cache, $params, null);
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$expiry = '+5 seconds';

		$closure = $this->Cache->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Cache, $params, null);
		$expected = $data;
		$this->assertTrue($result);

		$result = zend_disk_cache_fetch($key);
		$this->assertEqual($expected, $result);

		$closure = $this->Cache->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cache, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$closure = $this->Cache->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cache, $params, null);
		$this->assertTrue($result);
	}

	public function testClear() {
		$key1 = 'key_clear_1';
		$key2 = 'key_clear_2';

		$result = zend_disk_cache_store($key1, 'data that will no longer exist', 60);
		$this->assertTrue($result);

		$result = zend_disk_cache_store($key2, 'more dead data', 60);
		$this->assertTrue($result);

		$result = $this->Cache->clear();
		$this->assertTrue($result);

		$this->assertFalse(zend_disk_cache_fetch($key1));
		$this->assertFalse(zend_disk_cache_fetch($key2));
	}
}

?>