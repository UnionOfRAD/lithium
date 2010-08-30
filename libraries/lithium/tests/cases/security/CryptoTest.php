<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\security;

use \lithium\security\Crypto;

class CryptoTest extends \lithium\test\Unit {
	/**
	 * testRandomGenerator method
	 *
	 * @return void
	 **/
	public function testRandomGenerator() {
		$check = array();
		$count = 50;
		$pattern = "/^[0-9A-Za-z\.\/]+$/";
		for ($i = 0; $i < $count; $i++) {
			$result = Crypto::random(8);
			$this->assertPattern($pattern, Crypto::encode64($result));
			$this->assertFalse(in_array($result, $check));
			$check[] = $result;
		}
	}
}

?>