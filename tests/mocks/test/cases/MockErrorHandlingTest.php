<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\test\cases;

class MockErrorHandlingTest extends \lithium\test\Unit {

	public $enabled = false;

	public function methods() {
		return $this->enabled ? ['testNotEnoughParams'] : [];
	}

	public function testNotEnoughParams() {
		array_shift();
	}
}

?>