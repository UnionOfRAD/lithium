<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console;

use \lithium\console\Dispatcher;
use \lithium\console\Request;

class DispatcherTest extends \lithium\test\Unit {

	public function setUp() {
		$this->server = $_SERVER;
		$_SERVER['argv'] = array();
	}

	public function tearDown() {
		$_SERVER = $this->server;
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
		$expected = 'test run';
		$result = Dispatcher::run();
		$this->assertEqual($expected, $result);
	}

	public function testRunWithCommand() {
		$result = Dispatcher::run(new Request(array(
			'args' => array(
				'\lithium\tests\mocks\console\MockDispatcherCommand'
			)
		)));
		$expected = 'test run';
		$this->assertEqual($expected, $result);
	}

	public function testRunWithPassed() {
		$result = Dispatcher::run(new Request(array(
			'args' => array(
				'\lithium\tests\mocks\console\MockDispatcherCommand',
				' with param'
			)
		)));
		$expected = 'test run with param';
		$this->assertEqual($expected, $result);
	}

	public function testRunWithAction() {
		$result = Dispatcher::run(new Request(array(
			'args' => array(
				'\lithium\tests\mocks\console\MockDispatcherCommand',
				'testAction'
			)
		)));
		$expected = 'test action';
		$this->assertEqual($expected, $result);
	}

	public function testInvalidCommand() {
		ob_start();
		$result = Dispatcher::run(new Request(array(
			'args' => array(
				'\this\command\is\fake',
				'testAction'
			)
		)));

		$expected = "Command `\\this\\command\\is\\fake` not found\n";
		$result = ob_get_clean();
		$this->assertEqual($expected, $result);
	}
}
?>
