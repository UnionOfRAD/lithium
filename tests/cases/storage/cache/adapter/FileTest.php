<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\cache\adapter;

use SplFileInfo;
use lithium\core\Libraries;
use lithium\storage\Cache;
use lithium\storage\cache\adapter\File;

class FileTest extends \lithium\test\Unit {

	public $File;

	/**
	 * Checks whether the 'empty' file exists in `resources/tmp/cache` and, if so, ensures
	 * that it is restored at the end of the testing cycle.
	 *
	 * @var string
	 */
	protected $_hasEmpty = true;

	/**
	 * Skip the test if the default File adapter read/write path
	 * is not read/write-able.
	 */
	public function skip() {
		$directory = new SplFileInfo(Libraries::get(true, 'resources') . "/tmp/cache/");
		$accessible = ($directory->isDir() && $directory->isReadable() && $directory->isWritable());
		$message = 'The File cache adapter path does not have the proper permissions.';
		$this->skipIf(!$accessible, $message);
	}

	public function setUp() {
		$this->_hasEmpty = file_exists(Libraries::get(true, 'resources') . "/tmp/cache/empty");
		$this->File = new File();
	}

	public function tearDown() {
		$this->File->clear();

		$resources = realpath(Libraries::get(true, 'resources'));
		$paths = ["{$resources}/tmp/cache", "{$resources}/tmp/cache/templates"];

		if ($this->_hasEmpty) {
			foreach ($paths as $path) {
				$path = realpath($path);
				if (is_dir($path) && is_writable($path)) {
					touch("{$resources}/empty");
				}
			}
		}
		unset($this->File);
	}

	public function testEnabled() {
		$file = $this->File;
		$this->assertTrue($file::enabled());
	}

	public function testSanitzeKeys() {
		$result = $this->File->key(['posts for bjœrn']);
		$expected = ['posts_for_bj_rn_fdf03955'];
		$this->assertEqual($expected, $result);

		$result = $this->File->key(['posts-for-bjoern']);
		$expected = ['posts-for-bjoern'];
		$this->assertEqual($expected, $result);

		$result = $this->File->key(['posts for Helgi Þorbjörnsson']);
		$expected = ['posts_for_Helgi__orbj_rnsson_c7f8433a'];
		$this->assertEqual($expected, $result);

		$result = $this->File->key(['libraries.cache']);
		$expected = ['libraries_cache_38235880'];
		$this->assertEqual($expected, $result);

		$key = 'post_';
		for ($i = 0; $i <= 127; $i++) {
			$key .= chr($i);
		}
		$result = $this->File->key([$key]);
		$expected  = 'post______________________________________________-__0123456789_______ABCDEF';
		$expected .= 'GHIJKLMNOPQRSTUVWXYZ______abcdefghijklmnopqrstuvwxyz______38676d3e';
		$expected = [$expected];
		$this->assertEqual($expected, $result);

		$key = str_repeat('0', 300);
		$result = $this->File->key([$key]);
		$expected = [str_repeat('0', 246) . '_9e1830ed'];
		$this->assertEqual($expected, $result);
		$this->assertTrue(strlen($result[0]) <= 255);

		$adapter = new File(['scope' => 'foo']);

		$key = str_repeat('0', 300);
		$result = $adapter->key([$key]);
		$expected = [str_repeat('0', 246 - strlen('_foo')) . '_9e1830ed'];
		$this->assertEqual($expected, $result);
		$this->assertTrue(strlen($result[0]) <= 255);
	}

	public function testWrite() {
		$key = 'key';
		$data = 'data';
		$keys = [$key => $data];
		$time = time();
		$expiry = "@{$time} +1 minute";
		$time = $time + 60;


		$expected = 25;
		$result = $this->File->write($keys, $expiry);
		$this->assertEqual($expected, $result);

		$this->assertFileExists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}");
		$this->assertEqual(
			file_get_contents(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"),
			"{:expiry:$time}\ndata"
		);

		$this->assertTrue(unlink(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
		$this->assertFileNotExists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}");
	}

	public function testWriteMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];
		$result = $this->File->write($keys, $expiry);
		$this->assertTrue($result);

		foreach ($keys as $key => $data) {
			$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";
			$result = file_get_contents($path);
			$this->assertPattern("/{:expiry:[0-9]+}\n{$data}/", $result);
		}

		$this->File->delete(array_keys($keys));
	}

	public function testWriteExpiryDefault() {
		$time = time();
		$file = new File(['expiry' => "@{$time} +1 minute"]);
		$key = 'default_keykey';
		$data = 'data';
		$keys = [$key => $data];
		$time = $time + 60;

		$expected = 25;
		$result = $file->write($keys);
		$this->assertEqual($expected, $result);

		$this->assertFileExists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}");
		$this->assertEqual(
			file_get_contents(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"),
			"{:expiry:{$time}}\ndata"
		);

		$this->assertTrue(unlink(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
		$this->assertFileNotExists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}");
	}

	public function testWriteNoExpiry() {
		$file = Libraries::get(true, 'resources') . '/tmp/cache/key1';
		$keys = ['key1' => 'data1'];

		$adapter = new File(['expiry' => null]);
		$expiry = null;

		$result = $adapter->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = "{:expiry:0}\ndata1";
		$result = file_get_contents($file);
		$this->assertEqual($expected, $result);

		unlink($file);

		$adapter = new File(['expiry' => Cache::PERSIST]);
		$expiry = Cache::PERSIST;

		$result = $adapter->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = "{:expiry:0}\ndata1";
		$result = file_get_contents($file);
		$this->assertEqual($expected, $result);

		unlink($file);

		$adapter = new File();
		$expiry = Cache::PERSIST;

		$result = $adapter->write($keys, $expiry);
		$this->assertTrue($result);

		$expected = "{:expiry:0}\ndata1";
		$result = file_get_contents($file);
		$this->assertEqual($expected, $result);

		unlink($file);
	}

	public function testWriteExpiryExpires() {
		$now = time();

		$keys = ['key1' => 'data1'];
		$time = $now + 5;
		$expiry = "@{$now} +5 seconds";
		$this->File->write($keys, $expiry);

		$file = Libraries::get(true, 'resources') . '/tmp/cache/key1';

		$expected = "{:expiry:{$time}}\ndata1";
		$result = file_get_contents($file);
		$this->assertEqual($expected, $result);
	}

	public function testWriteExpiryTtl() {
		$now = time();

		$keys = ['key1' => 'data1'];
		$time = $now + 5;
		$expiry = 5;
		$this->File->write($keys, $expiry);

		$file = Libraries::get(true, 'resources') . '/tmp/cache/key1';

		$expected = "{:expiry:{$time}}\ndata1";
		$result = file_get_contents($file);
		$this->assertEqual($expected, $result);
	}

	public function testWriteWithScope() {
		$now = time();

		$adapter = new File(['scope' => 'primary']);

		$time = $now + 5;
		$expiry = 5;

		$keys = [
			'key1' => 'test1'
		];
		$adapter->write($keys, $expiry);

		$file = Libraries::get(true, 'resources') . '/tmp/cache/primary_key1';

		$expected = "{:expiry:{$time}}\ntest1";
		$result = file_get_contents($file);
		$this->assertEqual($expected, $result);
	}

	public function testWriteUsingStream() {
		$now = time();

		$adapter = new File();
		$file = Libraries::get(true, 'resources') . '/tmp/cache/bar';

		$time = $now + 5;
		$expiry = 5;

		$stream = fopen('php://temp', 'wb');
		fwrite($stream, 'foo');
		rewind($stream);
		$adapter->write(['bar' => $stream], $expiry);

		$expected = "{:expiry:{$time}}\nfoo";
		$result = file_get_contents($file);
		$this->assertEqual($expected, $result);
	}

	public function testRead() {
		$key = 'key';
		$keys = [$key];
		$time = time() + 60;

		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";
		file_put_contents($path, "{:expiry:$time}\ndata");
		$this->assertFileExists($path);

		$params = compact('keys');
		$result = $this->File->read($keys);
		$this->assertEqual([$key => 'data'], $result);

		unlink($path);
	}

	public function testReadMulti() {
		$time = time() + 60;
		$keys = [
			'key1' => 'data1',
			'key2' => 'data2',
			'key3' => 'data3'
		];
		foreach ($keys as $key => $data) {
			$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";
			file_put_contents($path, "{:expiry:{$time}}\n{$data}");
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
		$result = $this->File->read($keys);
		$this->assertEqual($expected, $result);

		$this->File->delete($keys);
	}

	public function testExpiredRead() {
		$key = 'expired_key';
		$keys = [$key];
		$time = time() + 1;

		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";

		file_put_contents($path, "{:expiry:$time}\ndata");
		$this->assertFileExists($path);

		sleep(2);

		$expected = [];
		$result= $this->File->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testReadKeyThatDoesNotExist() {
		$key = 'does_not_exist';
		$keys = [$key];

		$expected = [];
		$result = $this->File->read($keys);
		$this->assertIdentical($expected, $result);
	}

	public function testReadWithScope() {
		$adapter = new File(['scope' => 'primary']);
		$time = time() + 60;

		$keys = [
			'primary_key1' => 'test1',
			'key1' => 'test2'
		];
		foreach ($keys as $key => $data) {
			$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";
			file_put_contents($path, "{:expiry:{$time}}\n{$data}");
		}

		$keys = ['key1'];
		$expected = ['key1' => 'test1'];
		$result = $adapter->read($keys);
		$this->assertEqual($expected, $result);
	}

	public function testReadStreams() {
		$adapter = new File(['streams' => true]);

		$adapter->write(['bar' => 'foo'], 50);
		$result = $adapter->read(['bar']);
		$this->assertTrue(is_resource($result['bar']));

		$expected = 'foo';
		$result = stream_get_contents($result['bar']);
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNull() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null
		];
		$result = $this->File->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->File->read(array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testWriteAndReadNullMulti() {
		$expiry = '+1 minute';
		$keys = [
			'key1' => null,
			'key2' => 'data2'
		];
		$result = $this->File->write($keys);
		$this->assertTrue($result);

		$expected = $keys;
		$result = $this->File->read(array_keys($keys));
		$this->assertEqual($expected, $result);

		$keys = [
			'key1' => null,
			'key2' => null
		];
		$result = $this->File->write($keys);
		$this->assertTrue($result);
	}

	public function testDelete() {
		$key = 'key_to_delete';
		$keys = [$key];
		$time = time() + 1;
		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";

		file_put_contents($path, "{:expiry:$time}\ndata");
		$this->assertFileExists($path);


		$result = $this->File->delete($keys);
		$this->assertTrue($result);

		$key = 'non_existent';
		$keys = [$key];
		$result = $this->File->delete($keys);
		$this->assertFalse($result);
	}

	public function testDeleteWithScope() {
		$adapter = new File(['scope' => 'primary']);
		$time = time() + 60;

		$keys = [
			'primary_key1' => 'test1',
			'key1' => 'test2'
		];
		foreach ($keys as $key => $data) {
			$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";
			file_put_contents($path, "{:expiry:{$time}}\n{$data}");
		}

		$keys = ['key1'];
		$adapter->delete($keys);

		$file = Libraries::get(true, 'resources') . "/tmp/cache/key1";
		$result = file_exists($file);
		$this->assertTrue($result);

		$file = Libraries::get(true, 'resources') . "/tmp/cache/primary_key1";
		$result = file_exists($file);
		$this->assertFalse($result);
	}

	public function testClear() {
		$key = 'key_to_clear';
		$time = time() + 1;
		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";
		file_put_contents($path, "{:expiry:$time}\ndata");

		$result = $this->File->clear();
		$this->assertTrue($result);
		$this->assertFileNotExists($path);

		$result = touch(Libraries::get(true, 'resources') . "/tmp/cache/empty");
		$this->assertTrue($result);
	}

	public function testClean() {
		$time = time() - 10;
		$path = Libraries::get(true, 'resources') . "/tmp/cache/key_to_clean";
		file_put_contents($path, "{:expiry:$time}\ndata");

		$result = $this->File->clean();
		$this->assertTrue($result);
		$this->assertFileNotExists($path);

		$time = time() + 10;
		$path = Libraries::get(true, 'resources') . "/tmp/cache/key_not_to_clean";
		file_put_contents($path, "{:expiry:$time}\ndata");

		$result = $this->File->clean();
		$this->assertTrue($result);
		$this->assertFileExists($path);
	}

	public function testDecrement() {
		$key = __FUNCTION__;

		$result = $this->File->write([$key => 5]);
		$this->assertTrue($result);

		$expected = 4;
		$result = $this->File->decrement($key);
		$this->assertEqual($expected, $result);

		$expected = [$key => 4];
		$result = $this->File->read([$key]);
		$this->assertEqual($expected, $result);
	}

	public function testDecrementNotExistent() {
		$key = __FUNCTION__;

		$result = $this->File->decrement($key);
		$this->assertFalse($result);
	}

	public function testDecrementWithScope() {
		$adapter = new File(['scope' => 'primary']);

		$this->File->write(['primary_key1' => 5]);
		$this->File->write(['key1' => 10]);

		$expected = 4;
		$result = $adapter->decrement('key1');
		$this->assertEqual($expected, $result);

		$expected = ['key1' => 4];
		$result = $adapter->read(['key1']);
		$this->assertEqual($expected, $result);
	}

	public function testIncrement() {
		$key = __FUNCTION__;

		$result = $this->File->write([$key => 5]);
		$this->assertTrue($result);

		$expected = 6;
		$result = $this->File->increment($key);
		$this->assertEqual($expected, $result);

		$expected = [$key => 6];
		$result = $this->File->read([$key]);
		$this->assertEqual($expected, $result);
	}

	public function testIncrementNotExistent() {
		$key = __FUNCTION__;

		$result = $this->File->increment($key);
		$this->assertFalse($result);
	}

	public function testIncrementWithScope() {
		$adapter = new File(['scope' => 'primary']);

		$this->File->write(['primary_key1' => 5]);
		$this->File->write(['key1' => 10]);

		$expected = 6;
		$result = $adapter->increment('key1');
		$this->assertEqual($expected, $result);

		$expected = ['key1' => 6];
		$result = $adapter->read(['key1']);
		$this->assertEqual($expected, $result);
	}
}

?>