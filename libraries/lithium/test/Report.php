<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

class Report extends \lithium\core\Object {

	/**
	 * Contains an instance of `lithium\test\Group`, which contains all unit tests to be executed
	 * this test run.
	 *
	 * @var object
	 */
	public $group = null;

	/**
	 * An array of fully-namespaced class names representing the filters to be applied to this test
	 * group.
	 *
	 * @var array
	 */
	public $filters = array();

	protected $_startTime = null;

	protected function _init() {
		$this->_startTime = microtime(true);
	}
}

?>