<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\collection;

class MockRecordSet extends \lithium\data\collection\RecordSet {

	protected $_closed = false;

	public function close() {
		$this->_closed = true;
	}

	/**
	 * Convenience method for lazy loading testing.
	 * Reset the `RecordSet` to its inital state after `_construct`.
	 */
	public function reset() {
		if (is_object($this->_result) && method_exists($this->_result, 'rewind')) {
			$this->_closed = false;
			$this->_init = false;
			$this->_started = false;
			$this->_valid = false;
			$this->_data = [];
			$this->_index = [];
			$this->_result->rewind();
			$this->_columns = $this->_columnMap();
			return true;
		}
		return false;
	}

	public function get($var) {
		return $this->{$var};
	}
}

?>