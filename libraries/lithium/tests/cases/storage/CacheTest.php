<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage;

use \lithium\storage\Cache;
use \lithium\util\Collection;
use \SplFileInfo;

class CacheTest extends \lithium\test\Unit {

	public function setUp() {
		Cache::reset();
	}

	public function tearDown() {
		Cache::reset();
	}

	public function testBasicCacheConfig() {
		$result = Cache::config();
		$this->assertFalse($result);

		$config = array('default' => array('adapter' => '\some\adapter', 'filters' => array()));
		$result = Cache::config($config);
		$this->assertNull($result);

		$expected = $config;
		$result = Cache::config();
		$this->assertEqual($expected, $result);

		$result = Cache::reset();
		$this->assertNull($result);

		$config = array('default' => array('adapter' => '\some\adapter', 'filters' => array()));
		Cache::config($config);

		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::reset();
		$this->assertNull($result);

		$config = array('default' => array(
			'adapter' => '\some\adapter',
			'filters' => array('Filter1', 'Filter2')
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);
	}

	public function testkeyNoContext() {
		$key = 'this is a cache key';

		$result = Cache::key($key);
		$expected = 'this is a cache key';
		$this->assertIdentical($expected, $result);

		$key = '1120-cache éë';

		$result = Cache::key($key);
		$expected = '1120-cache éë';
		$this->assertIdentical($expected, $result);
	}

	public function testKeyWithLambda() {
		$key = function() {
			return 'lambda_key';
		};

		$result = Cache::key($key);
		$expected = 'lambda_key';
		$this->assertIdentical($expected, $result);

		$key = function() {
			return 'lambda key';
		};

		$result = Cache::key($key);
		$expected = 'lambda key';
		$this->assertIdentical($expected, $result);

		$key = function($data = array()) {
			$defaults = array('foo' => 'foo', 'bar' => 'bar');
			$data += $defaults;
			return 'composed_key_with_' . $data['foo'] . '_' . $data['bar'];
		};

		$result = Cache::key($key, array('foo' => 'boo', 'bar' => 'far'));
		$expected = 'composed_key_with_boo_far';
		$this->assertIdentical($expected, $result);
	}

	public function testKeyWithClosure() {
		$value = 5;

		$key = function() use ($value) {
			return "closure key {$value}";
		};

		$result = Cache::key($key);
		$expected = 'closure key 5';
		$this->assertIdentical($expected, $result);

		$reference = 'mutable';

		$key = function () use (&$reference) {
			$reference .= ' key';
			return $reference;
		};

		$result = Cache::key($key);
		$expected = 'mutable key';
		$this->assertIdentical($expected, $result);
		$this->assertIdentical('mutable key', $reference);
	}

	public function testKeyWithClosureAndArguments() {
		$value = 'closure argument';

		$key = function($value) {
			return $value;
		};

		$result = Cache::key($key($value));
		$expected = 'closure argument';
		$this->assertIdentical($expected, $result);
	}

	public function testCacheWrite() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::write('default', 'some_key', 'some_data', '+1 minute');
		$this->assertTrue($result);

		$result = Cache::write('non_existing', 'key_value', 'data', '+1 minute');
		$this->assertFalse($result);
	}

	public function testCacheWriteMultipleItems() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array(), 'strategies' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$data = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 'value3'
		);
		$result = Cache::write('default', $data, '+1 minute');
		$this->assertTrue($result);
	}

	public function testCacheReadMultipleItems() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array(), 'strategies' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$data = array(
			'read1' => 'value1',
			'read2' => 'value2',
			'read3' => 'value3'
		);
		$result = Cache::write('default', $data, '+1 minute');
		$this->assertTrue($result);

		$keys = array_keys($data);
		$result = Cache::read('default', $keys);
		$this->assertEqual($data, $result);
	}

	public function testCacheWriteWithConditions() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::write('default', 'some_key', 'some_data', '+1 minute', function() {
			return false;
		});
		$this->assertFalse($result);

		$anonymous = function() use (&$config) {
			return (isset($config['default']));
		};
		$result = Cache::write('default', 'some_key', 'some_data', '+1 minute', $anonymous);
		$this->assertTrue($result);

		$result = Cache::write('non_existing', 'key_value', 'data', '+1 minute', $anonymous);
		$this->assertFalse($result);

	}

	public function testCacheReadAndWrite() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::read('non_existing', 'key_value');
		$this->assertFalse($result);

		$result = Cache::write('default', 'keyed', 'some data', '+1 minute');
		$this->assertTrue($result);

		$result = Cache::read('default', 'keyed');
		$expected = 'some data';
		$this->assertEqual($expected, $result);

		$result = Cache::write('default', 'another', array('data' => 'take two'), '+1 minute');
		$this->assertTrue($result);

		$result = Cache::read('default', 'another');
		$expected = array('data' => 'take two');
		$this->assertEqual($expected, $result);

		$result = Cache::write(
			'default', 'another', (object) array('data' => 'take two'), '+1 minute'
		);
		$this->assertTrue($result);

		$result = Cache::read('default', 'another');
		$expected = (object) array('data' => 'take two');
		$this->assertEqual($expected, $result);
	}

	public function testCacheReadAndWriteWithConditions() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$anonymous = function() use (&$config) {
			return (isset($config['default']));
		};
		$result = Cache::read('non_existing', 'key_value', $anonymous);
		$this->assertFalse($result);

		$result = Cache::read('default', 'key_value', $anonymous);
		$this->assertFalse($result);

		$result = Cache::write('default', 'keyed', 'some data', '+1 minute', $anonymous);
		$this->assertTrue($result);

		$result = Cache::write('default', 'keyed', 'some data', '+1 minute', function() {
			return false;
		});
		$this->assertFalse($result);
	}

	public function testCacheWriteAndDelete() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::delete('non_existing', 'key_value');
		$this->assertFalse($result);

		$result = Cache::write('default', 'to delete', 'dead data', '+1 minute');
		$this->assertTrue($result);

		$result = Cache::delete('default', 'to delete');
		$this->assertTrue($result);
		$this->assertFalse(Cache::read('default', 'to delete'));
	}

	public function testCacheWriteAndDeleteWithConditions() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$anonymous = function() use (&$config) {
			return (isset($config['default']));
		};
		$result = Cache::delete('non_existing', 'key_value', $anonymous);
		$this->assertFalse($result);

		$result = Cache::write('default', 'to delete', 'dead data', '+1 minute');
		$this->assertTrue($result);

		$result = Cache::delete('default', 'to delete', function() { return false; });
		$this->assertFalse($result);

		$result = Cache::delete('default', 'to delete', $anonymous);
		$this->assertTrue($result);
	}

	public function testCacheWriteAndClear() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::clear('non_existing');
		$this->assertFalse($result);

		$result = Cache::write('default', 'to delete', 'dead data', '+1 minute');
		$this->assertTrue($result);

		$result = Cache::clear('default');
		$this->assertTrue($result);

		$result = Cache::read('default', 'to delete');
		$this->assertFalse($result);

	}

	public function testClean() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::clean('non_existing');
		$this->assertFalse($result);

		$result = Cache::clean('default');
		$this->assertFalse($result);

	}

	public function testReset() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::reset();
		$this->assertNull($result);

		$result = Cache::config();
		$this->assertFalse($result);
	}

	public function testIncrement() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::write('default', 'increment', 5, '+1 minute');
		$this->assertTrue($result);

		$result = Cache::increment('default', 'increment');
		$this->assertTrue($result);

		$result = Cache::read('default', 'increment');
		$this->assertEqual(6, $result);
	}

	public function testDecrement() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::write('default', 'decrement', 5, '+1 minute');
		$this->assertTrue($result);

		$result = Cache::decrement('default', 'decrement');
		$this->assertTrue($result);

		$result = Cache::read('default', 'decrement');
		$this->assertEqual(4, $result);
	}

	public function testNonPortableCacheAdapterMethod() {
		$config = array('default' => array(
			'adapter' => 'Memory', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);
	}

	public function testIntegrationFileAdapterCacheConfig() {
		$result = Cache::config();
		$this->assertFalse($result);

		$config = array('default' => array(
			'adapter' => 'File', 'filters' => array()
		));
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);
	}

	public function testIntegrationFileAdapterWrite() {
		$directory = new SplFileInfo(LITHIUM_APP_PATH . "/resources/tmp/cache/");
		$accessible = ($directory->isDir() && $directory->isReadable() && $directory->isWritable());
		$message = "$directory does not have the proper permissions.";
		$this->skipIf(!$accessible, $message);

		$config = array('default' => array(
			'adapter' => 'File',
			'path' => LITHIUM_APP_PATH . '/resources/tmp/cache',
			'filters' => array()
		));
		Cache::config($config);

		$result = Cache::write('default', 'key', 'value', '+1 minute');
		$this->assertTrue($result);

		$time = time() + 60;
		$result = file_get_contents(LITHIUM_APP_PATH . '/resources/tmp/cache/key');
		$expected = "{:expiry:$time}\nvalue";
		$this->assertEqual($result, $expected);

		$result = unlink(LITHIUM_APP_PATH . '/resources/tmp/cache/key');
		$this->assertTrue($result);
		$this->assertFalse(file_exists(LITHIUM_APP_PATH . '/resources/tmp/cache/key'));
	}
}

?>