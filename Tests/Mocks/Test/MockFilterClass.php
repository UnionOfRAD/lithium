<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Test;

class MockFilterClass extends \Lithium\Core\Object{
	public function __construct($all = false) {
		if ($all) {
			return true;
		}

		return false;
	}

	public function testFunction() {
		$test = true;

		return $test;
	}
}

?>