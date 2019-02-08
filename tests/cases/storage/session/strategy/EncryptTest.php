<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage\session\strategy;

use lithium\storage\session\strategy\Encrypt;
use lithium\tests\mocks\storage\session\strategy\MockCookieSession;

class EncryptTest extends \lithium\test\Unit {

	public $secret = 'foobar';

	/**
	 * Skip the test if the mcrypt extension is unavailable.
	 */
	public function skip() {
		$this->skipIf(!Encrypt::enabled(), 'The Mcrypt extension is not installed or enabled.');
	}

	public function setUp() {
		error_reporting(($this->_backup = error_reporting()) & ~E_DEPRECATED);
		$this->mock = 'lithium\tests\mocks\storage\session\strategy\MockCookieSession';
		MockCookieSession::reset();
	}

	public function tearDown() {
		error_reporting($this->_backup);
	}

	public function testConstructException() {
		$this->assertException('/Encrypt strategy requires a secret key./', function() {
			new Encrypt();
		});
	}

	public function testEnabled() {
		$this->assertTrue(Encrypt::enabled());
	}

	public function testConstruct() {
		$encrypt = new Encrypt(['secret' => $this->secret]);
		$this->assertInstanceOf('lithium\storage\session\strategy\Encrypt', $encrypt);
	}

	public function testWrite() {
		$encrypt = new Encrypt(['secret' => $this->secret]);

		$key = 'fookey';
		$value = 'barvalue';

		$result = $encrypt->write($value, ['class' => $this->mock, 'key' => $key]);
		$cookie = MockCookieSession::data();

		$this->assertNotEmpty($result);
		$this->assertNotEmpty($cookie['__encrypted']);
		$this->assertInternalType('string', $cookie['__encrypted']);
		$this->assertNotEqual($cookie['__encrypted'], $value);
	}

	public function testRead() {
		$encrypt = new Encrypt(['secret' => $this->secret]);

		$key = 'fookey';
		$value = 'barvalue';

		$result = $encrypt->write($value, ['class' => $this->mock, 'key' => $key]);
		$this->assertNotEmpty($result);

		$cookie = MockCookieSession::data();
		$result = $encrypt->read($key, ['class' => $this->mock, 'key' => $key]);

		$this->assertEqual($value, $result);
		$this->assertNotEqual($cookie['__encrypted'], $result);
	}

	public function testDelete() {
		$encrypt = new Encrypt(['secret' => $this->secret]);

		$key = 'fookey';
		$value = 'barvalue';

		$result = $encrypt->write($value, ['class' => $this->mock, 'key' => $key]);
		$this->assertNotEmpty($result);

		$cookie = MockCookieSession::data();
		$result = $encrypt->read($key, ['class' => $this->mock, 'key' => $key]);

		$this->assertEqual($value, $result);

		$result = $encrypt->delete($key, ['class' => $this->mock, 'key' => $key]);

		$cookie = MockCookieSession::data();
		$this->assertEmpty($cookie['__encrypted']);

		$result = $encrypt->read($key, ['class' => $this->mock]);
		$this->assertEmpty($result);
	}
}

?>