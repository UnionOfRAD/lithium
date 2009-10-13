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

use \lithium\storage\cache\adapters\Apc;

class ApcTest extends \lithium\test\Unit {

	/**
	 * Skip the test if APC extension is unavailable.
	 *
	 * @return void
	 */
	public function skip() {
		$extensionExists = extension_loaded('apc');
		$message = 'The apc extension is not installed.';
		$this->skipIf(!$extensionExists, $message);
	}

	public function setUp() {
		apc_clear_cache('user');
		$this->Apc = new Apc();
	}

	public function tearDown() {
		apc_clear_cache('user');
		unset($this->Apc);
	}

	public function testSimpleWrite() {
		$key = 'key';
		$data = 'value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->Apc->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = apc_fetch($key);
		$this->assertEqual($expected, $result);

		$result = apc_fetch($key . '_expires');
		$this->assertEqual($time, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);

		$key = 'another_key';
		$data = 'more_data';
		$expiry = '+1 minute';
		$time = strtotime($expiry);

		$closure = $this->Apc->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = apc_fetch($key);
		$this->assertEqual($expected, $result);

		$result = apc_fetch($key . '_expires');
		$this->assertEqual($time, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);

		$result = apc_delete($key . '_expires');
		$this->assertTrue($result);
	}

	public function testSimpleRead() {
		$key = 'read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = apc_store($key . '_expires', $time, 60);
		$this->assertTrue($result);

		$result = apc_store($key, $data, 60);
		$this->assertTrue($result);

		$closure = $this->Apc->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);

		$result = apc_delete($key . '_expires');
		$this->assertTrue($result);

		$key = 'another_read_key';
		$data = 'read data';
		$time = strtotime('+1 minute');

		$result = apc_store($key, $data, 60);
		$this->assertTrue($result);

		$result = apc_store($key . '_expires', $time, 60);
		$this->assertTrue($result);

		$closure = $this->Apc->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = apc_delete($key);
		$this->assertTrue($result);

		$result = apc_delete($key . '_expires');
		$this->assertTrue($result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$closure = $this->Apc->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertFalse($result);

	}

	public function testDelete() {
		$key = 'delete_key';
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$result = apc_store($key, $data, 60);
		$this->assertTrue($result);

		$result = apc_store($key . '_expires', $time, 60);
		$this->assertTrue($result);

		$closure = $this->Apc->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertTrue($result);

		$this->assertFalse(apc_delete($key));
		$this->assertFalse(apc_delete($key . '_expires'));
	}

	public function testDeleteNonExistentKey() {
		$key = 'delete_key';
		$data = 'data to delete';
		$time = strtotime('+1 minute');

		$closure = $this->Apc->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertFalse($result);
	}

	public function testWriteReadAndDeleteRoundtrip() {
		$key = 'write_read_key';
		$data = 'write/read value';
		$expiry = '+5 seconds';
		$time = strtotime($expiry);

		$closure = $this->Apc->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = apc_fetch($key);
		$this->assertEqual($expected, $result);

		$result = apc_fetch($key . '_expires');
		$this->assertEqual($time, $result);

		$closure = $this->Apc->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$expected = $data;
		$this->assertEqual($expected, $result);

		$closure = $this->Apc->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertTrue($result);

		$this->assertFalse(apc_fetch($key));
		$this->assertFalse(apc_fetch($key . '_expires'));
	}

	public function testExpiredRead() {
		$key = 'expiring_read_key';
		$data = 'expired data';
		$time = strtotime('+1 second');

		$result = apc_store($key . '_expires', $time, 1);
		$this->assertTrue($result);

		$result = apc_store($key, $data, 1);
		$this->assertTrue($result);

		sleep(2);
		$closure = $this->Apc->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Apc, $params, null);
		$this->assertFalse($result);
	}

	public function testClear() {
		$key1 = 'key_clear_1';
		$key2 = 'key_clear_2';
		$time = strtotime('+1 minute');

		$result = apc_store($key1, 'data that will no longer exist', $time);
		$this->assertTrue($result);

		$result = apc_store($key2, 'more dead data', $time);
		$this->assertTrue($result);

		$result = $this->Apc->clear();
		$this->assertTrue($result);

		$this->assertFalse(apc_fetch($key1));
		$this->assertFalse(apc_fetch($key2));
	}
}

?>