<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\session\adapter;

use \lithium\storage\session\adapter\Cookie;

class CookieTest extends \lithium\test\Unit {

	/**
	 * Skip the test if running under CLI.
	 *
	 * @return void
	 */
	public function skip() {
		$sapi = PHP_SAPI;
		$message = 'Cookie tests cannot be run via command-line interface.';
		$this->skipIf($sapi === 'cli', $message);
	}

	public function assertCookie($expected, $headers) {
		$key = $expected['key'];
		$value = preg_quote(urlencode($expected['value']), '/');

		if (isset($expected['expires'])) {
			$expires = preg_quote(gmdate('D, d-M-Y H:i:s \G\M\T', strtotime($expected['expires'])), '/');
		} else {
			$expires = ".+?";
		}
		$path = preg_quote($expected['path'], '/');
		$pattern = "/^Set\-Cookie:\sli3\[$key\]=$value;\sexpires=$expires;\spath=$path/";
		$match = false;

		foreach ($headers as $header) {
			if (preg_match($pattern, $header)) {
				$match = true;
				continue;
			}
		}

		if (!$match) {
			$this->assert(false, sprintf('{:message} - Cookie %s not found in headers.', $pattern));
			return false;
		}
		return $this->assert(true, '%s');
	}

	public function setUp() {
		$this->Cookie = new Cookie();
	}

	public function testEnabled() {
		$this->assertTrue($this->Cookie->isEnabled());
	}

	public function testKey() {
		$this->assertEqual('li3', $this->Cookie->key());
	}

	public function testIsStarted() {
		$this->assertTrue($this->Cookie->isStarted());
	}

	public function testWriteDefaultParameters() {
		$key = 'write';
		$value = 'value to be written';
		$expires = "+2 days";
		$path = '/';

		$closure = $this->Cookie->write($key, $value);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'value');
		$result = $closure($this->Cookie, $params, null);

		$this->assertCookie(compact('key', 'value', 'expires', 'path'), headers_list());
	}

	public function testWriteCustomParameters() {
		$key = 'write';
		$value = 'value to be written';
		$expires = "+1 day";
		$path = '/';
		$options = array('expire' => $expires);

		$closure = $this->Cookie->write($key, $value, $options);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'value', 'options');
		$result = $closure($this->Cookie, $params, null);

		$this->assertCookie(compact('key', 'value', 'expires', 'path'), headers_list());
	}

	public function testWriteArrayOfValues() {

	}

	public function testRead() {
		$key = 'read';
		$value = 'value to be read';
		$_COOKIE[$key] = $value;

		$closure = $this->Cookie->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cookie, $params, null);
		$this->assertEqual($value, $result);

		$key = 'does_not_exist';
		$closure = $this->Cookie->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cookie, $params, null);

		$this->assertNull($result);
	}

	public function testReadArrayOfValues() {

	}

	public function testDeleteNonExistentValue() {
		$key = 'delete';
		$value = 'deleted';
		$path = '/';

		$closure = $this->Cookie->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Cookie, $params, null);
		$this->assertNull($result);

		$this->assertCookie(compact('key', 'value', 'path'), headers_list());
	}
}

?>