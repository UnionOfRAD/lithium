<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\storage;

use lithium\storage\Session;

class SessionTest extends \lithium\test\Unit {

	public function setUp() {
		Session::reset();
		$cookies = array_keys($_COOKIE);

		foreach ($cookies as $cookie) {
			setcookie($cookie, "", time()-1);
		}
	}

	public function tearDown() {
		Session::reset();
		$cookies = array_keys($_COOKIE);

		foreach ($cookies as $cookie) {
			setcookie($cookie, "", time()-1);
		}
	}

	public function testWriteAndRead() {
		Session::config(array(
			'default' => array('adapter' => 'Php')
		));

		$key = 'write_key';
		$value = 'write_value';

		Session::write($key, $value);
		$result = Session::read($key);
		$this->assertEqual($value, $result);

		$key2 = 'write_key_2';
		$value2 = 'write_value_2';
		Session::write($key2, $value2);
		$result = Session::read($key2);
		$this->assertEqual($value2, $result);

		$this->assertTrue(Session::delete($key));
		$this->assertTrue(Session::delete($key2));

		$result = Session::read($key);
		$result2 = Session::read($key2);

		$this->assertNull($result);
		$this->assertNull($result2);
	}

	public function testWriteReadDelete() {
		Session::config(array(
			'default' => array('adapter' => 'Php')
		));

		$key = 'test';
		$value = 'value';

		Session::write($key, $value);
		$result = Session::read($key);

		$this->assertEqual($value, $result);
		$this->assertTrue(Session::delete($key));

		$result = Session::read($key);
		$this->assertNull($result);
	}

	public function testNamespaces() {
		Session::config(array(
			'test' => array('adapter' => 'Php')
		));

		$value = 'second value';
		Session::write('first.second', $value);
		$result = Session::read('first.second');
		$this->assertEqual($value, $result);
		$this->assertTrue(isset($_SESSION['first']));
		$this->assertTrue(isset($_SESSION['first']['second']));
		$this->assertEqual($value, $_SESSION['first']['second']);

		$result = Session::read('first');
		$expected = array('second' => 'second value');
		$this->assertEqual($expected, $result);
		$this->assertTrue(isset($_SESSION['first']));
		$this->assertEqual($_SESSION['first'], $result);

		$value = 'another value';
		Session::write('first.sibling', $value);
		$result = Session::read('first.sibling');
		$this->assertEqual($value, $result);
		$this->assertEqual($_SESSION['first']['sibling'], $value);

		$result = Session::delete('first.sibling');
		$this->assertEqual(true, $result);
		$this->assertFalse(isset($_SESSION['first']['sibling']));
		$this->assertTrue(isset($_SESSION['first']['second']));

		$result = Session::delete('first');
		$this->assertEqual(true, $result);
		$this->assertFalse(isset($_SESSION['first']));
	}

	public function testCookieWriteReadDelete() {
		Session::config(array(
			'li3' => array('adapter' => 'Cookie', 'expiry' => '+1 day')
		));

		Session::write('testkey1', 'value1', array('name' => 'li3'));
		Session::write('testkey2', 'value2', array('name' => 'li3'));
		Session::write('testkey3', 'value3', array('name' => 'li3'));

		$this->assertCookie(
			array('key' => 'testkey1', 'value' => 'value1')
		);
		$this->assertCookie(
			array('key' => 'testkey2', 'value' => 'value2')
		);
		$this->assertCookie(
			array('key' => 'testkey3', 'value' => 'value3')
		);

		Session::delete('testkey1', array('name' => 'li3'));
		Session::delete('testkey2', array('name' => 'li3'));
		Session::delete('testkey3', array('name' => 'li3'));

		$params = array('exires' => '-1 second', 'path' => '/');

		$this->assertCookie(
			array('key' => 'testkey1', 'value' => 'deleted')
		);
		$this->assertCookie(
			array('key' => 'testkey2', 'value' => 'deleted')
		);
		$this->assertCookie(
			array('key' => 'testkey3', 'value' => 'deleted')
		);
	}

	public function testStrategiesPhpAdapter() {
		Session::config(array(
			'strategy' => array(
				'adapter' => 'Php',
				'strategies' => array('Hmac' => array('secret' => 'somesecretkey'))
			)
		));

		$key = 'test';
		$value = 'value';

		Session::write($key, $value, array('name' => 'strategy'));
		$result = Session::read($key, array('name' => 'strategy'));

		$this->assertEqual($value, $result);
		$this->assertTrue(Session::delete($key, array('name' => 'strategy')));
		$result = Session::read($key, array('name' => 'strategy'));
		$this->assertNull($result);

		Session::write($key, $value, array('name' => 'strategy'));
		$result = Session::read($key, array('name' => 'strategy'));
		$this->assertEqual($value, $result);

		$cache = $_SESSION;
		$_SESSION['injectedkey'] = 'hax0r';
		$this->expectException('/Possible data tampering - HMAC signature does not match data./');
		$result = Session::read($key, array('name' => 'strategy'));
		$_SESSION = $cache;
	}
}

?>