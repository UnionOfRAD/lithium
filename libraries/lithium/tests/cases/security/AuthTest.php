<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\security;

use \lithium\security\Auth;

class AuthTest extends \lithium\test\Unit {

	public function testBasicAuthCheck() {
		Auth::config(array(
			'test' => array(
				'adapter' => '\lithium\tests\mocks\security\auth\adapter\MockAuthAdapter'
			)
		));

		$this->assertFalse(Auth::check('test', null));
		$this->assertTrue(Auth::check('test', null, array('success' => true)));
	}
}

?>