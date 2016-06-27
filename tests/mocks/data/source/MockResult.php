<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

class MockResult extends \lithium\data\source\Result {

	protected $_data = array();

	protected $_autoConfig = array('data');

	protected function _fetch() {
		if ($this->_data) {
			return array($this->_iterator++, array_shift($this->_data));
		}
	}
}

?>