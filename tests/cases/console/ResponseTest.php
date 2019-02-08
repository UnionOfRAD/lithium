<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\console;

use lithium\core\Libraries;
use lithium\console\Response;

class ResponseTest extends \lithium\test\Unit {

	public $streams;

	public function setUp() {
		$this->streams = [
			'output' => Libraries::get(true, 'resources') . '/tmp/tests/output.txt',
			'error' => Libraries::get(true, 'resources') . '/tmp/tests/error.txt'
		];
	}

	public function tearDown() {
		foreach ($this->streams as $path) {
			if (file_exists($path)) {
				unlink($path);
			}
		}
	}

	public function testConstructWithoutConfig() {
		$response = new Response();
		$this->assertInternalType('resource', $response->output);
		$this->assertInternalType('resource', $response->error);
	}

	public function testConstructWithConfigOutput() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");
		$stream = fopen($this->streams['output'], 'w');

		$response = new Response([
			'output' => $stream
		]);
		$this->assertInternalType('resource', $response->output);
		$this->assertEqual($stream, $response->output);

	}

	public function testConstructWithConfigError() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		$stream = fopen($this->streams['error'], 'w');
		$response = new Response(['error' => $stream]);
		$this->assertInternalType('resource', $response->error);
		$this->assertEqual($stream, $response->error);
	}

	public function testOutput() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		$response = new Response(['output' => fopen($this->streams['output'], 'w+')]);
		$this->assertInternalType('resource', $response->output);

		$this->assertEqual(2, $response->output('ok'));
		$this->assertEqual('ok', file_get_contents($this->streams['output']));
	}

	public function testStyledOutput() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		$response = new Response(['output' => fopen($this->streams['output'], 'w+')]);
		$response->styles(['heading' => "\033[1;36m"]);
		$response->output('{:heading}ok');

		$expected = "\033[1;36mok";
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$expected = 'ok';
		}
		$this->assertEqual($expected, file_get_contents($this->streams['output']));
	}

	public function testError() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		$response = new Response(['error' => fopen($this->streams['error'], 'w+')]);
		$this->assertInternalType('resource', $response->error);
		$this->assertEqual(2, $response->error('ok'));
		$this->assertEqual('ok', file_get_contents($this->streams['error']));
	}
}

?>