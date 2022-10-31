<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\session\adapter;

use lithium\util\Inflector;
use lithium\storage\session\adapter\Cookie;
use lithium\core\Libraries;

class CookieTest extends \lithium\test\Unit {

	public $cookie;

	public $name = 'testcookie';

	/**
	 * Skip the test if running under CLI.
	 */
	public function skip() {
		$sapi = PHP_SAPI;
		$message = 'Cookie tests cannot be run via command-line interface.';
		$this->skipIf($sapi === 'cli', $message);
	}

	public function setUp() {
		$this->cookie = new Cookie(['name' => $this->name]);
	}

	public function tearDown() {
		$this->_destroyCookie($this->name);
		$cookies = array_keys($_COOKIE);

		foreach ($cookies as $cookie) {
			setcookie($cookie, "", time() - 1);
		}
	}

	protected function _destroyCookie($name = null) {
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
		foreach ($_COOKIE as $key => $val) {
			unset($_COOKIE[$key]);
		}
	}

	public function testEnabled() {
		$this->assertTrue($this->cookie->isEnabled());
	}

	public function testKey() {
		$this->assertEqual($this->name, $this->cookie->key());
	}

	public function testIsStarted() {
		$this->assertTrue($this->cookie->isStarted());
	}

	public function testWriteDefaultParameters() {
		$key = 'write';
		$value = 'value to be written';
		$expires = "+2 days";
		$path = '/';

		$closure = $this->cookie->write($key, $value);
		$this->assertInternalType('callable', $closure);

		$params = compact('key', 'value');
		$result = $closure($params);

		$this->assertCookie(compact('key', 'value', 'expires', 'path'));
	}

	public function testCustomCookieName() {
		$cookie = new Cookie(['name' => 'test']);
		$this->assertEqual('test', $cookie->key());
	}

	public function testWriteArrayData() {
		$key = 'user';
		$value = [
			'email' => 'test@localhost',
			'name' => 'Testy McTesterson',
			'address' => ['country' => 'Iran', 'city' => 'Mashhad']
		];
		$expires = "+2 days";
		$path = '/';

		$closure = $this->cookie->write($key, $value);
		$this->assertInternalType('callable', $closure);
		$params = compact('key', 'value');
		$result = $closure($params);

		$expected = compact('expires');
		$expected += ['key' => 'user.email', 'value' => 'test@localhost'];
		$this->assertCookie($expected);

		$expected = compact('expires');
		$expected += ['key' => 'user.address.country', 'value' => 'Iran'];
		$this->assertCookie($expected);
	}

	public function testReadDotSyntax() {
		$key = 'read.test';
		$value = 'value to be read';
		$_COOKIE[$this->name]['read']['test'] = $value;

		$closure = $this->cookie->read($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertEqual($value, $result);

		$result = $closure(['key' => null], null);
		$this->assertEqual($_COOKIE[$this->name], $result);

		$key = 'does.not.exist';
		$closure = $this->cookie->read($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertNull($result);

	}

	public function testWriteCustomParameters() {
		$key = 'write';
		$value = 'value to be written';
		$expires = "+1 day";
		$path = '/';
		$options = ['expire' => $expires];

		$closure = $this->cookie->write($key, $value, $options);
		$this->assertInternalType('callable', $closure);

		$params = compact('key', 'value', 'options');
		$result = $closure($params);

		$this->assertCookie(compact('key', 'value', 'expires', 'path'));
	}

	public function testRead() {
		$key = 'read';
		$value = 'value to be read';
		$_COOKIE[$this->name][$key] = $value;

		$closure = $this->cookie->read($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertEqual($value, $result);

		$result = $closure(['key' => null], null);
		$this->assertEqual($_COOKIE[$this->name], $result);

		$key = 'does_not_exist';
		$closure = $this->cookie->read($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertNull($result);
	}

	public function testCheck() {
		$key = 'read';
		$value = 'value to be read';
		$_COOKIE[$this->name][$key] = $value;

		$closure = $this->cookie->check($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);

		$key = 'does_not_exist';
		$closure = $this->cookie->check($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertFalse($result);
	}

	public function testClearCookie() {
		$key = 'clear_key';
		$value = 'clear_value';
		$_COOKIE[$this->name][$key] = $value;

		$closure = $this->cookie->check($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);

		$closure = $this->cookie->clear();
		$this->assertInternalType('callable', $closure);

		$params = [];
		$result = $closure($params, null);
		$this->assertTrue($result);
		$this->assertNoCookie(compact('key', 'value'));

	}

	public function testDeleteArrayData() {
		$key = 'user';
		$value = ['email' => 'user@localhost', 'name' => 'Ali'];
		$_COOKIE[$this->name][$key] = $value;

		$closure = $this->cookie->delete($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);

		$expected = ['key' => 'user.name', 'value' => 'deleted'];
		$this->assertCookie($expected);

		$expected = ['key' => 'user.email', 'value' => 'deleted'];
		$this->assertCookie($expected);
	}

	public function testDeleteNonExistentValue() {
		$key = 'delete';
		$value = 'deleted';
		$path = '/';

		$closure = $this->cookie->delete($key);
		$this->assertInternalType('callable', $closure);

		$params = compact('key');
		$result = $closure($params, null);
		$this->assertTrue($result);
		$this->assertCookie(compact('key', 'value', 'path'));
	}

	public function testDefaultCookieName() {
		$cookie = new Cookie();
		$expected = Inflector::slug(basename(Libraries::get(true, 'path'))) . 'cookie';
		$this->assertEqual($expected, $cookie->key());
	}

	public function testBadWrite() {
		$cookie = new Cookie(['expire' => null]);
		$this->assertNull($cookie->write('bad', 'val'));
	}

	public function testNameWithDotCookie() {
		$cookie = new Cookie(['name' => 'my.name']);
		$key = 'key';
		$value = 'value';
		$result = $cookie->write($key, $value)->__invoke(compact('key', 'value'));
		$this->assertCookie(compact('key', 'value'));
	}
}

?>