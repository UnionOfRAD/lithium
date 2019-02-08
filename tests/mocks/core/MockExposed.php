<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\core;

/**
 * @deprecated
 */
class MockExposed extends \lithium\core\Object {

	protected $_internal = 'secret';

	public function tamper() {
		$internal =& $this->_internal;

		return $this->_filter(__METHOD__, [], function() use (&$internal) {
			$internal = 'tampered';
			return true;
		});
	}

	public function get() {
		return $this->_internal;
	}
}

?>