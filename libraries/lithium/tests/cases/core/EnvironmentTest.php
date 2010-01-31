<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use \lithium\core\Environment;
use \lithium\tests\mocks\core\MockRequest;

class EnvironmentTest extends \lithium\test\Unit {

	/**
	 * Tests setting and getting current environment, and that invalid environments cannot be
	 * selected.
	 *
	 * @return void
	 */
	public function testSetAndGetCurrentEnvironment() {
		Environment::set('development');
		$this->assertEqual('development', Environment::get());
		$this->assertEqual('development', Environment::is());
		$this->assertTrue(Environment::is('development'));
		$this->assertNull(Environment::get('foo'));
		$this->assertTrue(is_array(Environment::get('development')));
	}

	/**
	 * Tests modifying environment configuration.
	 *
	 * @return void
	 */
	public function testModifyEnvironmentConfiguration() {
		$expected = array('inherit' => 'development', 'foo' => 'bar');
		Environment::set('test', array('foo' => 'bar'));
		$this->assertEqual($expected, Environment::get('test'));

		$expected = array('inherit' => 'production', 'foo' => 'bar', 'baz' => 'qux');
		Environment::set('test', array('inherit' => 'production', 'baz' => 'qux'));
		$this->assertEqual($expected, Environment::get('test'));
	}

	/**
	 * Tests auto-detecting environment settings through a series of mock request classes.
	 *
	 * @return void
	 */
	public function testEnvironmentDetection() {
		Environment::set(new MockRequest(array('SERVER_ADDR' => '::1')));
		$this->assertTrue(Environment::is('development'));

		$request = new MockRequest(array('SERVER_ADDR' => '1.1.1.1', 'HTTP_HOST' => 'test.local'));
		Environment::set($request);
		$this->assertTrue(Environment::is('test'));

		$request = new MockRequest(array('SERVER_ADDR' => '1.1.1.1', 'HTTP_HOST' => 'www.com'));
		Environment::set($request);
		$this->assertTrue(Environment::is('production'));
	}

	/**
	 * Tests using a custom detector to get the current environment.
	 *
	 * @return void
	 */
	public function testCustomDetector() {
		Environment::is(function($request) {
			if ($request->env('HTTP_HOST') == 'localhost') {
				return 'development';
			}
			if ($request->env('HTTP_HOST') == 'staging.server') {
				return 'test';
			}
			return 'production';
		});

		$request = new MockRequest(array('HTTP_HOST' => 'localhost'));
		Environment::set($request);
		$this->assertTrue(Environment::is('development'));

		$request = new MockRequest(array('HTTP_HOST' => 'lappy.local'));
		Environment::set($request);
		$this->assertTrue(Environment::is('production'));

		$request = new MockRequest(array('HTTP_HOST' => 'test.local'));
		Environment::set($request);
		$this->assertTrue(Environment::is('production'));
	}
}

?>