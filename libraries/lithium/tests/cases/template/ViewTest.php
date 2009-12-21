<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template;

use \lithium\template\View;
use \lithium\template\view\adapter\Simple;
use \lithium\g11n\Catalog;
use \lithium\g11n\catalog\adapter\Memory;

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

	public function testTranslationOutputFilters() {
		$backup = Catalog::config()->to('array');
		Catalog::reset();
		Catalog::config(array(
			'runtime' => array('adapter' => new Memory())
		));
		$data = array(
			'root' => function($n) { return $n == 1 ? 0 : 1; }
		);
		Catalog::write('message.plural', $data, array('name' => 'runtime'));

		$data = array(
			'de' => array(
				'house' => array('Haus', 'Häuser')
		));
		Catalog::write('message.page', $data, array('name' => 'runtime'));

		$t = $this->_view->outputFilters['t'];
		$tn = $this->_view->outputFilters['tn'];

		$expected = 'Haus';
		$result = $t('house', array('locale' => 'de'));
		$this->assertEqual($expected, $result);

		$expected = 'Haus';
		$result = $tn('house', 'houses', 1, array('locale' => 'de'));
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = $tn('house', 'houses', 3, array('locale' => 'de'));
		$this->assertEqual($expected, $result);

		Catalog::reset();
		Catalog::config($backup);
	}
}

?>