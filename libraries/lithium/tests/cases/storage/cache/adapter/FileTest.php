<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapter;

use \lithium\storage\cache\adapter\File;
use \SplFileInfo;

class FileTest extends \lithium\test\Unit {

	/**
	 * Checks whether the 'empty' file exists in `app/resources/tmp/cache` and, if so, ensures
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
		$directory = new SplFileInfo(LITHIUM_APP_PATH . "/resources/tmp/cache/");
		$accessible = ($directory->isDir() && $directory->isReadable() && $directory->isWritable());
		$message = 'The File cache adapter path does not have the proper permissions.';
		$this->skipIf(!$accessible, $message);
	}

	public function setUp() {
		$this->_hasEmpty = file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/empty");
		$this->File = new File();
	}

	public function tearDown() {
		if ($this->_hasEmpty) {
			touch(LITHIUM_APP_PATH . "/resources/tmp/cache/empty");
			touch(LITHIUM_APP_PATH . "/resources/tmp/cache/templates/empty");
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
		$expiry = '+1 minute';
		$time = time() + 60;

		$closure = $this->File->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data', 'expiry');
		$result = $closure($this->File, $params, null);
		$expected = 25;
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));
		$this->assertEqual(
			file_get_contents(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"),
			"{:expiry:$time}\ndata"
		);

		$this->assertTrue(unlink(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));
		$this->assertFalse(file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));
	}

	public function testWriteDefaultCacheExpiry() {
		$File = new File(array('expiry' => '+1 minute'));
		$key = 'default_keykey';
		$data = 'data';
		$time = time() + 60;

		$closure = $File->write($key, $data);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data');
		$result = $closure($File, $params, null);
		$expected = 25;
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));
		$this->assertEqual(
			file_get_contents(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"),
			"{:expiry:$time}\ndata"
		);

		$this->assertTrue(unlink(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));
		$this->assertFalse(file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));
	}

	public function testRead() {
		$key = 'key';
		$time = time() + 60;

		$closure = $this->File->read($key);
		$this->assertTrue(is_callable($closure));

		file_put_contents(LITHIUM_APP_PATH . "/resources/tmp/cache/$key", "{:expiry:$time}\ndata");
		$this->assertTrue(file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));

		$params = compact('key');
		$result = $closure($this->File, $params, null);
		$expected = 'data';
		$this->assertEqual($expected, $result);

		$this->assertTrue(unlink(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));
		$this->assertFalse(file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));

		$key = 'non_existent';
		$params = compact('key');
		$closure = $this->File->read($key);
		$this->assertTrue(is_callable($closure));

		$result = $closure($this->File, $params, null);
		$this->assertFalse($result);
	}

	public function testExpiredRead() {
		$key = 'expired_key';
		$time = time() + 1;

		$closure = $this->File->read($key);
		$this->assertTrue(is_callable($closure));

		file_put_contents(LITHIUM_APP_PATH . "/resources/tmp/cache/$key", "{:expiry:$time}\ndata");
		$this->assertTrue(file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));

		sleep(2);
		$params = compact('key');
		$result = $closure($this->File, $params, null);
		$this->assertFalse($result);

	}

	public function testDelete() {
		$key = 'key_to_delete';
		$time = time() + 1;

		file_put_contents(LITHIUM_APP_PATH . "/resources/tmp/cache/$key", "{:expiry:$time}\ndata");
		$this->assertTrue(file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));

		$closure = $this->File->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->File, $params, null);
		$this->assertTrue($result);

		$key = 'non_existent';
		$params = compact('key');
		$result = $closure($this->File, $params, null);
		$this->assertFalse($result);
	}

	public function testClear() {
		$key = 'key_to_clear';
		$time = time() + 1;
		file_put_contents(LITHIUM_APP_PATH . "/resources/tmp/cache/$key", "{:expiry:$time}\ndata");

		$result = $this->File->clear();
		$this->assertTrue($result);
		$this->assertFalse(file_exists(LITHIUM_APP_PATH . "/resources/tmp/cache/$key"));

		$result = touch(LITHIUM_APP_PATH . "/resources/tmp/cache/empty");
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