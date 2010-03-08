<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\test\filters;

use lithium\tests\mocks\test\filters\MockCoverageClass;

class MockCoverageClassTest extends \lithium\test\Unit {

	public function testNothing() {
		$coverage = new MockCoverageClass();

		$this->assertTrue(true);
	}
}

?>