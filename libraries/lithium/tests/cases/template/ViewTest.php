<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template;

use \lithium\template\View;

class ViewTest extends \lithium\test\Unit {

	protected $_view = null;

	public function setUp() {
		$this->_view = new View();
	}

	public function testInitialization() {
		$this->_view = new View();
	}

	public function testInitializationWithBadClasses() {
		$this->expectException('Template adapter Badness not found');
		new View(array('loader' => 'Badness'));
		$this->expectException('Template adapter Badness not found');
		new View(array('renderer' => 'Badness'));
	}
}

?>