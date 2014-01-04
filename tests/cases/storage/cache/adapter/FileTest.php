<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter;

use SplFileInfo;
use lithium\core\Libraries;
use lithium\storage\cache\adapter\File;

class FileTest extends \lithium\test\Unit {

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
	 *
	 * @return void
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
		$resources = realpath(Libraries::get(true, 'resources'));
		$paths = array("{$resources}/tmp/cache", "{$resources}/tmp/cache/templates");

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

	public function testWrite() {
		$key = 'key';
		$data = 'data';
		$keys = array($key => $data);
		$time = time();
		$expiry = "@{$time} +1 minute";
		$time = $time + 60;

		$closure = $this->File->write($keys, $expiry);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys', 'expiry');
		$result = $closure($this->File, $params, null);
		$expected = 25;
		$this->assertEqual($expected, $result);

		$this->assertFileExists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}");
		$this->assertEqual(
			file_get_contents(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"),
			"{:expiry:$time}\ndata"
		);

		$this->assertTrue(unlink(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
		$this->assertFileNotExists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}");
	}

	public function testWriteDefaultCacheExpiry() {
		$time = time();
		$file = new File(array('expiry' => "@{$time} +1 minute"));
		$key = 'default_keykey';
		$data = 'data';
		$keys = array($key => $data);
		$time = $time + 60;

		$closure = $file->write($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$result = $closure($file, $params, null);
		$expected = 25;
		$this->assertEqual($expected, $result);

		$this->assertFileExists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}");
		$this->assertEqual(
			file_get_contents(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"),
			"{:expiry:{$time}}\ndata"
		);

		$this->assertTrue(unlink(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
		$this->assertFileNotExists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}");
	}

	public function testRead() {
		$key = 'key';
		$keys = array($key);
		$time = time() + 60;

		$closure = $this->File->read($keys);
		$this->assertInternalType('callable', $closure);

		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";
		file_put_contents($path, "{:expiry:$time}\ndata");
		$this->assertFileExists($path);

		$params = compact('keys');
		$result = $closure($this->File, $params, null);
		$this->assertEqual(array($key => 'data'), $result);

		unlink($path);

		$key = 'non_existent';
		$keys = array($key);
		$params = compact('keys');
		$closure = $this->File->read($keys);
		$this->assertInternalType('callable', $closure);

		$result = $closure($this->File, $params, null);
		$this->assertFalse($result);
	}

	public function testExpiredRead() {
		$key = 'expired_key';
		$keys = array($key);
		$time = time() + 1;

		$closure = $this->File->read($keys);
		$this->assertInternalType('callable', $closure);
		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";

		file_put_contents($path, "{:expiry:$time}\ndata");
		$this->assertFileExists($path);

		sleep(2);
		$params = compact('keys');
		$this->assertFalse($closure($this->File, $params, null));

	}

	public function testDelete() {
		$key = 'key_to_delete';
		$keys = array($key);
		$time = time() + 1;
		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";

		file_put_contents($path, "{:expiry:$time}\ndata");
		$this->assertFileExists($path);

		$closure = $this->File->delete($keys);
		$this->assertInternalType('callable', $closure);

		$params = compact('keys');
		$this->assertTrue($closure($this->File, $params));

		$key = 'non_existent';
		$keys = array($key);
		$params = compact('keys');
		$this->assertFalse($closure($this->File, $params));
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

	public function testIncrement() {
		$key = 'key_to_increment';
		$result = $this->File->increment($key);
		$this->assertEqual(false, $result);
	}

	public function testDecrement() {
		$key = 'key_to_decrement';
		$result = $this->File->decrement($key);
		$this->assertEqual(false, $result);
	}
}

?>