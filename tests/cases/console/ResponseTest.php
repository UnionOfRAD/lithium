<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console;

use lithium\core\Libraries;
use lithium\console\Response;

class ResponseTest extends \lithium\test\Unit {

	public $streams;

	public function setUp() {
		$this->streams = array(
			'output' => Libraries::get(true, 'resources') . '/tmp/tests/output.txt',
			'error' => Libraries::get(true, 'resources') . '/tmp/tests/error.txt'
		);
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

		$response = new Response(array(
			'output' => $stream
		));
		$this->assertInternalType('resource', $response->output);
		$this->assertEqual($stream, $response->output);

	}

	public function testConstructWithConfigError() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		$stream = fopen($this->streams['error'], 'w');
		$response = new Response(array('error' => $stream));
		$this->assertInternalType('resource', $response->error);
		$this->assertEqual($stream, $response->error);
	}

	public function testOutput() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		$response = new Response(array('output' => fopen($this->streams['output'], 'w+')));
		$this->assertInternalType('resource', $response->output);

		$this->assertEqual(2, $response->output('ok'));
		$this->assertEqual('ok', file_get_contents($this->streams['output']));
	}

	public function testStyledOutput() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		$response = new Response(array('output' => fopen($this->streams['output'], 'w+')));
		$response->styles(array('heading' => "\033[1;36m"));
		$response->output('{:heading}ok');
		$this->assertEqual("\033[1;36mok", file_get_contents($this->streams['output']));
	}

	public function testError() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		$response = new Response(array('error' => fopen($this->streams['error'], 'w+')));
		$this->assertInternalType('resource', $response->error);
		$this->assertEqual(2, $response->error('ok'));
		$this->assertEqual('ok', file_get_contents($this->streams['error']));
	}
}

?>