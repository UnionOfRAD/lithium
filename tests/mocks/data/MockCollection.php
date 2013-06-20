<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockCollection extends \lithium\data\Collection {
	protected function _populate() {}
	protected function _set($data = null, $offset = null, $options = array()) {
		$this->_data[$offset] = $data;
		return $data;
	}
}

?>