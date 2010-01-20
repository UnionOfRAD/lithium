<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console;

use \lithium\console\Dispatcher;
use \lithium\console\Request;

class DispatcherTest extends \lithium\test\Unit {

	protected $_backups = array();

	public function setUp() {
		$this->_backups['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = array();
	}

	public function tearDown() {
		$_SERVER = $this->_backups['_SERVER'];
	}

	public function testEmptyConfigReturnRules() {
		$result = Dispatcher::config();
		$expected = array('rules' => array());
		$this->assertEqual($expected, $result);
	}

	public function testConfigWithClasses() {
		Dispatcher::config(array(
			'classes' => array(
				'request' => '\lithium\tests\mocks\console\MockDispatcherRequest'
			)
		));
		$expected = 'run';
		$result = Dispatcher::run()->testAction;
		$this->assertEqual($expected, $result);
	}

	public function testRunWithCommand() {
		$response = Dispatcher::run(new Request(array(
			'args' => array(
				'\lithium\tests\mocks\console\MockDispatcherCommand'
			)
		)));
		$expected = 'run';
		$result = $response->testAction;
		$this->assertEqual($expected, $result);
	}

	public function testRunWithPassed() {
		$response = Dispatcher::run(new Request(array(
			'args' => array(
				'\lithium\tests\mocks\console\MockDispatcherCommand',
				'with param'
			)
		)));

		$expected = 'run';
		$result = $response->testAction;
		$this->assertEqual($expected, $result);

		$expected = 'with param';
		$result = $response->testParam;
		$this->assertEqual($expected, $result);
	}

	public function testRunWithAction() {
		$response = Dispatcher::run(new Request(array(
			'args' => array(
				'\lithium\tests\mocks\console\MockDispatcherCommand',
				'testAction'
			)
		)));
		$expected = 'testAction';
		$result = $response->testAction;
		$this->assertEqual($expected, $result);
	}

	public function testInvalidCommand() {
		$expected = (object) array('status' => "Command `\\this\\command\\is\\fake` not found\n");
		$result = Dispatcher::run(new Request(array(
			'args' => array(
				'\this\command\is\fake',
				'testAction'
			)
		)));

		$this->assertEqual($expected, $result);
	}
}

?>