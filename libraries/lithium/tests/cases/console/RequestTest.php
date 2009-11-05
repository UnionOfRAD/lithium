<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console;

use \lithium\console\Request;

class RequestTest extends \lithium\test\Unit {

	public function setUp() {
		$this->streams = array(
			'input' => LITHIUM_APP_PATH . '/tmp/input.txt',
		);

		$this->working = LITHIUM_APP_PATH;
		if (!empty($_SERVER['PWD'])) {
			$this->working = $_SERVER['PWD'];
		}
		$this->server = $_SERVER;
		$_SERVER['argv'] = array();
	}

	public function tearDown() {
		foreach ($this->streams as $path) {
			if (file_exists($path)) {
				unlink($path);
			}
		}
		$_SERVER = $this->server;
	}

	public function testConstructWithoutConfig() {
		$request = new Request();
		$expected = array();
		$this->assertEqual($expected, $request->args);

		$expected = array('working' => $this->working);
		$this->assertEqual($expected, $request->env);
	}

	public function testConstructWithServer() {
		$_SERVER['PWD'] = '/path/to/console';
		$request = new Request();
		$expected = array();
		$this->assertEqual($expected, $request->args);

		$expected = array('working' => '/path/to/console');
		$this->assertEqual($expected, $request->env);

		$_SERVER['argv'] = array('one', 'two');
		$request = new Request();
		$expected = array('one', 'two');
		$this->assertEqual($expected, $request->args);

		$expected = array('working' => '/path/to/console');
		$this->assertEqual($expected, $request->env);
	}

	public function testConstructWithConfigArgv() {
		$request = new Request(array(
			'argv' => array('wrong')
		));
		$expected = array('wrong');
		$this->assertEqual($expected, $request->args);

		$expected = array('working' => $this->working);
		$this->assertEqual($expected, $request->env);

		$request = new Request(array(
			'argv' => array('lithium.php', '-working', '/path/to/console', 'one', 'two')
		));

		$expected = array('one', 'two');
		$this->assertEqual($expected, $request->args);

		$expected = array('working' => '/path/to/console');
		$this->assertEqual($expected, $request->env);
	}

	public function testConstructWithConfigArgs() {
		$request = new Request(array(
			'args' => array('ok')
		));
		$expected = array('ok');
		$this->assertEqual($expected, $request->args);

		$request = new Request(array(
			'args' => array('ok'),
			'argv' => array('one', 'two', 'three', 'four')
		));
		$expected = array('ok', 'two', 'three', 'four');
		$this->assertEqual($expected, $request->args);

		$expected = array('working' => $this->working);
		$this->assertEqual($expected, $request->env);
	}

	public function testConstructWithEnv() {
		$_SERVER['PWD'] = '/dont/use/this/';
		$request = new Request(array(
			'env' => array('working' => '/some/other/path')
		));

		$expected = array('working' => '/some/other/path');
		$this->assertEqual($expected, $request->env);
	}

	public function testInput() {
		$stream = fopen($this->streams['input'], 'w+');
		$request = new Request(array(
			'input' => $stream
		));
		$this->assertTrue(is_resource($request->input));
		$this->assertEqual($stream, $request->input);


		$expected = 2;
		$result = fwrite($request->input, 'ok');
		$this->assertEqual($expected, $result);
		rewind($request->input);

		$expected = 'ok';
		$result = $request->input();
		$this->assertEqual($expected, $result);
	}
}
?>