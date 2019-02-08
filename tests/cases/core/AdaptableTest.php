<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\core;

use lithium\storage\cache\adapter\Memory;
use lithium\tests\mocks\core\MockAdaptable;

class AdaptableTest extends \lithium\test\Unit {

	public function tearDown() {
		MockAdaptable::reset();
	}

	public function testConfig() {
		$this->assertEmpty(MockAdaptable::config());

		$items = [[
			'adapter' => 'some\adapter',
			'filters' => []
		]];
		$result = MockAdaptable::config($items);
		$this->assertNull($result);

		$expected = $items;
		$result = MockAdaptable::config();
		$this->assertEqual($expected, $result);

		$items = [[
			'adapter' => 'some\adapter',
			'filters' => []
		]];
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = $items;
		$this->assertEqual($expected, $result);
	}

	public function testReset() {
		$items = [[
			'adapter' => '\some\adapter',
			'filters' => []
		]];
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = MockAdaptable::reset();
		$this->assertNull($result);
		$this->assertEmpty(MockAdaptable::config());
	}

	public function testNonExistentConfig() {
		$this->assertException("Configuration `non_existent_config` has not been defined.", function() {
			MockAdaptable::adapter('non_existent_config');
		});
	}

	public function testAdapter() {
		$items = ['default' => ['adapter' => 'Memory', 'filters' => []]];
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = MockAdaptable::adapter('default');
		$expected = new Memory($items['default']);
		$this->assertEqual($expected, $result);
	}

	public function testConfigAndAdapter() {
		$items = ['default' => ['adapter' => 'Memory', 'filters' => []]];
		MockAdaptable::config($items);
		$config = MockAdaptable::config();

		$intermediate = MockAdaptable::adapter('default');
		$expected = new Memory($items['default']);
		$this->assertEqual($expected, $intermediate);

		$result = MockAdaptable::config();
		$modified['default'] = $config['default'] + ['object' => $intermediate];
		$this->assertEqual($modified, $result);

		MockAdaptable::config(['default' => ['adapter' => 'Memory']]);
		$result = MockAdaptable::config();
		$this->assertEqual($config, $result);
	}

	public function testStrategy() {
		$strategy = new MockAdaptable();
		$items = ['default' => [
			'strategies' => ['lithium\tests\mocks\storage\cache\strategy\MockSerializer'],
			'filters' => [],
			'adapter' => null
		]];
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = $strategy::strategies('default');
		$this->assertInstanceOf('SplDoublyLinkedList', $result);
		$this->assertCount(1, $result);
		$obj = $result->top();
		$this->assertInstanceOf('lithium\tests\mocks\storage\cache\strategy\MockSerializer', $obj);
	}

	public function testInvalidStrategy() {
		$strategy = new MockAdaptable();
		$items = ['default' => [
			'strategies' => ['InvalidStrategy'],
			'filters' => [],
			'adapter' => null
		]];
		$strategy::config($items);

		$class = 'lithium\tests\mocks\core\MockAdaptable';
		$message = "Could not find strategy `InvalidStrategy` in class `{$class}`.";
		$this->assertException($message, function() use ($strategy) {
			$strategy::strategies('default');
		});
	}

	public function testStrategyConstructionSettings() {
		$mockConfigurizer = 'lithium\tests\mocks\storage\cache\strategy\MockConfigurizer';
		$strategy = new MockAdaptable();
		$items = ['default' => [
			'strategies' => [
				$mockConfigurizer => [
					'key1' => 'value1', 'key2' => 'value2'
				]
			],
			'filters' => [],
			'adapter' => null
		]];
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = $strategy::strategies('default');
		$this->assertInstanceOf('SplDoublyLinkedList', $result);
		$this->assertEqual(count($result), 1);
		$this->assertInstanceOf($mockConfigurizer, $result->top());
	}

	public function testNonExistentStrategyConfiguration() {
		$strategy = new MockAdaptable();

		$expected = "Configuration `non_existent_config` has not been defined.";
		$this->assertException($expected, function() use ($strategy) {
			$strategy::strategies('non_existent_config');
		});
	}

	public function testApplyStrategiesNonExistentConfiguration() {
		$strategy = new MockAdaptable();

		$expected = "Configuration `non_existent_config` has not been defined.";
		$this->assertException($expected, function() use ($strategy) {
			$strategy::applyStrategies('method', 'non_existent_config', null);
		});
	}

	public function testApplySingleStrategy() {
		$strategy = new MockAdaptable();
		$items = ['default' => [
			'filters' => [],
			'adapter' => null,
			'strategies' => ['lithium\tests\mocks\storage\cache\strategy\MockSerializer']
		]];
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$data = ['some' => 'data'];
		$result = $strategy::applyStrategies('write', 'default', $data);
		$this->assertEqual(serialize($data), $result);
	}

	public function testApplySingleStrategyWithConfiguration() {
		$strategy = new MockAdaptable();
		$params = ['key1' => 'value1', 'key2' => 'value2'];
		$items = ['default' => [
			'filters' => [],
			'adapter' => null,
			'strategies' => [
				'lithium\tests\mocks\storage\cache\strategy\MockConfigurizer' => $params
			]
		]];
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = $strategy::applyStrategies('write', 'default', null);
		$this->assertEqual($params, $result);
	}

	public function testApplyMultipleStrategies() {
		$strategy = new MockAdaptable();
		$items = ['default' => [
			'filters' => [],
			'adapter' => null,
			'strategies' => [
				'lithium\tests\mocks\storage\cache\strategy\MockSerializer', 'Base64'
			]
		]];
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$data = ['some' => 'data'];
		$result = $strategy::applyStrategies('write', 'default', $data);
		$transformed = base64_encode(serialize($data));
		$this->assertEqual($transformed, $result);

		$options = ['mode' => 'LIFO'];
		$result = $strategy::applyStrategies('read', 'default', $transformed, $options);
		$expected = $data;
		$this->assertEqual($expected, $result);
	}

	public function testApplyStrategiesNoConfiguredStrategies() {
		$strategy = new MockAdaptable();

		$items = ['default' => [
			'filters' => [],
			'adapter' => null
		]];
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = $strategy::applyStrategies('method', 'default', null);
		$this->assertNull($result);

		$items = ['default' => [
			'filters' => [],
			'adapter' => null,
			'strategies' => []
		]];
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$data = ['some' => 'data'];
		$result = $strategy::applyStrategies('method', 'default', $data);
		$this->assertEqual($data, $result);
	}

	public function testEnabled() {
		$items = ['default' => ['adapter' => 'Memory', 'filters' => []]];
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = MockAdaptable::adapter('default');
		$expected = new Memory($items['default']);
		$this->assertEqual($expected, $result);

		$this->assertIdentical(true, MockAdaptable::enabled('default'));
		$this->assertException('/No adapter set for configuration/', function() {
			MockAdaptable::enabled('non-existent');
		});
	}

	public function testNonExistentAdapter() {
		$items = ['default' => ['adapter' => 'NonExistent', 'filters' => []]];
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$message  = 'Could not find adapter `NonExistent` in ';
		$message .= 'class `lithium\tests\mocks\core\MockAdaptable`.';
		$this->assertException($message, function() {
			MockAdaptable::adapter('default');
		});
	}

	public function testEnvironmentSpecificConfiguration() {
		$config = ['adapter' => 'Memory', 'filters' => []];
		$items = ['default' => [
			'development' => $config, 'test' => $config, 'production' => $config
		]];
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = ['default' => $config];
		$this->assertEqual($expected, $result);

		$result = MockAdaptable::config('default');
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = MockAdaptable::adapter('default');
		$expected = new Memory($config);
		$this->assertEqual($expected, $result);
	}

	public function testConfigurationNoAdapter() {
		$items = ['default' => ['filters' => []]];
		MockAdaptable::config($items);

		$message  = 'No adapter set for configuration in ';
		$message .= 'class `lithium\tests\mocks\core\MockAdaptable`.';
		$this->assertException($message, function() {
			MockAdaptable::adapter('default');
		});
	}

	public function testNotCreateCacheWhenTestingEnabled() {
		MockAdaptable::config([
			'default' => [
				['adapter' => 'Memory']
			]
		]);
		MockAdaptable::enabled('default');
		$this->assertFalse(MockAdaptable::testInitialized('default'));
	}

	/* Deprecated / BC */

	public function testDeprecatedConfig() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$items = [[
			'adapter' => 'some\adapter',
			'filters' => ['filter1', 'filter2']
		]];
		$result = MockAdaptable::config($items);
		$this->assertNull($result);

		$expected = $items;
		$result = MockAdaptable::config();
		$this->assertEqual($expected, $result);

		$items = [[
			'adapter' => 'some\adapter',
			'filters' => ['filter1', 'filter2']
		]];
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		error_reporting($original);
	}

	public function testDeprecatedReset() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$items = [[
			'adapter' => '\some\adapter',
			'filters' => ['filter1', 'filter2']
		]];
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		error_reporting($original);
	}
}

?>