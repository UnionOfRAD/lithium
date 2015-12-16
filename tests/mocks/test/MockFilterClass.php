<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\test;

use lithium\core\Filterable;

class MockFilterClass extends \lithium\core\Object {
	use Filterable;

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