<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Test;

use Lithium\Tests\Mocks\Test\MockIntegrationTest;

class IntegrationTest extends \Lithium\Test\Unit {

	public function testIntegrationHaltsOnFail() {
		$test = new MockIntegrationTest();

		$expected = 2;
		$report = $test->run();
		$result = count($report);

		$this->assertEqual($expected, $result);
	}
}

?>