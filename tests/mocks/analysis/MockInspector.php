<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\analysis;

class MockInspector extends \lithium\analysis\Inspector {

	public static function foo() {
		$args = func_get_args();
		return $args;
	}

}

?>