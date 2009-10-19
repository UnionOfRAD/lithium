<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace lithium\tests\cases\console;

use \lithium\console\Dispatcher;
use \lithium\console\Request;

class TestCommandForDispatcherTest extends \lithium\console\Command {

	public function run($param = null) {
		return 'test run' . $param;
	}

	public function testAction() {
		return 'test action';
	}
}

class TestRequestForDispatcherTest extends \lithium\console\Request {

	public $params = array(
		'command' => '\lithium\tests\cases\console\TestCommandForDispatcherTest'
	);
}

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
				'request' => '\lithium\tests\cases\console\TestRequestForDispatcherTest'
			)
		));
		$expected = 'test run';
		$result = Dispatcher::run();
		$this->assertEqual($expected, $result);
	}

	public function testRunWithCommand() {
		$result = Dispatcher::run(new Request(array(
			'args' => array(
				'\lithium\tests\cases\console\TestCommandForDispatcherTest'
			)
		)));
		$expected = 'test run';
		$this->assertEqual($expected, $result);
	}

	public function testRunWithPassed() {
		$result = Dispatcher::run(new Request(array(
			'args' => array(
				'\lithium\tests\cases\console\TestCommandForDispatcherTest',
				' with param'
			)
		)));
		$expected = 'test run with param';
		$this->assertEqual($expected, $result);
	}

	public function testRunWithAction() {
		$result = Dispatcher::run(new Request(array(
			'args' => array(
				'\lithium\tests\cases\console\TestCommandForDispatcherTest',
				'testAction'
			)
		)));
		$expected = 'test action';
		$this->assertEqual($expected, $result);
	}
}
?>