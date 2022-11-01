<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\storage\session\strategy;

use lithium\storage\Session;
use lithium\storage\session\strategy\Hmac;

class HmacTest extends \lithium\test\Integration {

	public function tearDown() {
		if (Session::config()) {
			Session::clear();
		}
	}

	public function testWithPhpAdapter() {
		$this->skipIf(PHP_SAPI === 'cli', 'No PHP session support in cli SAPI.');

		$config = ['name' => 'hmacInt'];

		Session::config([
			$config['name'] => [
				'adapter' => 'Php',
				'strategies' => [
					'Hmac' => [
						'secret' => 's3cr3t'
					]
				]
			]
		]);

		Session::clear($config);

		$key = 'test';
		$value = 'value';

		$this->assertTrue(Session::write($key, $value, $config));
		$this->assertEqual($value, Session::read($key, $config));
		$this->assertTrue(Session::delete($key, $config));
		$this->assertNull(Session::read($key, $config));

		Session::clear($config);

		$this->assertTrue(Session::write('foo', 'bar', $config));
		$this->assertEqual('bar', Session::read('foo', $config));
		$this->assertTrue(Session::write('foo', 'bar1', $config));
		$this->assertEqual('bar1', Session::read('foo', $config));

		Session::clear($config);

		$this->assertTrue(Session::write($key, $value, $config));
		$this->assertEqual($value, Session::read($key, $config));

		$cache = $_SESSION;
		$_SESSION['injectedkey'] = 'hax0r';
		$expected = '/Possible data tampering: HMAC signature does not match data./';
		$this->assertException($expected, function() use ($key, $config) {
			Session::read($key, $config);
		});
		$_SESSION = $cache;

		Session::reset();
	}

	public function testWithMemoryOnNonExistKey() {
		Session::config(['primary' => [
			'adapter' => 'Memory',
			'strategies' => [
				'Hmac' => [
					'secret' => 's3cr3t'
				]
			]
		]]);

		$this->assertEmpty(Session::read('test'));

		Session::write('test', 'value');
		$result = Session::read('test');
		$expected = 'value';
		$this->assertEqual($expected, $result);
	}
}

?>