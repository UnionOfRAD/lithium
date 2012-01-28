<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\g11n\multibyte\adapter;

class MockAdapter extends \lithium\core\Object {

	public $testStrlenArgs = array();

	public static function enabled() {
		return true;
	}

	public function strlen($string) {
		$this->testStrlenArgs = func_get_args();
	}
}

?>