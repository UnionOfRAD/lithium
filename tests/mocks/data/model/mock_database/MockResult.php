<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model\mock_database;

class MockResult extends \lithium\data\source\database\Result {

	public $records = array();

	public function __construct(array $config = array()) {
		$defaults = array('resource' => true);
		parent::__construct($config + $defaults);
	}

	protected function _close() {
	}

	protected function _prev() {
		return prev($this->records);
	}

	protected function _next() {
		return next($this->records);
	}
}

?>