<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\collection;

class MockMultiKeyRecordSet extends \lithium\data\collection\MultiKeyRecordSet {

	protected $_closed = false;

	public function close() {
		$this->_closed = true;
	}

	public function get($var) {
		return $this->{$var};
	}
}

?>