<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source\mongo_db;

class MockMongoId {

	protected $_name;

	public function __construct($name) {
		$this->_name = $name;
	}

	public function __toString() {
		return $this->_name;
	}
}

?>