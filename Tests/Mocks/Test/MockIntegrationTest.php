<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Test;

class MockIntegrationTest extends \Lithium\Test\Integration {

	public function testPass() {
		$this->assertTrue(true);
	}

	public function testFail() {
		$this->assertTrue(false);
	}

	public function testAnotherPass() {
		$this->assertTrue(true);
	}
}

?>