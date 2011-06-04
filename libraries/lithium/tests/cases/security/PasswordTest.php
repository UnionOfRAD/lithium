<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\security;

use lithium\security\Password;

class PasswordTest extends \lithium\test\Unit {

	/**
	 * testPassword method
	 */
	public function testPassword() {
		$pass = 'Lithium rocks!';

		$bfSalt = "{^\\$2a\\$06\\$[0-9A-Za-z./]{22}$}";
		$bfHash = "{^\\$2a\\$06\\$[0-9A-Za-z./]{53}$}";

		$xdesSalt = "{^_zD..[0-9A-Za-z./]{4}$}";
		$xdesHash = "{^_zD..[0-9A-Za-z./]{15}$}";

		$md5Salt = "{^\\$1\\$[0-9A-Za-z./]{8}$}";
		$md5Hash = "{^\\$1\\$[0-9A-Za-z./]{8}\\$[0-9A-Za-z./]{22}$}";

		// Make it faster than the default settings, else we'll be there tomorrow
		foreach (array('bf' => 6, 'xdes' => 10, 'md5' => null) as $method => $log2) {
			$salts = array();
			$hashes = array();
			$count = 20;
			$saltPattern = ${$method . 'Salt'};
			$hashPattern = ${$method . 'Hash'};

			for ($i = 0; $i < $count; $i++) {
				$salt = Password::salt($method, $log2);
				$this->assertPattern($saltPattern, $salt);
				$this->assertFalse(in_array($salt, $salts));
				$salts[] = $salt;

				$hash = Password::hash($pass, $salt);
				$this->assertPattern($hashPattern, $hash);
				$this->assertEqual(substr($hash, 0, strlen($salt)), $salt);
				$this->assertFalse(in_array($hash, $hashes));
				$hashes[] = $hash;

				$this->assertTrue(Password::check($pass, $hash));
			}
		}
	}

	/**
	 * testPasswordMaxLength method
	 */
	public function testPasswordMaxLength() {
		foreach (array('bf' => 72) as $method => $length) {
			$salt = Password::salt($method);
			$pass = str_repeat('a', $length);
			$this->assertIdentical(Password::hash($pass, $salt), Password::hash($pass . 'a', $salt));
		}
	}
}

?>