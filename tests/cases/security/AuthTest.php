<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\security;

use lithium\security\Auth;
use lithium\storage\Session;

class AuthTest extends \lithium\test\Unit {

	protected $_classes = [
		'mockAuthAdapter' => 'lithium\tests\mocks\security\auth\adapter\MockAuthAdapter'
	];

	public function setUp() {
		Session::config([
			'test' => ['adapter' => 'Memory']
		]);

		Auth::config([
			'test' => [
				'adapter' => $this->_classes['mockAuthAdapter']
			]
		]);
	}

	public function testBasicAuthCheck() {
		$this->assertFalse(Auth::check('test'));
		$user = ['user' => 'bob'];

		$result = Auth::check('test', $user, ['success' => true]);
		$this->assertEqual($user, $result);

		$result = Session::read('test');
		$this->assertEqual($user, $result);

		$result = Auth::check('test');
		$this->assertEqual($user, $result);
	}

	public function testAuthLogout() {
		$user = ['user' => 'bob'];

		$result = Auth::check('test', $user, ['success' => true]);
		$this->assertEqual($user, $result);

		$result = Auth::check('test');
		$this->assertEqual($user, $result);

		Auth::clear('test');
		$this->assertFalse(Auth::check('test'));
	}

	public function testManualSessionInitialization() {
		$this->assertFalse(Auth::check('test'));
		$user = ['id' => 13, 'user' => 'bob'];

		$this->assertNotEmpty(Auth::set('test', $user));

		$result = Auth::check('test');
		$this->assertEqual($user, $result);
	}

	public function testManualSessionFail() {
		$this->assertFalse(Auth::check('test'));
		$user = ['id' => 13, 'user' => 'bob'];

		$this->assertFalse(Auth::set('test', $user, ['fail' => true]));
		$this->assertFalse(Auth::check('test'));
	}

	public function testNoConfigurations() {
		Auth::reset();
		$this->assertIdentical([], Auth::config());
		$this->assertException("Configuration `user` has not been defined.", function() {
			Auth::check('user');
		});
	}

	public function testAuthPersist() {
		Auth::reset();

		Auth::config([
			'test' => [
				'adapter' => $this->_classes['mockAuthAdapter'],
			]
		]);

		$config = Auth::config();
		$this->assertTrue(isset($config['test']['session']['persist']));
		$this->assertTrue(empty($config['test']['session']['persist']));

		$user = ['username' => 'foo', 'password' => 'bar'];
		$result = Auth::check('test', $user, ['success' => true]);
		$this->assertTrue(isset($result['username']));
		$this->assertFalse(isset($result['password']));

		Auth::reset();

		Auth::config([
			'test' => [
				'adapter' => $this->_classes['mockAuthAdapter'],
				'session' => [
					'persist' => ['username', 'email']
				]
			]
		]);

		$user = [
			'username' => 'foobar',
			'password' => 'not!important',
			'email' => 'foo@bar.com',
			'insuranceNumer' => 1234567
		];

		$expected = [
			'username' => 'foobar',
			'email' => 'foo@bar.com'
		];

		$result = Auth::check('test', $user, ['success' => true, 'checkSession' => false]);
		$this->assertEqual($expected, $result);
		$this->assertEqual($expected, Session::read('test'));

		Auth::reset();

		Auth::config([
			'test' => [
				'adapter' => $this->_classes['mockAuthAdapter'],
			]
		]);

		$user = [
			'id' => '123',
			'username' => 'foobar',
			'password' => 'not!important',
			'email' => 'foo@bar.com',
			'insuranceNumer' => 1234567
		];

		$expected = 123;

		$result = Auth::check('test', $user, ['keyOnly' => true, 'checkSession' => false]);
		$this->assertEqual($expected, $result);
		$this->assertEqual($expected, Session::read('test'));
	}
}

?>