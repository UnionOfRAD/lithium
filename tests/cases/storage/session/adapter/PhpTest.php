<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\session\adapter;

use lithium\core\Libraries;
use lithium\storage\session\adapter\Php;
use lithium\tests\mocks\storage\session\adapter\MockPhp;

class PhpTest extends \lithium\test\Unit {

	protected $_session;

	protected $_gc_divisor;

	public $php;

	public function setUp() {
		$this->_session = isset($_SESSION) ? $_SESSION : [];
		$this->_destroySession();

		$this->php = new Php();
		$this->_destroySession();

		$this->_gc_divisor = ini_get('session.gc_divisor');
		ini_set('session.gc_divisor', '1');
	}

	public function tearDown() {
		$this->_destroySession();

		ini_set('session.gc_divisor', $this->_gc_divisor);
		$_SESSION = $this->_session;
	}

	protected function _destroySession($name = null) {
		if (!$name) {
			$name = session_name();
		}
		$settings = session_get_cookie_params();
		setcookie(
			$name, '', time() - 1000, $settings['path'], $settings['domain'],
			$settings['secure'], $settings['httponly']
		);
		if (session_id()) {
			session_destroy();
		}
		$_SESSION = [];
	}

	public function testEnabled() {
		$php = $this->php;
		/* Is PHP Session support enabled? */
		$sessionsSupported = in_array('session', get_loaded_extensions());
		$this->assertEqual($sessionsSupported, $php::enabled());
	}

	public function testInit() {
		$id = session_id();
		$this->assertEmpty($id);

		$result = ini_get('session.name');
		$this->assertEqual(basename(Libraries::get(true, 'path')), $result);

		$result = ini_get('session.cookie_lifetime');
		$this->assertEqual(0, (integer) $result);

		$result = ini_get('session.cookie_httponly');
		$this->assertNotEmpty((integer) $result);

		$name = 'this-is-a-custom-name';
		$php = new MockPhp(['session.name' => $name]);
		$this->assertNotInternalType('numeric', $php->config()['session.name']);
	}

	public function testCustomConfiguration() {
		$config = [
			'session.name' => 'awesome_name', 'session.cookie_lifetime' => 1200,
			'session.cookie_domain' => 'awesome.domain',
			'session.save_path' => Libraries::get(true, 'resources') . '/tmp/',
			'somebad.configuration' => 'whoops'
		];

		$adapter = new Php($config);

		$result = ini_get('session.name');
		$this->assertEqual($config['session.name'], $result);

		$result = ini_get('session.cookie_lifetime');
		$this->assertEqual($config['session.cookie_lifetime'], (integer) $result);

		$result = ini_get('session.cookie_domain');
		$this->assertEqual($config['session.cookie_domain'], $result);

		$result = ini_get('session.cookie_secure');
		$this->assertEmpty($result);

		$result = ini_get('session.cookie_httponly');
		$this->assertNotEmpty($result);

		$result = ini_get('session.save_path');
		$this->assertEqual($config['session.save_path'], $result);

		$result = ini_get('somebad.configuration');
		$this->assertFalse($result);
	}

	public function testIsStarted() {
		$result = $this->php->isStarted();
		$this->assertFalse($result);

		$this->php->read();

		$result = $this->php->isStarted();
		$this->assertTrue($result);

		$this->_destroySession(session_name());
		$result = $this->php->isStarted();
		$this->assertFalse($result);
	}

	public function testIsStartedNoInit() {
		$this->_destroySession(session_name());

		$php = new Php(['init' => false]);
		$result = $php->isStarted();
		$this->assertFalse($result);

		$php = new Php();
		$php->read();
		$result = $php->isStarted();
		$this->assertTrue($result);
	}

	public function testKey() {
		$result = $this->php->key();
		$this->assertEqual(session_id(), $result);

		$this->_destroySession(session_name());
		$result = $this->php->key();
		$this->assertNull($result);
	}

	public function testWrite() {
		$key = 'write-test';
		$value = 'value to be written';

		$closure = $this->php->write($key, $value);
		$this->assertInternalType('callable', $closure);

		$params = compact('key', 'value');
		$result = $closure($params, null);

		$this->assertEqual($_SESSION[$key], $value);
	}

	public function testRead() {
		$this->php->read();

		$key = 'read_test';
		$value = 'value to be read';

		$_SESSION[$key] = $value;

		$closure = $this->php->read($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);

		$this->assertIdentical($value, $result);

		$key = 'non-existent';
		$closure = $this->php->read($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertNull($result);

		$closure = $this->php->read();
		$this->assertInternalType('callable', $closure);

		$result = $closure(['key' => null], null);
		$expected = ['read_test' => 'value to be read'];
		$this->assertEqual($expected, $result);
	}

	public function testCheck() {
		$this->php->read();

		$key = 'read';
		$value = 'value to be read';
		$_SESSION[$key] = $value;

		$closure = $this->php->check($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);

		$key = 'does_not_exist';
		$closure = $this->php->check($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertFalse($result);
	}

	public function testDelete() {
		$this->php->read();

		$key = 'delete_test';
		$value = 'value to be deleted';

		$_SESSION[$key] = $value;

		$closure = $this->php->delete($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);

		$key = 'non-existent';
		$closure = $this->php->delete($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);
	}

	/**
	 * Checks if erasing the whole session array works as expected.
	 */
	public function testClear() {
		$_SESSION['foo'] = 'bar';
		$this->assertNotEmpty($_SESSION);
		$closure = $this->php->clear();
		$this->assertInternalType('callable', $closure);
		$result = $closure([], null);
		$this->assertEmpty($_SESSION);
	}

	public function testCheckThrowException() {
		$php = new MockPhp(['init' => false]);
		$this->assertException('/Could not start session./', function() use ($php) {
			$php->check('whatever');
		});
	}

	public function testReadThrowException() {
		$php = new MockPhp(['init' => false]);
		$this->assertException('/Could not start session./', function() use ($php) {
			$php->read('whatever');
		});
	}

	public function testWriteThrowException() {
		$php = new MockPhp(['init' => false]);
		$this->assertException('/Could not start session./', function() use ($php) {
			$php->write('whatever', 'value');
		});
	}

	public function testDeleteThrowException() {
		$php = new MockPhp(['init' => false]);
		$this->assertException('/Could not start session./', function() use ($php) {
			$php->delete('whatever');
		});
	}

	public function testReadDotSyntax() {
		$this->php->read();

		$key = 'dot';
		$value = ['syntax' => ['key' => 'value']];

		$_SESSION[$key] = $value;

		$closure = $this->php->read($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);

		$this->assertIdentical($value, $result);

		$params = ['key' => 'dot.syntax'];
		$result = $closure($params, null);

		$this->assertIdentical($value['syntax'], $result);
	}

	public function testWriteDotSyntax() {
		$key = 'dot.syntax';
		$value = 'value to be written';

		$closure = $this->php->write($key, $value);
		$this->assertInternalType('callable', $closure);

		$params = compact('key', 'value');
		$result = $closure($params, null);

		$this->assertEqual($_SESSION['dot']['syntax'], $value);
	}
}

?>