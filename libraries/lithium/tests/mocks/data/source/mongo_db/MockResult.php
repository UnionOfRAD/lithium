<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\mongo_db;

class MockResult extends \lithium\data\source\mongo_db\Result {

	protected $_data = array(
		false,
		array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar'),
		array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo'),
		array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib')
	);

	public function next() {
		return $this->_next();
	}

	protected function _close() {
	}

	protected function _next() {
		return next($this->_data) ?: null;
	}
}

?>