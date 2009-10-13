<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console;

use \lithium\console\Response;
use \lithium\console\Request;

class ResponseTest extends \lithium\test\Unit {

	public function setUp() {
		$this->streams = array(
			'output' => LITHIUM_APP_PATH . '/tmp/output.txt',
			'error' => LITHIUM_APP_PATH . '/tmp/error.txt'
		);

		$this->working = LITHIUM_APP_PATH;
		if (!empty($_SERVER['PWD'])) {
			$this->working = $_SERVER['PWD'];
		}
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
		$expected = null;
		$this->assertEqual($expected, $response->request);

		$this->assertTrue(is_resource($response->output));

		$this->assertTrue(is_resource($response->error));
	}

	public function testConstructWithConfigRequest() {
		$response = new Response(array(
			'request' => new Request()
		));
		$expected = new Request();
		$this->assertEqual($expected->env, $response->request->env);

		$expected = array('working' => $this->working);
		$this->assertEqual($expected, $response->request->env);
	}

	public function testConstructWithConfigOutput() {
		$stream = fopen($this->streams['output'], 'w');
		$response = new Response(array(
			'output' => $stream
		));
		$this->assertTrue(is_resource($response->output));
		$this->assertEqual($stream, $response->output);

	}

	public function testConstructWithConfigErrror() {
		$stream = fopen($this->streams['error'], 'w');
		$response = new Response(array(
			'error' => $stream
		));
		$this->assertTrue(is_resource($response->error));
		$this->assertEqual($stream, $response->error);

	}

	public function testOutput() {
		$response = new Response(array(
			'output' => fopen($this->streams['output'], 'w+')
		));
		$this->assertTrue(is_resource($response->output));

		$expected = 2;
		$result = $response->output('ok');
		$this->assertEqual($expected, $result);

		$expected = 'ok';
		$result = file_get_contents($this->streams['output']);
		$this->assertEqual($expected, $result);
	}

	public function testError() {
		$response = new Response(array(
			'error' => fopen($this->streams['error'], 'w+')
		));
		$this->assertTrue(is_resource($response->error));

		$expected = 2;
		$result = $response->error('ok');
		$this->assertEqual($expected, $result);

		$expected = 'ok';
		$result = file_get_contents($this->streams['error']);
		$this->assertEqual($expected, $result);
	}
}

?>