<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template;

use \lithium\template\View;
use \lithium\g11n\catalog\adapter\Memory;
use \lithium\template\view\adapter\Simple;

class TestViewClass extends \lithium\template\View {

	public function renderer() {
		return $this->_config['renderer'];
	}
}

class ViewTest extends \lithium\test\Unit {

	protected $_view = null;

	public function setUp() {
		$this->_view = new View();
	}

	public function testInitialization() {
		$expected = new Simple();
		$this->_view = new TestViewClass(array('renderer' => $expected));
		$result = $this->_view->renderer();
		$this->assertEqual($expected, $result);
	}

	public function testInitializationWithBadClasses() {
		$this->expectException('Template adapter Badness not found');
		new View(array('loader' => 'Badness'));
		$this->expectException('Template adapter Badness not found');
		new View(array('renderer' => 'Badness'));
	}

	public function testEscapeOutputFilter() {
		$h = $this->_view->outputFilters['h'];
		$expected = '&lt;p&gt;Foo, Bar &amp; Baz&lt;/p&gt;';
		$result = $h('<p>Foo, Bar & Baz</p>');
		$this->assertEqual($expected, $result);
	}
}

?>