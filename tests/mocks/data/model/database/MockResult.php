<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model\database;

class MockResult extends \lithium\data\source\Result {

	protected $_records = array();

	protected $_autoConfig = array('resource', 'records');

	public function rewind() {
		$this->_key = $this->_iterator = 0;
		$this->_current = reset($this->_records);
		return parent::rewind();
	}

	/**
	 * Fetches the result from the resource and caches it.
	 *
	 * @return boolean Return `true` on success or `false` if it is not valid.
	 */
	protected function _fetch() {
		if ($this->_iterator >= count($this->_records)) {
			return false;
		}
		$this->_current = current($this->_records);
		$this->_key = $this->_iterator++;
		next($this->_records);
		return true;
	}

	protected function _close() {
	}
}

?>