<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage;

use lithium\storage\Cache;

class CacheTest extends \lithium\test\Unit {

	public function setUp() {
		Cache::reset();
	}

	public function tearDown() {
		Cache::reset();
	}

	public function testBasicCacheConfig() {
		$result = Cache::config();
		$this->assertEmpty($result);

		$config = ['default' => ['adapter' => '\some\adapter', 'filters' => []]];
		$result = Cache::config($config);
		$this->assertNull($result);

		$expected = $config;
		$result = Cache::config();
		$this->assertEqual($expected, $result);

		$result = Cache::reset();
		$this->assertNull($result);

		$config = ['default' => ['adapter' => '\some\adapter', 'filters' => []]];
		Cache::config($config);

		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::reset();
		$this->assertNull($result);

		$config = ['default' => [
			'adapter' => '\some\adapter',
			'filters' => []
		]];
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

		$key = function($data = []) {
			$defaults = ['foo' => 'foo', 'bar' => 'bar'];
			$data += $defaults;
			return 'composed_key_with_' . $data['foo'] . '_' . $data['bar'];
		};

		$result = Cache::key($key, ['foo' => 'boo', 'bar' => 'far']);
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
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
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
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => [], 'strategies' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$data = [
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 'value3'
		];
		$result = Cache::write('default', $data, '+1 minute');
		$this->assertTrue($result);
	}

	public function testCacheReadMultipleItems() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => [], 'strategies' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$data = [
			'read1' => 'value1',
			'read2' => 'value2',
			'read3' => 'value3'
		];
		$result = Cache::write('default', $data, '+1 minute');
		$this->assertTrue($result);

		$keys = array_keys($data);
		$result = Cache::read('default', $keys);
		$this->assertEqual($data, $result);
	}

	public function testCacheReadWithConditions() {
		$config = ['default' => ['adapter' => 'Memory', 'filters' => []]];
		Cache::config($config);

		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::read('default', 'some_key', ['conditions' => function() {
			return false;
		}]);
		$this->assertFalse($result);

		$conditions = function() use (&$config) {
			return (isset($config['default']));
		};

		Cache::write('default', 'some_key', 'some value', '+1 minute');
		$result = Cache::read('default', 'some_key', compact('conditions'));
		$this->assertNotEmpty($result);

		$this->assertFalse(Cache::read('non_existing', 'key_value', compact('conditions')));
	}

	public function testCacheIncrementDecrementWithConditions() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$conditions = function() {
			return false;
		};

		$result = Cache::increment('default', 'some_key', 1, compact('conditions'));
		$this->assertFalse($result);

		$conditions = function() use (&$config) {
			return (isset($config['default']));
		};

		Cache::write('default', 'some_key', 1, '+1 minute');
		$result = Cache::increment('default', 'some_key', 1, compact('conditions'));
		$this->assertEqual(2, $result);

		$conditions = function() {
			return false;
		};

		$result = Cache::decrement('default', 'decrement_some_key', 1, compact('conditions'));
		$this->assertFalse($result);

		$conditions = function() use (&$config) {
			return (isset($config['default']));
		};
		Cache::write('default', 'decrement_some_key', 1, '+1 minute');
		$result = Cache::decrement('default', 'decrement_some_key', 1, compact('conditions'));
		$this->assertEqual(0, $result);
	}

	public function testCacheWriteWithConditions() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$conditions = function() {
			return false;
		};
		$result = Cache::write(
			'default', 'some_key', 'some_data', '+1 minute', compact('conditions')
		);
		$this->assertFalse($result);

		$conditions = function() use (&$config) {
			return (isset($config['default']));
		};

		$result = Cache::write(
			'default', 'some_key', 'some_data', '+1 minute', compact('conditions')
		);
		$this->assertTrue($result);

		$result = Cache::write(
			'non_existing', 'key_value', 'data', '+1 minute', compact('conditions')
		);
		$this->assertFalse($result);
	}

	public function testCacheReadThroughWrite() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$write = function() {
			return ['+1 minute' => 'read-through write'];
		};
		$this->assertNull(Cache::read('default', 'read_through'));

		$result = Cache::read('default', 'read_through', compact('write'));
		$this->assertIdentical('read-through write', $result);

		$result = Cache::read('default', 'read_through');
		$this->assertIdentical('read-through write', $result);

		$write = ['+1 minute' => 'string read-through write'];
		$result = Cache::read('default', 'string_read_through', compact('write'));
		$this->assertIdentical('string read-through write', $result);

		$result = Cache::read('default', 'string_read_through');
		$this->assertIdentical('string read-through write', $result);

		$this->assertNull(Cache::read('default', 'string_read_through_2'));

		$result = Cache::read('default', 'string_read_through_2', ['write' => [
			'+1 minute' => function() {
				return 'read-through write 2';
			}
		]]);
		$this->assertIdentical('read-through write 2', $result);
	}

	public function testCacheReadThroughWriteNoCallWhenHasKey() {
		Cache::config(['default' => ['adapter' => 'Memory']]);

		$callCount = 0;
		Cache::write('default', 'foo', 'bar');

		$result = Cache::read('default', 'foo');
		$this->assertEqual('bar', $result);

		Cache::read('default', 'foo', ['write' => [
			'+1 minute' => function() use (&$callCount) {
				$callCount++;
				return 'baz';
			}
		]]);
		$this->assertIdentical(0, $callCount);

		$result = Cache::read('default', 'foo');
		$this->assertEqual('bar', $result);
	}

	public function testCacheReadAndWrite() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
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

		$result = Cache::write('default', 'another', ['data' => 'take two'], '+1 minute');
		$this->assertTrue($result);

		$result = Cache::read('default', 'another');
		$expected = ['data' => 'take two'];
		$this->assertEqual($expected, $result);

		$result = Cache::write(
			'default', 'another', (object) ['data' => 'take two'], '+1 minute'
		);
		$this->assertTrue($result);

		$result = Cache::read('default', 'another');
		$expected = (object) ['data' => 'take two'];
		$this->assertEqual($expected, $result);
	}

	public function testCacheWriteAndReadNull() {
		Cache::config([
			'default' => [
				'adapter' => 'Memory'
			]
		]);

		$result = Cache::write('default', 'some_key', null);
		$this->assertTrue($result);

		$result = Cache::read('default', 'some_key');
		$this->assertNull($result);
	}

	public function testCacheWriteAndReadNullMulti() {
		Cache::config([
			'default' => [
				'adapter' => 'Memory'
			]
		]);

		$keys = [
			'key1' => null,
			'key2' => 'data2'
		];
		$result = Cache::write('default', $keys);
		$this->assertTrue($result);

		$expected = [
			'key1' => null,
			'key2' => 'data2'
		];
		$result = Cache::read('default', array_keys($keys));
		$this->assertEqual($expected, $result);

		$keys = [
			'key1' => null,
			'key2' => null
		];
		$result = Cache::write('default', $keys);
		$this->assertTrue($result);

		$expected = [
			'key1' => null,
			'key2' => null
		];
		$result = Cache::read('default', array_keys($keys));
		$this->assertEqual($expected, $result);
	}

	public function testCacheReadAndWriteWithConditions() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$conditions = function() use (&$config) {
			return (isset($config['default']));
		};
		$result = Cache::read('non_existing', 'key_value', compact('conditions'));
		$this->assertFalse($result);

		$result = Cache::read('default', 'key_value', compact('conditions'));
		$this->assertEmpty($result);

		$result = Cache::write('default', 'keyed', 'some data', '+1 minute', compact('conditions'));
		$this->assertTrue($result);

		$conditions = function() {
			return false;
		};
		$result = Cache::write('default', 'keyed', 'some data', '+1 minute', compact('conditions'));
		$this->assertFalse($result);
	}

	public function testCacheWriteAndDelete() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
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
		$this->assertEmpty(Cache::read('default', 'to delete'));
	}

	public function testCacheWriteAndDeleteWithConditions() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$conditions = function() use (&$config) {
			return (isset($config['default']));
		};
		$result = Cache::delete('non_existing', 'key_value', compact('conditions'));
		$this->assertFalse($result);

		$result = Cache::write('default', 'to delete', 'dead data', '+1 minute');
		$this->assertTrue($result);

		$result = Cache::delete('default', 'to delete', [
			'conditions' => function() {
				return false;
			}
		]);
		$this->assertFalse($result);

		$result = Cache::delete('default', 'to delete', compact('conditions'));
		$this->assertTrue($result);
	}

	public function testCacheWriteAndClear() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
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
		$this->assertEmpty($result);
	}

	public function testClean() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
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
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::reset();
		$this->assertNull($result);

		$result = Cache::config();
		$this->assertEmpty($result);
	}

	public function testIncrement() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::increment('does_not_exist', 'inc');
		$this->assertFalse($result);

		$result = Cache::write('default', 'increment', 5, '+1 minute');
		$this->assertTrue($result);

		$result = Cache::increment('default', 'increment');
		$this->assertNotEmpty($result);

		$result = Cache::read('default', 'increment');
		$this->assertEqual(6, $result);
	}

	public function testDecrement() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = Cache::decrement('does_not_exist', 'dec');
		$this->assertFalse($result);

		$result = Cache::write('default', 'decrement', 5, '+1 minute');
		$this->assertTrue($result);

		$result = Cache::decrement('default', 'decrement');
		$this->assertNotEmpty($result);

		$result = Cache::read('default', 'decrement');
		$this->assertEqual(4, $result);
	}

	public function testNonPortableCacheAdapterMethod() {
		$config = ['default' => [
			'adapter' => 'Memory', 'filters' => []
		]];
		Cache::config($config);
		$result = Cache::config();
		$expected = $config;
		$this->assertEqual($expected, $result);
	}
}

?>