<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\view\adapter;

use \lithium\template\view\adapter\File;

class FileTest extends \lithium\test\Unit {

	protected $_path = '/resources/tmp/tests';

	public function setUp() {
		$template1 = '<' . '?php echo $foo; ?' . '>';
		$template2 = '<' . '?php echo $this["foo"]; ?' . '>';
		file_put_contents(LITHIUM_APP_PATH . "{$this->_path}/template1.html.php", $template1);
		file_put_contents(LITHIUM_APP_PATH . "{$this->_path}/template2.html.php", $template2);
	}

	public function tearDown() {
		unlink(LITHIUM_APP_PATH . "{$this->_path}/template1.html.php");
		unlink(LITHIUM_APP_PATH . "{$this->_path}/template2.html.php");
	}

	public function testRenderingWithExtraction() {
		$file = new File();
		$content = $file->render(LITHIUM_APP_PATH . "{$this->_path}/template1.html.php", array(
			'foo' => 'bar'
		));
		$this->assertEqual('bar', $content);

		$content = $file->render(LITHIUM_APP_PATH . "{$this->_path}/template2.html.php", array(
			'foo' => 'bar'
		));
		$this->assertEqual('bar', $content);
	}

	public function testRenderingWithNoExtraction() {
		$file = new File(array('extract' => false));
		$this->expectException('Undefined variable: foo');
		$content = $file->render(LITHIUM_APP_PATH . "{$this->_path}/template1.html.php", array(
			'foo' => 'bar'
		));
		$this->assertFalse($content);

		$content = $file->render(LITHIUM_APP_PATH . "{$this->_path}/template2.html.php", array(
			'foo' => 'bar'
		));
		$this->assertEqual('bar', $content);
	}

	public function testContextOffsetManipulation() {
		$file = new File();
		$this->assertFalse(isset($file['title']));

		$file['title'] = 'Document Title';
		$this->assertEqual('Document Title', $file['title']);
		$this->assertTrue(isset($file['title']));

		unset($file['title']);
		$this->assertFalse(isset($file['title']));
	}

	public function testTemplateLocating() {
		$file = new File(array('paths' => array(
			'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
		)));

		$template = $file->template('template', array(
			'controller' => 'pages', 'template' => 'home', 'type' => 'html'
		));
		$this->assertPattern('/template_views_pages_home\.html_[0-9]+/', $template);

		$file = new File(array('compile' => false, 'paths' => array(
			'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
		)));
		$template = $file->template('template', array(
			'controller' => 'pages', 'template' => 'home', 'type' => 'html'
		));
		$this->assertPattern('/\/views\/pages\/home\.html\.php$/', $template);

		$template = $file->template('invalid', array('template' => 'foo'));
		$this->assertNull($template);

		$this->expectException('/Template not found/');
		$file->template('template', array(
			'controller' => 'pages', 'template' => 'foo', 'type' => 'html'
		));
	}
}

?>