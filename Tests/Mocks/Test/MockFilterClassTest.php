<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Test;

use Lithium\Tests\Mocks\Test\MockFilterClass;

class MockFilterClassTest extends \Lithium\Test\Unit {

	public function testNothing() {
		$coverage = new MockFilterClass();

		$this->assertTrue(true);
	}
}

?>