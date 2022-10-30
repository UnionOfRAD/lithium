<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\console;

use lithium\console\Request;
use lithium\tests\mocks\console\MockCommand;

class CommandTest extends \lithium\test\Unit {

	public $request;

	public $classes;

	public function setUp() {
		$this->request = new Request(['input' => fopen('php://temp', 'w+')]);
		$this->classes = ['response' => 'lithium\tests\mocks\console\MockResponse'];
	}

	public function testConstruct() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = $this->request->env();
		$result = $command->request->env();
		$this->assertEqual($expected, $result);

		$this->request->params = [
			'case' => 'lithium.tests.cases.console.CommandTest'
		];
		$command = new MockCommand(['request' => $this->request]);
		$this->assertNotEmpty($command->case);
	}

	public function testInvoke() {
		$command = new MockCommand(['request' => $this->request]);
		$response = $command('testRun');

		$result = $response;
		$this->assertInstanceOf('lithium\console\Response', $result);

		$expected = 'testRun';
		$result = $response->testAction;
		$this->assertEqual($expected, $result);
	}

	public function testInvokeSettingResponseStatus() {
		$command = new MockCommand(['request' => $this->request]);

		$expected = 0;
		$result = $command('testReturnNull')->status;
		$this->assertEqual($expected, $result);

		$expected = 0;
		$result = $command('testReturnTrue')->status;
		$this->assertEqual($expected, $result);

		$expected = 1;
		$result = $command('testReturnFalse')->status;
		$this->assertEqual($expected, $result);

		$expected = -1;
		$result = $command('testReturnNegative1')->status;
		$this->assertEqual($expected, $result);

		$expected = 1;
		$result = $command('testReturn1')->status;
		$this->assertEqual($expected, $result);

		$expected = 3;
		$result = $command('testReturn3')->status;
		$this->assertEqual($expected, $result);

		$expected = 0;
		$result = $command('testReturnString');
		$this->assertEqual($expected, $result->status);

		$expected = 1;
		$result = $command('testReturnEmptyArray')->status;
		$this->assertEqual($expected, $result);

		$expected = 0;
		$result = $command('testReturnArray')->status;
		$this->assertEqual($expected, $result);
	}

	public function testOut() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = "ok\n";
		$result = $command->out('ok');
		$this->assertEqual($expected, $result);
	}

	public function testOutArray() {
		$command = new MockCommand(['request' => $this->request]);

		$expected = "line 1\nline 2\n";
		$command->out(['line 1', 'line 2']);
		$result = $command->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testError() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = "ok\n";
		$result = $command->error('ok');
		$this->assertEqual($expected, $result);
	}

	public function testErrorArray() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = "line 1\nline 2\n";
		$command->error(['line 1', 'line 2']);
		$result = $command->response->error;
		$this->assertEqual($expected, $result);
	}

	public function testNl() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = "\n\n\n";
		$result = $command->nl(3);
		$this->assertEqual($expected, $result);
	}

	public function testHr() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = "----\n";
		$command->hr(4);
		$result = $command->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testHeader() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = "----\nheader\n----\n";
		$command->header('header', 4);
		$result = $command->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testColumns() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = "data1\t\ndata2\t\n";
		$command->columns(['col1' => 'data1', 'col2' => 'data2']);
		$result = $command->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testHelp() {
		$command = new MockCommand(['request' => $this->request]);
		$return = $command->__invoke('_help');

		$this->assertInstanceOf('lithium\tests\mocks\console\MockResponse', $return);

		$expected = "DESCRIPTION.*This is the Mock Command";
		$result = $command->response->output;
		$this->assertPattern("/{$expected}/s", $result);

		$command = new MockCommand(['request' => $this->request]);
		$return = $command->__invoke('_help');

		$expected = "testRun";
		$result = $command->response->output;
		$this->assertPattern("/{$expected}/m", $result);
	}

	public function testIn() {
		$command = new MockCommand(['request' => $this->request]);
		fwrite($command->request->input, 'nada mucho');
		rewind($command->request->input);

		$expected = "nada mucho";
		$result = $command->in('What up dog?');
		$this->assertEqual($expected, $result);

		$expected = "What up dog?  \n > ";
		$result = $command->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testQuit() {
		$command = new MockCommand(['request' => $this->request]);
		fwrite($command->request->input, "q\n");
		rewind($command->request->input);

		$result = $command->in('This should return bool false');
		$this->assertFalse($result);
	}

	public function testInWithDefaultOption() {
		$command = new MockCommand(['request' => $this->request]);
		fwrite($command->request->input, '  ');
		rewind($command->request->input);

		$expected = "y";
		$result = $command->in('What up dog?', ['default' => 'y']);
		$this->assertEqual($expected, $result);

		$expected = "What up dog?  \n [y] > ";
		$result = $command->response->output;
		$this->assertEqual($expected, $result);

		fwrite($command->request->input, "\n");
		fwrite($command->request->input, 'n');
		rewind($command->request->input);

		$expected = "y";
		$result = $command->in('R U Sure?', ['choices' => ['y', 'n'], 'default' => 'y']);
		$this->assertEqual($expected, $result);
	}

	public function testInWithOptions() {
		$command = new MockCommand(['request' => $this->request]);
		fwrite($command->request->input, 'y');
		rewind($command->request->input);

		$expected = "y";
		$result = $command->in('Everything Cool?', ['choices' => ['y', 'n']]);
		$this->assertEqual($expected, $result);

		$expected = "Everything Cool? (y/n) \n > ";
		$result = $command->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testInWithBadInput(){
		$command = new MockCommand(['request' => $this->request]);
		fwrite($command->request->input, "f\n");
		fwrite($command->request->input, 'y');
		rewind($command->request->input);

		$expected = "y";
		$result = $command->in('Everything Cool?', ['choices' => ['y', 'n']]);
		$this->assertEqual($expected, $result);
	}

	public function testOutWithStyles() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = "{:some-style}ok\n";
		$result = $command->out('ok', 'some-style');
		$this->assertEqual($expected, $result);
	}

	public function testOutWithSilent() {
		$command = new MockCommand(['request' => $this->request]);
		$command->silent = true;
		$expected = "";
		$result = $command->out('ok', 'some-style');
		$this->assertEqual($expected, $result);
	}

	public function testColumnsOnErrorOutput() {
		$command = new MockCommand(['request' => $this->request]);
		$expected = "data1\t\ndata2\t\n";
		$command->columns(['col1' => 'data1', 'col2' => 'data2'], ['error' => true]);
		$result = $command->response->error;
		$this->assertEqual($expected, $result);
	}
}

?>