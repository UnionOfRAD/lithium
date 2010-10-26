<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model\mock_database;

class MockResult extends \lithium\data\source\database\Result {

	public $records = array();

	protected function _close() {
	}

	protected function _next() {
		return next($this->records);
	}
}

?>