<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use \lithium\util\Collection;
use \lithium\core\Adaptable;
use \lithium\storage\cache\adapter\Memory;
use \lithium\tests\mocks\core\MockAdapter;

class AdaptableTest extends \lithium\test\Unit {


	public function setUp() {
		$this->adaptable = new Adaptable();
	}

	public function testConfig() {
		$this->assertFalse($this->adaptable->config());

		$items = array(array(
			'adapter' => '\some\adapter',
			'filters' => array('filter1', 'filter2')
		));
		$result = $this->adaptable->config($items);
		$this->assertNull($result);

		$expected = $items;
		$result = $this->adaptable->config();
		$this->assertEqual($expected, $result);

		$items = array(array(
			'adapter' => '\some\adapter',
			'filters' => array('filter1', 'filter2')
		));
		$this->adaptable->config($items);
		$result = $this->adaptable->config();
		$expected = $items;
		$this->assertEqual($expected, $result);
	}

	public function testReset() {
		$items = array(array(
			'adapter' => '\some\adapter',
			'filters' => array('filter1', 'filter2')
		));
		$this->adaptable->config($items);
		$result = $this->adaptable->config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = $this->adaptable->reset();
		$this->assertNull($result);
		$this->assertFalse($this->adaptable->config());
	}

	public function testNonExistentConfig() {
		$adapter = new MockAdapter();
		$this->expectException('Adapter configuration non_existent_config has not been defined');
		$result = $adapter::adapter('non_existent_config');
		$this->assertNull($result);
	}

	public function testAdapter() {
		$adapter = new MockAdapter();
		$items = array('default' => array(
			'adapter' => 'Memory',
			'filters' => array()
		));
		$adapter::config($items);
		$result = $adapter::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = $adapter::adapter('default');
		$expected = new Memory($items['default']);
		$this->assertEqual($expected, $result);
	}

	public function testEnabled() {
		$adapter = new MockAdapter();

		$items = array('default' => array(
			'adapter' => 'Memory',
			'filters' => array()
		));
		$adapter::config($items);
		$result = $adapter::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$result = $adapter::adapter('default');
		$expected = new Memory($items['default']);
		$this->assertEqual($expected, $result);

		$this->assertTrue($adapter::enabled('default'));
		$this->assertNull($adapter::enabled('non-existent'));
	}

	public function testNonExistentAdapter() {
		$adapter = new MockAdapter();

		$items = array('default' => array(
			'adapter' => 'NonExistent', 'filters' => array()
		));
		$adapter::config($items);
		$result = $adapter::config();
		$expected = $items;
		$this->assertEqual($expected, $result);

		$this->expectException(
			'Could not find adapter NonExistent in class lithium\tests\mocks\core\MockAdapter'
		);
		$result = $adapter::adapter('default');
		$this->assertNull($result);
	}

	public function testEnvironmentSpecificConfiguration() {
		$adapter = new MockAdapter();
		$config = array('adapter' => 'Memory', 'filters' => array());
		$items = array('default' => array(
			'development' => $config, 'test' => $config, 'production' => $config
		));
		$adapter::config($items);
		$result = $adapter::config();
		$expected = array('default' => $config);
		$this->assertEqual($expected, $result);

		$result = $adapter::config('default');
		$expected = $config;
		$this->assertEqual($expected, $result);

		$result = $adapter::adapter('default');
		$expected = new Memory($config);
		$this->assertEqual($expected, $result);
	}

	public function testConfigurationNoAdapter() {
		$adapter = new MockAdapter();
		$items = array('default' => array('filters' => array()));
		$adapter::config($items);
		$this->expectException(
			'No adapter set for configuration in class lithium\tests\mocks\core\MockAdapter'
		);
		$result = $adapter::adapter('default');
	}
}

?>