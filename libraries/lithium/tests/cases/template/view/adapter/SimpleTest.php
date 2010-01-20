<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\view\adapter;

use \lithium\template\view\adapter\Simple;

class SimpleTest extends \lithium\test\Unit {

	protected $_simple = null;

	public function setUp() {
		$this->_simple = new Simple();
	}

	public function testFoo() {
		$this->_simple = new Simple();
	}
}

?>