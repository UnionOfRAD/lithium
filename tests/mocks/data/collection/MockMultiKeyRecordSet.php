<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\collection;

class MockMultiKeyRecordSet extends \lithium\data\collection\MultiKeyRecordSet {

	public function close() {
		$this->_closed = true;
	}

	public function get($var) {
		return $this->{$var};
	}
}

?>