<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\util;

class MockCollectionMarker {

	public $marker = false;

	public $data = 'foo';

	public function mark() {
		$this->marker = true;
		return true;
	}

	public function mapArray() {
		return ['foo'];
	}
}

?>