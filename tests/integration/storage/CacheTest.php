<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\storage;

use SplFileInfo;
use lithium\core\Libraries;
use lithium\storage\Cache;

class CacheTest extends \lithium\test\Integration {

	public function setUp() {
		Cache::reset();
	}

	public function tearDown() {
		Cache::reset();
	}

	protected function _checkPath() {
		$resources = Libraries::get(true, 'resources');

		if (is_writable($resources) && !is_dir("{$resources}/tmp/cache")) {
			mkdir("{$resources}/tmp/cache", 0777, true);
		}
		$directory = new SplFileInfo("{$resources}/tmp/cache");

		return ($directory->isDir() && $directory->isReadable() && $directory->isWritable());
	}

	public function testFileAdapterCacheConfig() {
		$result = Cache::config();
		$this->assertEmpty($result);
		$config = array('default' => array('adapter' => 'File', 'filters' => array()));

		Cache::config($config);
		$this->assertEqual($config, Cache::config());
	}

	public function testReadThroughWithStrategies() {
		Cache::config(array(
			'default' => array(
				'adapter' => 'Memory',
				'strategies' => array('Serializer')
			)
		));

		$expected = 'bar';
		$result = Cache::read('default', 'foo', array(
			'write' => array('+5 seconds' => 'bar')
		));
		$this->assertEqual($expected, $result);
	}

	public function testFileAdapterReadThroughWithStrategies() {
		$resources = Libraries::get(true, 'resources');
		$path = "{$resources}/tmp/cache";
		$this->skipIf(!$this->_checkPath(), "{$path} does not have the proper permissions.");

		Cache::config(array(
			'default' => array(
				'adapter' => 'File',
				'strategies' => array('Serializer'),
				'filters' => array(),
				'path' => $path
			)
		));

		$expected = 'bar';
		$result = Cache::read('default', 'foo', array(
			'write' => array('+5 seconds' => 'bar')
		));
		$this->assertEqual($expected, $result);

		$expected = 'bar';
		$result = Cache::read('default', 'foo');
		$this->assertEqual($expected, $result);
	}

	public function testMultiWriteReadWithStrategies() {
		Cache::config(array(
			'default' => array(
				'adapter' => 'Memory',
				'strategies' => array('Serializer')
			)
		));
		$keys = array(
			'key1' => 'data1',
			'key2' => 'data2'
		);
		$result = Cache::write('default', $keys, null);
		$this->assertTrue($result);

		$expected = array(
			'key1' => 'data1',
			'key2' => 'data2'
		);
		$keys = array(
			'key1',
			'key2'
		);
		$result = Cache::read('default', $keys);
		$this->assertEqual($expected, $result);
	}

	public function testMultiWriteReadWithMultipleStrategies() {
		Cache::config(array(
			'default' => array(
				'adapter' => 'Memory',
				'strategies' => array('Serializer', 'Base64')
			)
		));
		$keys = array(
			'key1' => 'data1',
			'key2' => 'data2'
		);
		$result = Cache::write('default', $keys, null);
		$this->assertTrue($result);

		$expected = array(
			'key1' => 'data1',
			'key2' => 'data2'
		);
		$keys = array(
			'key1',
			'key2'
		);
		$result = Cache::read('default', $keys);
		$this->assertEqual($expected, $result);
	}

	public function testFileAdapterWrite() {
		$resources = Libraries::get(true, 'resources');
		$path = "{$resources}/tmp/cache";
		$this->skipIf(!$this->_checkPath(), "{$path} does not have the proper permissions.");

		$config = array('default' => compact('path') + array(
			'adapter' => 'File',
			'filters' => array()
		));
		Cache::config($config);

		$time = time();
		$result = Cache::write('default', 'key', 'value', "@{$time} +1 minute");
		$this->assertNotEmpty($result);

		$time = $time + 60;
		$result = file_get_contents("{$path}/key");
		$expected = "{:expiry:$time}\nvalue";
		$this->assertEqual($result, $expected);

		$result = unlink("{$path}/key");
		$this->assertTrue($result);
		$this->assertFileNotExists("{$path}/key");
	}

	public function testFileAdapterWithStrategies() {
		$resources = Libraries::get(true, 'resources');
		$path = "{$resources}/tmp/cache";
		$this->skipIf(!$this->_checkPath(), "{$path} does not have the proper permissions.");

		$config = array('default' => compact('path') + array(
			'adapter' => 'File',
			'filters' => array(),
			'strategies' => array('Serializer')
		));
		Cache::config($config);

		$data = array('some' => 'data');
		$time = time();
		$result = Cache::write('default', 'key', $data, "@{$time} +1 minute");
		$this->assertNotEmpty($result);

		$time = $time + 60;
		$result = file_get_contents("{$path}/key");

		$expected = "{:expiry:$time}\na:1:{s:4:\"some\";s:4:\"data\";}";
		$this->assertEqual($result, $expected);

		$result = Cache::read('default', 'key');
		$this->assertEqual($data, $result);

		$result = unlink("{$path}/key");
		$this->assertTrue($result);
		$this->assertFileNotExists("{$path}/key");
	}

	public function testFileAdapterMultipleStrategies() {
		$resources = Libraries::get(true, 'resources');
		$path = "{$resources}/tmp/cache";
		$this->skipIf(!$this->_checkPath(), "{$path} does not have the proper permissions.");

		$config = array('default' => compact('path') + array(
			'adapter' => 'File',
			'filters' => array(),
			'strategies' => array('Serializer', 'Base64')
		));
		Cache::config($config);

		$data = array('some' => 'data');
		$time = time();
		$result = Cache::write('default', 'key', $data, "@{$time} +1 minute");
		$this->assertNotEmpty($result);

		$time = $time + 60;
		$result = file_get_contents("{$path}/key");

		$expected = "{:expiry:$time}\nYToxOntzOjQ6InNvbWUiO3M6NDoiZGF0YSI7fQ==";
		$this->assertEqual($result, $expected);

		$result = Cache::read('default', 'key');
		$this->assertEqual($data, $result);

		$result = unlink("{$path}/key");
		$this->assertTrue($result);
		$this->assertFileNotExists("{$path}/key");
	}
}

?>