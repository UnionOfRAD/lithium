<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\storage;

use lithium\storage\Session;

class CookieTest extends \lithium\test\Unit {

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

	public function testCookieWriteReadDelete() {
		Session::config(array(
			'li3' => array(
				'adapter' => 'Cookie',
				'expiry' => '+1 day'
			)
		));

		Session::write('ns.testkey1', 'value1', array('name' => 'li3'));
		Session::write('ns.testkey2', 'value2', array('name' => 'li3'));
		Session::write('ns.testkey3', 'value3', array('name' => 'li3'));

		$this->assertCookie(
			array('key' => 'ns.testkey1', 'value' => 'value1')
		);
		$this->assertCookie(
			array('key' => 'ns.testkey2', 'value' => 'value2')
		);
		$this->assertCookie(
			array('key' => 'ns.testkey3', 'value' => 'value3')
		);

		Session::delete('ns.testkey1', array('name' => 'li3'));
		Session::delete('ns.testkey2', array('name' => 'li3'));
		Session::delete('ns.testkey3', array('name' => 'li3'));

		$params = array('exires' => '-1 second', 'path' => '/');

		$this->assertNoCookie(array('key' => 'ns.testkey1'));
		$this->assertNoCookie(array('key' => 'ns.testkey2'));
		$this->assertNoCookie(array('key' => 'ns.testkey3'));
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

	public function testStrategiesCookieAdapter() {
		$key = 'test_key';
		$value = 'test_value';

		Session::config(array(
			'default' => array(
				'adapter' => 'Cookie',
				'strategies' => array('Hmac' => array('secret' => 'somesecretkey'))
			)
		));

		$result = Session::write($key, $value);
		$this->assertTrue($result);

		$result = Session::read($key);
		$this->assertEqual($value, $result);

		$this->assertTrue(Session::delete($key));

		$result = Session::read($key);
		$this->assertNull($result);

		Session::write($key, $value);
		$result = Session::read($key);
		$this->assertEqual($value, $result);
		$this->assertTrue(Session::delete($key));
	}

	public function testHmacStrategy() {
		$key = 'test';
		$value = 'value';
		$name = 'hmac_test';

		Session::config(array(
			'default' => array(
				'adapter' => 'Cookie',
				'strategies' => array('Hmac' => array('secret' => 'somesecretkey')),
				'name' => $name
			)
		));

		$cache = $_COOKIE;
		$_COOKIE[$name]['injectedkey'] = 'hax0r';
		$this->expectException('/Possible data tampering - HMAC signature does not match data./');
		$result = Session::read($key, array('name' => 'hmac'));
		$_COOKIE = $cache;
	}
}

?>
