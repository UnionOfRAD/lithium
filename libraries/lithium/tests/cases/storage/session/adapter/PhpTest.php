<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\session\adapter;

use \lithium\storage\session\adapter\Php;

class PhpTest extends \lithium\test\Unit {

	public function setUp() {
		if (session_id()) {
			session_destroy();
		}
		$this->Php = new Php();

		/* Garbage collection */
		$this->_gc_divisor = ini_get('session.gc_divisor');
		ini_set('session.gc_divisor', '1');
	}

	public function tearDown() {
		if (session_id()) {
			session_destroy();
		}
		/* Revert to original garbage collection probability */
		ini_set('session.gc_divisor', $this->_gc_divisor);

	}

	public function testEnabled() {
		$php = $this->Php;
		$this->assertTrue($php::enabled());
	}

	public function testInit() {
		$id = session_id();
		$this->assertTrue(!empty($id));
		$this->assertEqual(session_cache_limiter(), "nocache");

		$result = $_SESSION['_timestamp'];
		$expected = time();
		$this->assertEqual($expected, $result);
	}

	public function testDefaultConfiguration() {
		$result = ini_get('session.name');
		$this->assertEqual('', $result);

		$result = ini_get('session.cookie_lifetime');
		$this->assertEqual(strtotime('+1 day') - time(), (integer) $result);

		$result = ini_get('session.cookie_domain');
		$this->assertEqual('', $result);

		$result = ini_get('session.cookie_secure');
		$this->assertFalse($result);

		$result = ini_get('session.cookie_httponly');
		$this->assertFalse($result);
	}

	public function testCustomConfiguration() {
		$config = array(
			'name' => 'awesome_name', 'cookie_lifetime' => 1200, 'cookie_domain' => 'awesome.domain',
		);

		$adapter = new Php($config);

		$result = ini_get('session.name');
		$this->assertEqual($config['name'], $result);

		$result = ini_get('session.cookie_lifetime');
		$this->assertEqual($config['cookie_lifetime'], (integer) $result);

		$result = ini_get('session.cookie_domain');
		$this->assertEqual($config['cookie_domain'], $result);

		$result = ini_get('session.cookie_secure');
		$this->assertFalse($result);

		$result = ini_get('session.cookie_httponly');
		$this->assertFalse($result);
	}

	public function testIsStarted() {
		$result = $this->Php->isStarted();
		$this->assertTrue($result);

		unset($_SESSION);

		$result = $this->Php->isStarted();
		$this->assertFalse($result);
	}

	public function testKey() {
		$result = $this->Php->key();
		$this->assertEqual(session_id(), $result);

		session_destroy();
		$result = $this->Php->key();
		$this->assertNull($result);
	}

	public function testWrite() {
		$key = 'write-test';
		$value = 'value to be written';

		$closure = $this->Php->write($key, $value);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'value');
		$result = $closure($this->Php, $params, null);

		$this->assertEqual($_SESSION[$key], $value);
	}

	public function testRead() {
		$key = 'read_test';
		$value = 'value to be read';

		$_SESSION[$key] = $value;

		$closure = $this->Php->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Php, $params, null);
		$this->assertIdentical($value, $result);

		$key = 'non-existent';

		$closure = $this->Php->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Php, $params, null);

		$this->assertNull($result);
	}

	public function testDelete() {
		$key = 'delete_test';
		$value = 'value to be deleted';

		$_SESSION[$key] = $value;

		$closure = $this->Php->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Php, $params, null);

		$this->assertTrue($result);

		$key = 'non-existent';

		$closure = $this->Php->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Php, $params, null);

		$this->assertFalse($result);

	}
}

?>