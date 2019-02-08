<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\core;

use lithium\core\Environment;
use lithium\tests\mocks\core\MockRequest;

class EnvironmentTest extends \lithium\test\Unit {

	public function setUp() {
		Environment::reset();
	}

	/**
	 * Tests setting and getting current environment, and that invalid environments cannot be
	 * selected.
	 */
	public function testSetAndGetCurrentEnvironment() {
		Environment::set('production',  ['foo' => 'bar']);
		Environment::set('staging',     ['foo' => 'baz']);
		Environment::set('development', ['foo' => 'dib']);

		Environment::set('development');

		$this->assertEqual('development', Environment::get());
		$this->assertTrue(Environment::is('development'));
		$this->assertNull(Environment::get('doesNotExist'));

		$expected = ['foo' => 'dib'];
		$config = Environment::get('development');
		$this->assertEqual($expected, $config);

		$foo = Environment::get('foo'); // returns 'dib', since the current env. is 'development'
		$expected = 'dib';
		$this->assertEqual($expected, $foo);
	}

	/**
	 * Tests that a set of configuration keys can be assigned to multiple environments.
	 */
	public function testSetMultipleEnvironments() {
		foreach (['foo', 'bar', 'baz'] as $key) {
			$this->assertNull(Environment::get($key));
		}

		Environment::set(['foo', 'bar', 'baz'], [
			'key' => ['subkey' => 'value']
		]);

		foreach (['foo', 'bar', 'baz'] as $key) {
			$this->assertEqual(['key' => ['subkey' => 'value']], Environment::get($key));
		}
	}

	/**
	 * Tests creating a custom environment, and verifies that settings are properly retrieved.
	 */
	public function testCreateNonStandardEnvironment() {
		Environment::set('custom', ['host' => 'server.local']);
		Environment::set('custom');

		$host = Environment::get('host');
		$expected = 'server.local';
		$this->assertEqual($expected, $host);

		$custom = Environment::get('custom');
		$expected = ['host' => 'server.local'];
		$this->assertEqual($expected, $custom);
	}

	/**
	 * Tests modifying environment configuration.
	 */
	public function testModifyEnvironmentConfig() {
		Environment::set('test', ['foo' => 'bar']);
		$expected = ['foo' => 'bar'];
		$this->assertEqual($expected, Environment::get('test'));

		$expected = ['foo' => 'bar', 'baz' => 'qux'];
		Environment::set('test', ['baz' => 'qux']);
		$settings = Environment::get('test'); // returns ['foo' => 'bar', 'baz' => 'qux']
		$this->assertEqual($expected, Environment::get('test'));
	}

	/**
	 * Tests auto-detecting environment settings through a series of mock request classes.
	 */
	public function testEnvironmentDetection() {
		Environment::set(new MockRequest(['SERVER_ADDR' => '::1']));
		$this->assertTrue(Environment::is('development'));

		$request = new MockRequest(['SERVER_ADDR' => '1.1.1.1', 'HTTP_HOST' => 'test.local']);
		Environment::set($request);
		$this->assertTrue(Environment::is('test'));

		$request = new MockRequest(['SERVER_ADDR' => '1.1.1.1', 'HTTP_HOST' => 'www.com']);
		Environment::set($request);
		$isProduction = Environment::is('production');
		$this->assertTrue($isProduction);

		$request = new MockRequest(['SERVER_ADDR' => '::1']);
		$request->url = '/test/myTest';
		Environment::set($request);
		$this->assertTrue(Environment::is('test'));

		$request = new MockRequest(['SERVER_ADDR' => '::1']);
		$request->url = '/test';
		Environment::set($request);
		$this->assertTrue(Environment::is('test'));

		$request = new MockRequest();
		$request->command = 'test';
		Environment::set($request);
		$this->assertTrue(Environment::is('test'));

		$request = new MockRequest();
		$request->env = 'test';
		Environment::set($request);
		$this->assertTrue(Environment::is('test'));

		$request = new MockRequest(['PLATFORM' => 'CLI']);
		Environment::set($request);
		$this->assertTrue(Environment::is('development'));

		$request = new MockRequest();
		$request->params = ['env' => 'production'];
		Environment::set($request);
		$this->assertTrue(Environment::is('production'));
	}

	/**
	 * Tests that environment names can be mapped to lists of host names, or a hostname-matching
	 * regular expression.
	 */
	public function testDetectionWithArrayMap() {
		Environment::is([
			'development' => '/^local|^\.console/',
			'test' => ['test1.myapp.com', 'test2.myapp.com'],
			'staging' => ['staging.myapp.com']
		]);

		Environment::set(new MockRequest(['http:host' => 'localhost']));
		$this->assertTrue(Environment::is('development'));

		Environment::set(new MockRequest(['http:host' => 'test1.myapp.com']));
		$this->assertTrue(Environment::is('test'));

		Environment::set(new MockRequest(['http:host' => 'test3.myapp.com']));
		$this->assertTrue(Environment::is('production'));

		Environment::set(new MockRequest(['http:host' => 'localhost:3030']));
		$this->assertTrue(Environment::is('development'));

		$request = new MockRequest();
		$request->params = ['env' => 'whatever'];
		Environment::set($request);
		$this->assertTrue(Environment::is('whatever'));
	}

	/**
	 * Tests resetting the `Environment` class to its default state.
	 */
	public function testResetAll() {
		Environment::set('test', ['foo' => 'bar']);
		Environment::set('test');
		$this->assertEqual('test', Environment::get());
		$this->assertEqual('bar', Environment::get('foo'));

		Environment::reset();
		$this->assertEqual('', Environment::get());
		$this->assertNull(Environment::get('foo'));
	}

	public function testResetASpecificEnv() {
		Environment::set('test', ['foo' => 'bar']);
		Environment::set('development', ['hello' => 'world']);

		Environment::set('test');
		$this->assertEqual('test', Environment::get());
		$this->assertEqual('bar', Environment::get('foo'));

		Environment::reset('test');
		$this->assertEqual('test', Environment::get());
		$this->assertNull(Environment::get('foo'));

		Environment::set('development');
		$this->assertEqual('world', Environment::get('hello'));
	}

	/**
	 * Tests using a custom detector to get the current environment.
	 */
	public function testCustomDetector() {
		Environment::is(function($request) {
			if ($request->env('HTTP_HOST') === 'localhost') {
				return 'development';
			}
			if ($request->env('HTTP_HOST') === 'staging.server') {
				return 'test';
			}
			return 'production';
		});

		$request = new MockRequest(['HTTP_HOST' => 'localhost']);
		Environment::set($request);
		$this->assertTrue(Environment::is('development'));

		$request = new MockRequest(['HTTP_HOST' => 'lappy.local']);
		Environment::set($request);
		$this->assertTrue(Environment::is('production'));

		$request = new MockRequest(['HTTP_HOST' => 'staging.server']);
		Environment::set($request);
		$this->assertTrue(Environment::is('test'));

		$request = new MockRequest(['HTTP_HOST' => 'test.local']);
		Environment::set($request);
		$this->assertTrue(Environment::is('production'));
	}

	public function testDotPath() {
		$data = [
			'foo' => ['bar' => ['baz' => 123]],
			'some' => ['path' => true],
			'string' => 'lorem ipsum'
		];
		Environment::set('dotPathIndex', $data);

		$this->assertEqual(123, Environment::get('dotPathIndex.foo.bar.baz'));
		$this->assertEqual($data['foo'], Environment::get('dotPathIndex.foo'));
		$this->assertTrue(Environment::get('dotPathIndex.some.path'));
		$this->assertFalse(Environment::get('dotPathIndex.string.b'));
	}

	/**
	 * Tests calling `get()` and `set()` with `true` as the envrionment name, to automatically
	 * select the current environment.
	 */
	public function testReadWriteWithDefaultEnvironment() {
		Environment::set('development');
		Environment::set(true, ['foo' => 'bar']);

		$this->assertEqual(['foo' => 'bar'], Environment::get('development'));
		$this->assertEqual(Environment::get(true), Environment::get('development'));

		Environment::set('production');
		$this->assertEmpty(Environment::get(true));
	}
}

?>