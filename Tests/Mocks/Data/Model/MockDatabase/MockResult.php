<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data\Model\MockDatabase;

class MockResult extends \Lithium\Data\Source\Database\Result {

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