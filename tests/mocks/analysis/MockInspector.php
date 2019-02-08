<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\analysis;

class MockInspector extends \lithium\analysis\Inspector {

	public static function foo() {
		$args = func_get_args();
		return $args;
	}

}

?>