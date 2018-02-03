<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\storage;

use lithium\storage\Session;
use lithium\storage\session\strategy\Encrypt;

class SessionTest extends \lithium\test\Integration {

	public function tearDown() {
		if (Session::config()) {
			Session::clear();
		}
	}

	public function testPhpReadWriteDelete() {
		$this->skipIf(PHP_SAPI === 'cli', 'No PHP session support in cli SAPI.');

		$config = ['name' => 'phpInt'];

		Session::config([
			$config['name'] => [
				'adapter' => 'Php'
			]
		]);

		Session::clear($config);

		$key1 = 'key_one';
		$value1 = 'value_one';
		$key2 = 'key_two';
		$value2 = 'value_two';

		$this->assertNull(Session::read($key1, $config));
		$this->assertTrue(Session::write($key1, $value1, $config));
		$this->assertEqual($value1, Session::read($key1, $config));
		$this->assertNull(Session::read($key2, $config));
		$this->assertTrue(Session::delete($key1, $config));
		$this->assertNull(Session::read($key1, $config));
	}

	public function testCookieReadWriteDelete() {
		$this->skipIf(PHP_SAPI === 'cli', 'No headers support in cli SAPI.');

		$config = ['name' => 'cookieInt'];

		Session::config([
			$config['name'] => [
				'adapter' => 'Cookie'
			]
		]);

		Session::clear($config);

		$key1 = 'key_one';
		$value1 = 'value_one';
		$key2 = 'key_two';
		$value2 = 'value_two';

		$this->assertNull(Session::read($key1, $config));
		$this->assertTrue(Session::write($key1, $value1, $config));
		$this->assertCookie(['key' => $key1, 'value' => $value1]);
		$this->assertNull(Session::read($key2, $config));
		$this->assertTrue(Session::delete($key1, $config));
		$this->assertCookie(['key' => $key1, 'value' => 'deleted']);
		$this->assertNoCookie(['key' => $key2, 'value' => $value2]);
		$this->assertNull(Session::read($key1, $config));
	}

	public function testMemoryReadWriteDelete() {
		$config = ['name' => 'memoryInt'];

		Session::config([
			$config['name'] => [
				'adapter' => 'Memory'
			]
		]);

		Session::clear($config);

		$key1 = 'key_one';
		$value1 = 'value_one';
		$key2 = 'key_two';
		$value2 = 'value_two';

		$this->assertNull(Session::read($key1, $config));
		$this->assertTrue(Session::write($key1, $value1, $config));
		$this->assertEqual($value1, Session::read($key1, $config));
		$this->assertNull(Session::read($key2, $config));
		$this->assertTrue(Session::delete($key1, $config));
		$this->assertNull(Session::read($key1, $config));
	}

	public function testNamespacesWithPhpAdapter() {
		$this->skipIf(PHP_SAPI === 'cli', 'No PHP session support in cli SAPI.');

		$config = ['name' => 'namespaceInt'];

		Session::config([
			$config['name'] => [
				'adapter' => 'Php'
			]
		]);

		Session::clear($config);

		$key1 = 'really.deep.nested.key';
		$value1 = 'nested_val';
		$key2 = 'shallow.key';
		$value2 = 'shallow_val';

		$this->assertTrue(Session::write($key1, $value1, $config));
		$this->assertTrue(Session::write($key2, $value2, $config));
		$this->assertEqual($value1, Session::read($key1, $config));
		$this->assertEqual($value2, Session::read($key2, $config));
		$expected = ['nested' => ['key' => $value1]];
		$this->assertEqual($expected, Session::read('really.deep', $config));
	}
}

?>