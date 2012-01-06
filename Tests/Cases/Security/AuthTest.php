<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Security;

use Lithium\Security\Auth;
use Lithium\Storage\Session;

class AuthTest extends \Lithium\Test\Unit {

	public function setUp() {
		Session::config(array(
			'test' => array('adapter' => 'Memory')
		));

		Auth::config(array(
			'test' => array(
				'adapter' => 'Lithium\Tests\Mocks\Security\Auth\Adapter\MockAuthAdapter'
			)
		));
	}

	public function testBasicAuthCheck() {
		$this->assertFalse(Auth::check('test'));
		$user = array('user' => 'bob');

		$result = Auth::check('test', $user, array('success' => true));
		$this->assertEqual($user, $result);

		$result = Session::read('test');
		$this->assertEqual($user, $result);

		$result = Auth::check('test');
		$this->assertEqual($user, $result);
	}

	public function testAuthLogout() {
		$user = array('user' => 'bob');

		$result = Auth::check('test', $user, array('success' => true));
		$this->assertEqual($user, $result);

		$result = Auth::check('test');
		$this->assertEqual($user, $result);

		Auth::clear('test');
		$this->assertFalse(Auth::check('test'));
	}

	public function testManualSessionInitialization() {
		$this->assertFalse(Auth::check('test'));
		$user = array('id' => 13, 'user' => 'bob');

		$this->assertTrue(Auth::set('test', $user));

		$result = Auth::check('test');
		$this->assertEqual($user, $result);
	}

	public function testManualSessionFail() {
		$this->assertFalse(Auth::check('test'));
		$user = array('id' => 13, 'user' => 'bob');

		$this->assertFalse(Auth::set('test', $user, array('fail' => true)));
		$this->assertFalse(Auth::check('test'));
	}

	public function testNoConfigurations() {
		Auth::reset();
		$this->assertIdentical(array(), Auth::config());
		$this->expectException("Configuration `user` has not been defined.");
		Auth::check('user');
	}
}

?>