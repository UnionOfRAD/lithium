<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\storage;

use lithium\aop\Filters;
use lithium\storage\Session;
use lithium\storage\session\adapter\Memory;
use lithium\tests\mocks\storage\session\adapter\SessionStorageConditional;
use lithium\tests\mocks\storage\session\strategy\MockEncrypt;

class SessionTest extends \lithium\test\Unit {

	public function setUp() {
		Session::config([
			'default' => ['adapter' => new Memory()]
		]);
	}

	public function testSessionInitialization() {
		$store1 = new Memory();
		$store2 = new Memory();
		$config = [
			'store1' => ['adapter' => &$store1, 'filters' => []],
			'store2' => ['adapter' => &$store2, 'filters' => []]
		];

		Session::config($config);
		$result = Session::config();
		$this->assertEqual($config, $result);

		Session::reset();
		Session::config(['store1' => [
			'adapter' => 'lithium\storage\session\adapter\Memory',
			'filters' => []
		]]);
		$this->assertTrue(Session::write('key', 'value'));
		$result = Session::read('key');
		$expected = 'value';
		$this->assertEqual($expected, $result);
	}

	public function testSingleStoreReadWrite() {
		$this->assertNull(Session::read('key'));

		$this->assertTrue(Session::write('key', 'value'));
		$this->assertEqual(Session::read('key'), 'value');

		Session::reset();
		$this->assertNull(Session::read('key'));
		$this->assertFalse(Session::write('key', 'value'));
	}

	public function testNamedConfigurationReadWrite() {
		$store1 = new Memory();
		$store2 = new Memory();
		$config = [
			'store1' => ['adapter' => &$store1, 'filters' => []],
			'store2' => ['adapter' => &$store2, 'filters' => []]
		];
		Session::reset();
		Session::config($config);
		$result = Session::config();
		$this->assertEqual($config, $result);

		$result = Session::write('key', 'value', ['name' => 'store1']);
		$this->assertTrue($result);

		$result = Session::read('key', ['name' => 'store1']);
		$this->assertEqual($result, 'value');

		$result = Session::read('key', ['name' => 'store2']);
		$this->assertEmpty($result);
	}

	public function testSessionConfigReset() {
		$this->assertTrue(Session::write('key', 'value'));
		$this->assertEqual(Session::read('key'), 'value');

		Session::reset();
		$this->assertEmpty(Session::config());

		$this->assertEmpty(Session::read('key'));
		$this->assertFalse(Session::write('key', 'value'));
	}

	/**
	 * Tests a scenario where no session handler is available that matches the passed parameters.
	 */
	public function testUnhandledWrite() {
		Session::config([
			'conditional' => ['adapter' => new SessionStorageConditional()]
		]);
		$result = Session::write('key', 'value', ['fail' => true]);
		$this->assertFalse($result);
	}

	/**
	 * Tests deleting a session key from one or all adapters.
	 */
	public function testSessionKeyCheckAndDelete() {
		Session::config([
			'temp' => ['adapter' => new Memory(), 'filters' => []],
			'persistent' => ['adapter' => new Memory(), 'filters' => []]
		]);
		Session::write('key1', 'value', ['name' => 'persistent']);
		Session::write('key2', 'value', ['name' => 'temp']);

		$this->assertTrue(Session::check('key1'));
		$this->assertTrue(Session::check('key2'));

		$this->assertTrue(Session::check('key1', ['name' => 'persistent']));
		$this->assertFalse(Session::check('key1', ['name' => 'temp']));

		$this->assertFalse(Session::check('key2', ['name' => 'persistent']));
		$this->assertTrue(Session::check('key2', ['name' => 'temp']));

		Session::delete('key1');
		$this->assertFalse(Session::check('key1'));

		Session::write('key1', 'value', ['name' => 'persistent']);
		$this->assertTrue(Session::check('key1'));

		Session::delete('key1', ['name' => 'temp']);
		$this->assertTrue(Session::check('key1'));

		Session::delete('key1', ['name' => 'persistent']);
		$this->assertFalse(Session::check('key1'));
	}

	/**
	 * Tests clearing all session data from one or all adapters.
	 */
	public function testSessionClear() {
		Session::config([
			'primary' => ['adapter' => new Memory(), 'filters' => []],
			'secondary' => ['adapter' => new Memory(), 'filters' => []]
		]);
		Session::write('key1', 'value', ['name' => 'primary']);
		Session::write('key2', 'value', ['name' => 'secondary']);

		Session::clear(['name' => 'secondary']);
		$this->assertTrue(Session::check('key1'));
		$this->assertFalse(Session::check('key2'));

		Session::write('key2', 'value', ['name' => 'secondary']);
		Session::clear();
		$this->assertFalse(Session::check('key1'));
		$this->assertFalse(Session::check('key2'));
	}

	/**
	 * Tests querying session keys from the primary adapter.
	 * The memory adapter returns a UUID.
	 */
	public function testKey() {
		$result = Session::key();
		$pattern = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/";
		$this->assertPattern($pattern, $result);
	}

	public function testConfigNoAdapters() {
		Session::config([
			'conditional' => ['adapter' => new SessionStorageConditional()]
		]);
		$this->assertTrue(Session::write('key', 'value'));
		$this->assertEqual(Session::read('key'), 'value');
		$this->assertEmpty(Session::read('key', ['fail' => true]));
	}

	public function testSessionState() {
		$this->assertTrue(Session::isStarted());
		$this->assertTrue(Session::isStarted('default'));
		$this->assertException("Configuration `invalid` has not been defined.", function() {
			Session::isStarted('invalid');
		});
	}

	public function testSessionStateReset() {
		Session::reset();
		$this->assertFalse(Session::isStarted());
	}

	public function testSessionStateResetNamed() {
		Session::reset();
		$this->assertException("Configuration `default` has not been defined.", function() {
			Session::isStarted('default');
		});
	}

	public function testReadFilter() {
		Session::config([
			'primary' => ['adapter' => new Memory(), 'filters' => []],
			'secondary' => ['adapter' => new Memory(), 'filters' => []]
		]);
		Filters::apply('lithium\storage\Session', 'read', function($params, $next) {
			$result = $next($params);

			if (isset($params['options']['increment'])) {
				$result += $params['options']['increment'];
			}
			return $result;
		});
		Session::write('foo', 'bar');
		$this->assertEqual('bar', Session::read('foo'));

		Session::write('bar', 1);
		$this->assertEqual(2, Session::read('bar', ['increment' => 1]));

		Filters::clear('lithium\storage\Session');
	}

	public function testStrategies() {
		Session::config(['primary' => [
			'adapter' => new Memory(), 'filters' => [], 'strategies' => [
				'lithium\storage\cache\strategy\Json'
			]
		]]);

		Session::write('test', ['foo' => 'bar']);
		$this->assertEqual(['foo' => 'bar'], Session::read('test'));

		$this->assertTrue(Session::check('test'));
		$this->assertTrue(Session::check('test', ['strategies' => false]));

		$result = Session::read('test', ['strategies' => false]);
		$this->assertEqual('{"foo":"bar"}', $result);

		$result = Session::clear(['strategies' => false]);
		$this->assertNull(Session::read('test'));

		$this->assertFalse(Session::check('test'));
		$this->assertFalse(Session::check('test', ['strategies' => false]));
	}

	public function testMultipleStrategies() {
		Session::config([
			'primary' => [
				'adapter' => new Memory(),
				'filters' => [],
				'strategies' => []
			],
			'secondary' => [
				'adapter' => new Memory(),
				'filters' => [],
				'strategies' => ['lithium\storage\cache\strategy\Json']
			]
		]);

		Session::write('test', ['foo' => 'bar']);
		$result = Session::read('test');
		$this->assertEqual(['foo' => 'bar'], $result);

		$result = Session::read('test', ['name' => 'primary', 'strategies' => false]);
		$this->assertEqual(['foo' => 'bar'], $result);

		$result = Session::read('test', ['name' => 'secondary', 'strategies' => false]);
		$this->assertEqual('{"foo":"bar"}', $result);
	}

	public function testEncryptedStrategy() {
		$this->skipIf(!MockEncrypt::enabled(), 'The Mcrypt extension is not installed or enabled.');
		error_reporting(($this->_backup = error_reporting()) & ~E_DEPRECATED);

		$key = 'foobar';
		$adapter = new Memory();
		Session::config(['primary' => [
			'adapter' => $adapter, 'filters' => [], 'strategies' => [
				'lithium\tests\mocks\storage\session\strategy\MockEncrypt' => [
					'secret' => $key
				]
			]
		]]);

		$value = ['foo' => 'bar'];

		Session::write('test', $value);
		$this->assertEqual(['foo' => 'bar'], Session::read('test'));

		$this->assertTrue(Session::check('test'));
		$this->assertTrue(Session::check('test', ['strategies' => false]));

		$encrypted = Session::read('test', ['strategies' => false]);

		$this->assertNotEqual($value, $encrypted);
		$this->assertInternalType('string', $encrypted);

		$result = Session::read('test');
		$this->assertEqual($value, $result);

		$result = Session::clear(['strategies' => false]);
		$this->assertNull(Session::read('test'));

		$this->assertFalse(Session::check('test'));
		$this->assertFalse(Session::check('test', ['strategies' => false]));

		$savedData = ['test' => $value];

		$encrypt = new MockEncrypt(['secret' => $key]);
		$result = $encrypt->encrypt($savedData);
		$this->assertEqual($encrypted, $result);
		$result = $encrypt->decrypt($encrypted);
		$this->assertEqual($savedData, $result);
	}

	public function testHmacStrategyOnNonExistKey() {
		Session::config(['primary' => [
			'adapter' => new Memory(),
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