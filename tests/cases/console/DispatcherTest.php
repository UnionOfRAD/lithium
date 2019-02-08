<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\console;

use lithium\console\Dispatcher;
use lithium\console\Request;

class DispatcherTest extends \lithium\test\Unit {

	protected $_backup = [];

	public function setUp() {
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = [];
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
	}

	public function testEmptyConfigReturnRules() {
		$result = Dispatcher::config();
		$expected = ['rules' => [
			'command' => [['lithium\util\Inflector', 'camelize']],
			'action' => [['lithium\util\Inflector', 'camelize', [false]]]
		]];
		$this->assertEqual($expected, $result);
	}

	public function testConfigWithClasses() {
		Dispatcher::config([
			'classes' => ['request' => 'lithium\tests\mocks\console\MockDispatcherRequest']
		]);
		$expected = 'run';
		$result = Dispatcher::run()->testAction;
		$this->assertEqual($expected, $result);
	}

	public function testRunWithCommand() {
		$response = Dispatcher::run(new Request([
			'args' => ['lithium\tests\mocks\console\MockDispatcherCommand']
		]));
		$expected = 'run';
		$result = $response->testAction;
		$this->assertEqual($expected, $result);
	}

	public function testRunWithPassed() {
		$response = Dispatcher::run(new Request([
			'args' => ['lithium\tests\mocks\console\MockDispatcherCommand', 'with param']
		]));

		$expected = 'run';
		$result = $response->testAction;
		$this->assertEqual($expected, $result);

		$expected = 'with param';
		$result = $response->testParam;
		$this->assertEqual($expected, $result);
	}

	public function testRunWithAction() {
		$response = Dispatcher::run(new Request([
			'args' => ['lithium\tests\mocks\console\MockDispatcherCommand', 'testAction']
		]));
		$expected = 'testAction';
		$result = $response->testAction;
		$this->assertEqual($expected, $result);
	}

	public function testInvalidCommand() {
		$expected = (object) ['status' => "Command `\\this\\command\\is\\fake` not found.\n"];
		$result = Dispatcher::run(new Request([
			'args' => [
				'\this\command\is\fake',
				'testAction'
			]
		]));

		$this->assertEqual($expected, $result);
	}

	public function testRunWithCamelizingCommand() {
		$expected = (object) ['status' => "Command `FooBar` not found.\n"];
		$result = Dispatcher::run(new Request([
			'args' => [
				'foo-bar'
			]
		]));
		$this->assertEqual($expected, $result);

		$expected = (object) ['status' => "Command `FooBar` not found.\n"];
		$result = Dispatcher::run(new Request([
			'args' => ['foo_bar']
		]));
		$this->assertEqual($expected, $result);
	}

	public function testRunWithCamelizingAction() {
		$result = Dispatcher::run(new Request([
			'args' => [
				'lithium\tests\mocks\console\command\MockCommandHelp',
				'sample-task-with-optional-args'
			]
		]));
		$this->assertNotEmpty($result);

		$result = Dispatcher::run(new Request([
			'args' => [
				'lithium\tests\mocks\console\command\MockCommandHelp',
				'sample_task_with_optional_args'
			]
		]));
		$this->assertNotEmpty($result);
	}

	public function testEnvironmentIsSet() {
		$expected = 'production';
		$response = Dispatcher::run(new Request([
			'args' => [
				'lithium\tests\mocks\console\MockDispatcherCommand',
				'testEnv', '--env=production'
			]
		]));
		$result = $response->environment;
		$this->assertEqual($expected, $result);
	}
}

?>