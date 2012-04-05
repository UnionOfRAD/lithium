<?php

/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use lithium\core\Configurable;
use lithium\core\Environment;
use lithium\core\ConfigException;

class ConfigurableTest extends \lithium\test\Unit {

	protected $_config;
	protected $_instance;

	public function skip(){
		$this->_instance = new Configurable();
	}

	public function setup(){
		$this->_config = array('content' => array(
				'development' => array(
					'var1' => 'devel_value1',
					'var2' => 'devel_value2'
				),
				'test' => array(
					'var1' => 'test_value1',
					'var2' => 'test_value2'
				),
				'production' => array(
					'var1' => 'prod_value1',
					'var2' => 'prod_value2'
				)
			),
			'content2' => array(
				'development' => array(
					'var3' => 'devel_value3',
					'var4' => 'devel_value4'
				),
				'test' => array(
					'var3' => 'test_value3',
					'var4' => 'test_value4'
				),
				'production' => array(
					'var3' => 'prod_value3',
					'var4' => 'prod_value4'
				)
		));
	}
	public function tearDown(){
		$this->_instance->reset();
		Environment::reset();
	}

	public function testDevelopmentEnvironmentConfiguration() {
		$this->_instance->set('content', $this->_config['content']);
		$this->_instance->set('content2', $this->_config['content2']);

		Environment::set('development');
		$result = $this->_instance->get();
		$expected = array(
			'content' => $this->_config['content']['development'],
			'content2' => $this->_config['content2']['development']
		);

		$result = $this->_instance->get('content');
		$expected = $this->_config['content']['development'];
		$this->assertEqual($expected, $result);

		$result = $this->_instance->get('content2');
		$expected = $this->_config['content2']['development'];
		$this->assertEqual($expected, $result);
	}

	public function testProductionEnvironmentConfiguration() {
		$this->_instance->set('content', $this->_config['content']);
		$this->_instance->set('content2', $this->_config['content2']);

		Environment::set('production');
		$result = $this->_instance->get();
		$expected = array(
			'content' => $this->_config['content']['production'],
			'content2' => $this->_config['content2']['production']
		);
		$this->assertEqual($expected, $result);
	}
	
	public function testTestEnvironmentConfiguration() {
		$this->_instance->set('content', $this->_config['content']);
		$this->_instance->set('content2', $this->_config['content2']);

		Environment::set('test');
		$result = $this->_instance->get();
		$expected = array(
			'content' => $this->_config['content']['test'],
			'content2' => $this->_config['content2']['test']
		);
		$this->assertEqual($expected, $result);
	}

	public function testUndefinedConfiguration() {
		$result = $this->_instance->get('undefined');
		$this->assertNull($result);
	}

	public function testDeleteConfiguration() {
		$this->_instance->set('content', $this->_config['content']);
		$this->_instance->set('content', false);
		$this->assertNull($this->_instance->get('content'));
	}
}

?>
