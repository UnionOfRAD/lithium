<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model\database;

class MockResult extends \lithium\data\source\Result {

	protected $_records = [];

	protected $_autoConfig = ['resource', 'records'];

	protected function _fetch() {
		if ($this->_records) {
			return [$this->_iterator++, array_shift($this->_records)];
		}
	}
}

?>