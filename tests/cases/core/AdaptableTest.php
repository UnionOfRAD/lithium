<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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

		$items = array(array(
			'adapter' => 'some\adapter',
			'filters' => array('filter1', 'filter2')
		));
		$result = MockAdaptable::config($items);
		$this->assertNull($result);

		$expected = $items;
		$result = MockAdaptable::config();
		$this->assertEqual($expected, $result);

		$items = array(array(
			'adapter' => 'some\adapter',
			'filters' => array('filter1', 'filter2')
		));
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = $items;
		$this->assertEqual($expected, $result);
	}

	public function testReset() {
		$items = array(array(
			'adapter' => '\some\adapter',
			'filters' => array('filter1', 'filter2')
		));
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
		$items = array('default' => array('adapter' => 'Memory', 'filters' => array()));
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = MockAdaptable::adapter('default');
		$expected = new Memory($items['default']);
		$this->assertEqual($expected, $result);
	}

	public function testConfigAndAdapter() {
		$items = array('default' => array('adapter' => 'Memory', 'filters' => array()));
		MockAdaptable::config($items);
		$config = MockAdaptable::config();

		$intermediate = MockAdaptable::adapter('default');
		$expected = new Memory($items['default']);
		$this->assertEqual($expected, $intermediate);

		$result = MockAdaptable::config();
		$modified['default'] = $config['default'] + array('object' => $intermediate);
		$this->assertEqual($modified, $result);

		MockAdaptable::config(array('default' => array('adapter' => 'Memory')));
		$result = MockAdaptable::config();
		$this->assertEqual($config, $result);
	}

	public function testStrategy() {
		$strategy = new MockAdaptable();
		$items = array('default' => array(
			'strategies' => array('lithium\tests\mocks\storage\cache\strategy\MockSerializer'),
			'filters' => array(),
			'adapter' => null
		));
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
		$items = array('default' => array(
			'strategies' => array('InvalidStrategy'),
			'filters' => array(),
			'adapter' => null
		));
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
		$items = array('default' => array(
			'strategies' => array(
				$mockConfigurizer => array(
					'key1' => 'value1', 'key2' => 'value2'
				)
			),
			'filters' => array(),
			'adapter' => null
		));
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
		$items = array('default' => array(
			'filters' => array(),
			'adapter' => null,
			'strategies' => array('lithium\tests\mocks\storage\cache\strategy\MockSerializer')
		));
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$data = array('some' => 'data');
		$result = $strategy::applyStrategies('write', 'default', $data);
		$this->assertEqual(serialize($data), $result);
	}

	public function testApplySingleStrategyWithConfiguration() {
		$strategy = new MockAdaptable();
		$params = array('key1' => 'value1', 'key2' => 'value2');
		$items = array('default' => array(
			'filters' => array(),
			'adapter' => null,
			'strategies' => array(
				'lithium\tests\mocks\storage\cache\strategy\MockConfigurizer' => $params
			)
		));
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = $strategy::applyStrategies('write', 'default', null);
		$this->assertEqual($params, $result);
	}

	public function testApplyMultipleStrategies() {
		$strategy = new MockAdaptable();
		$items = array('default' => array(
			'filters' => array(),
			'adapter' => null,
			'strategies' => array(
				'lithium\tests\mocks\storage\cache\strategy\MockSerializer', 'Base64'
			)
		));
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$data = array('some' => 'data');
		$result = $strategy::applyStrategies('write', 'default', $data);
		$transformed = base64_encode(serialize($data));
		$this->assertEqual($transformed, $result);

		$options = array('mode' => 'LIFO');
		$result = $strategy::applyStrategies('read', 'default', $transformed, $options);
		$expected = $data;
		$this->assertEqual($expected, $result);
	}

	public function testApplyStrategiesNoConfiguredStrategies() {
		$strategy = new MockAdaptable();

		$items = array('default' => array(
			'filters' => array(),
			'adapter' => null
		));
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = $strategy::applyStrategies('method', 'default', null);
		$this->assertNull($result);

		$items = array('default' => array(
			'filters' => array(),
			'adapter' => null,
			'strategies' => array()
		));
		$strategy::config($items);
		$result = $strategy::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$data = array('some' => 'data');
		$result = $strategy::applyStrategies('method', 'default', $data);
		$this->assertEqual($data, $result);
	}

	public function testEnabled() {
		$items = array('default' => array('adapter' => 'Memory', 'filters' => array()));
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
		$items = array('default' => array('adapter' => 'NonExistent', 'filters' => array()));
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
		$config = array('adapter' => 'Memory', 'filters' => array());
		$items = array('default' => array(
			'development' => $config, 'test' => $config, 'production' => $config
		));
		MockAdaptable::config($items);
		$result = MockAdaptable::config();
		$expected = array('default' => $config);
		$this->assertEqual($expected, $result);

		$result = MockAdaptable::config('default');
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = MockAdaptable::adapter('default');
		$expected = new Memory($config);
		$this->assertEqual($expected, $result);
	}

	public function testConfigurationNoAdapter() {
		$items = array('default' => array('filters' => array()));
		MockAdaptable::config($items);

		$message  = 'No adapter set for configuration in ';
		$message .= 'class `lithium\tests\mocks\core\MockAdaptable`.';
		$this->assertException($message, function() {
			MockAdaptable::adapter('default');
		});
	}

	public function testNotCreateCacheWhenTestingEnabled() {
		MockAdaptable::config(array(
			'default' => array(
				array('adapter' => 'Memory')
			)
		));
		MockAdaptable::enabled('default');
		$this->assertFalse(MockAdaptable::testInitialized('default'));
	}
}

?>