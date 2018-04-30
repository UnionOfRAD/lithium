<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\console;

use lithium\core\Libraries;
use lithium\console\Request;

class RequestTest extends \lithium\test\Unit {

	public $streams;

	protected $_backup = [];

	public function setUp() {
		$this->streams = [
			'input' => Libraries::get(true, 'resources') . '/tmp/tests/input.txt'
		];

		$this->_backup['cwd'] = str_replace('\\', '/', getcwd()) ?: null;
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = [];
	}

	public function tearDown() {
		foreach ($this->streams as $path) {
			if (file_exists($path)) {
				unlink($path);
			}
		}
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
	}

	public function testConstructWithoutConfig() {
		$request = new Request();

		$expected = [];
		$result = $request->args;
		$this->assertEqual($expected, $result);

		$result = $request->env();
		$this->assertNotEmpty($result);

		$expected = $this->_backup['cwd'];
		$result = $result['working'];
		$this->assertEqual($expected, $result);
	}

	public function testEnvWorking() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_readable($base), "Path `{$base}` is not readable.");

		chdir(Libraries::get(true, 'resources') . '/tmp/tests');
		$request = new Request();

		$expected = str_replace('\\', '/', realpath(Libraries::get(true, 'resources') . '/tmp/tests'));
		$result = $request->env('working');
		$this->assertEqual($expected, $result);
	}

	public function testConstructWithServer() {
		$_SERVER['argv'] = ['/path/to/lithium.php', 'one', 'two'];
		$request = new Request();

		$expected = '/path/to/lithium.php';
		$result = $request->env('script');
		$this->assertEqual($expected, $result);

		$expected = ['one', 'two'];
		$result = $request->argv;
		$this->assertEqual($expected, $result);
	}

	public function testConstructWithConfigArgv() {
		$request = new Request(['args' => ['/path/to/lithium.php', 'wrong']]);

		$expected = ['/path/to/lithium.php', 'wrong'];
		$result = $request->argv;
		$this->assertEqual($expected, $result);

		$_SERVER['argv'] = ['/path/to/lithium.php'];
		$request = new Request(['args' => ['one', 'two']]);

		$expected = '/path/to/lithium.php';
		$result = $request->env('script');
		$this->assertEqual($expected, $result);

		$expected = ['one', 'two'];
		$result = $request->argv;
		$this->assertEqual($expected, $result);
	}

	public function testConstructWithConfigArgs() {
		$request = new Request([
			'args' => ['ok']
		]);
		$expected = ['ok'];
		$this->assertEqual($expected, $request->argv);

		$request = new Request([
			'env' => [
				'argv' => [
					'/path/to/lithium.php',
					'one', 'two', 'three', 'four'
				]
			],
			'globals' => false
		]);

		$expected = '/path/to/lithium.php';
		$result = $request->env('script');
		$this->assertEqual($expected, $result);

		$expected = ['one', 'two', 'three', 'four'];
		$this->assertEqual($expected, $request->argv);
	}

	public function testConstructWithEnv() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_readable($base), "Path `{$base}` is not readable.");

		chdir(Libraries::get(true, 'resources') . '/tmp');
		$request = new Request(['env' => ['working' => '/some/other/path']]);

		$expected = '/some/other/path';
		$result = $request->env('working');
		$this->assertEqual($expected, $result);
	}

	public function testInput() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "{$base} is not writable.");

		$stream = fopen($this->streams['input'], 'w+');
		$request = new Request(['input' => $stream]);
		$this->assertInternalType('resource', $request->input);
		$this->assertEqual($stream, $request->input);

		$this->assertEqual(2, fwrite($request->input, 'ok'));
		rewind($request->input);

		$this->assertEqual('ok', $request->input());
	}

	public function testArgs() {
		$request = new Request();
		$request->params = [
			'command' => 'one', 'action' => 'two', 'args' => ['three', 'four', 'five']
		];
		$this->assertEqual('five', $request->args(2));
	}

	public function testShiftDefaultOne() {
		$request = new Request();
		$request->params = [
			'command' => 'one', 'action' => 'two',
			'args' => ['three', 'four', 'five']
		];
		$request->shift();

		$expected = ['command' => 'two', 'action' => 'three', 'args' => ['four', 'five']];
		$this->assertEqual($expected, $request->params);
	}

	public function testShiftTwo() {
		$request = new Request();
		$request->params = [
			'command' => 'one', 'action' => 'two',
			'args' => ['three', 'four', 'five']
		];
		$request->shift(2);

		$expected = ['command' => 'three', 'action' => 'four', 'args' => ['five']];
		$result = $request->params;
		$this->assertEqual($expected, $result);
	}

	public function testTemporaryFileStructureExists() {
		$resources = Libraries::get(true, 'resources');
		$template = $resources . '/tmp/cache/templates/';
		$this->assertFileExists($template);
	}
}

?>