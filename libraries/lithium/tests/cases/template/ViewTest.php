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
		$this->expectException("Class 'Badness' of type 'adapter.template.view' not found.");
		new View(array('loader' => 'Badness'));
		$this->expectException("Class 'Badness' of type 'adapter.template.view' not found.");
		new View(array('renderer' => 'Badness'));
	}

	public function testEscapeOutputFilter() {
		$h = $this->_view->outputFilters['h'];
		$expected = '&lt;p&gt;Foo, Bar &amp; Baz&lt;/p&gt;';
		$result = $h('<p>Foo, Bar & Baz</p>');
		$this->assertEqual($expected, $result);
	}

	public function testBasicRenderModes() {
		$view = new View(array('loader' => 'Simple', 'renderer' => 'Simple'));

		$result = $view->render('template', array('content' => 'world'), array(
			'template' => 'Hello {:content}!'
		));
		$expected = 'Hello world!';
		$this->assertEqual($expected, $result);

		$result = $view->render(array('element' => 'Logged in as: {:name}.'), array(
			'name' => "Cap'n Crunch"
		));
		$expected = "Logged in as: Cap'n Crunch.";
		$this->assertEqual($expected, $result);

		$xmlHeader = '<' . '?xml version="1.0" ?' . '>' . "\n";
		$result = $view->render('all', array('type' => 'auth', 'success' => 'true'), array(
			'layout' => $xmlHeader . "\n{:content}\n",
			'template' => '<{:type}>{:success}</{:type}>'
		));
		$expected = "{$xmlHeader}\n<auth>true</auth>\n";
		$this->assertEqual($expected, $result);
	}

	public function testFullRenderNoLayout() {
		$view = new View(array('loader' => 'Simple', 'renderer' => 'Simple'));
		$result = $view->render('all', array('type' => 'auth', 'success' => 'true'), array(
			'template' => '<{:type}>{:success}</{:type}>'
		));
		$expected = '<auth>true</auth>';
		$this->assertEqual($expected, $result);
	}
}

?>