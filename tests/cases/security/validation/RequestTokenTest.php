<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\security\validation;

use lithium\action\Request;
use lithium\security\Password;
use lithium\security\validation\RequestToken;

class RequestTokenTest extends \lithium\test\Unit {

	protected static $_storage = [];

	public function setUp() {
		static::$_storage = [];
		RequestToken::config(['classes' => ['session' => __CLASS__]]);
	}

	public function tearDown() {
		RequestToken::config(['classes' => ['session' => 'lithium\storage\Session']]);
	}

	public static function read($key) {
		return isset(static::$_storage[$key]) ? static::$_storage[$key] : null;
	}

	public static function write($key, $val) {
		return static::$_storage[$key] = $val;
	}

	/**
	 * Tests that class dependencies can be reconfigured.
	 */
	public function testConfiguration() {
		$expected = ['classes' => ['session' => __CLASS__]];
		$this->assertEqual($expected, RequestToken::config());

		$new = ['classes' => ['session' => 'lithium\storage\Session']];
		RequestToken::config($new);
		$this->assertEqual($new, RequestToken::config());
	}

	/**
	 * Tests proper generation of secure tokens.
	 */
	public function testTokenGeneration() {
		$token = RequestToken::get();
		$this->assertPattern('/^[a-f0-9]{128}$/', $token);
		$this->assertEqual(['security.token' => $token], static::$_storage);

		$newToken = RequestToken::get();
		$this->assertEqual($token, $newToken);

		$reallyNewToken = RequestToken::get(['regenerate' => true]);
		$this->assertPattern('/^[a-f0-9]{128}$/', $reallyNewToken);
		$this->assertNotEqual($token, $reallyNewToken);
		$this->assertEqual(['security.token' => $reallyNewToken], static::$_storage);
	}

	public function testTokenGenerationWithProvidedAlgo() {
		$token = RequestToken::get(['type' => 'sha512', 'regenerate' => true]);
		$this->assertPattern('/^[a-f0-9]{128}$/', $token);

		$token = RequestToken::get(['type' => 'md5', 'regenerate' => true]);
		$this->assertPattern('/^[a-f0-9]{32}$/', $token);
	}

	/**
	 * Tests that a random sequence of keys and tokens properly match one another.
	 */
	public function testKeyMatching() {
		for ($i = 0; $i < 4; $i++) {
			$token = RequestToken::get(['regenerate' => true]);

			for ($j = 0; $j < 4; $j++) {
				$key = Password::hash($token);
				$this->assertTrue(RequestToken::check($key));
			}
		}
	}

	/**
	 * Tests extracting a key from a `Request` object and matching it against a token.
	 */
	public function testTokenFromRequestObject() {
		$request = new Request(['data' => [
			'security' => ['token' => RequestToken::key()]
		]]);
		$this->assertTrue(RequestToken::check($request));
	}
}

?>