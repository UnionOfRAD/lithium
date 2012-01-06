<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Template\View\Adapter;

use Lithium\Core\Libraries;
use Lithium\Template\View\Adapter\File;

class FileTest extends \Lithium\Test\Unit {

	protected $_path;

	public function setUp() {
		$this->_path = Libraries::get(true, 'resources') . '/tmp/tests';

		$template1 = '<' . '?php echo $foo; ?' . '>';
		$template2 = '<' . '?php echo $this["foo"]; ?' . '>';
		file_put_contents("{$this->_path}/template1.html.php", $template1);
		file_put_contents("{$this->_path}/template2.html.php", $template2);
	}

	public function tearDown() {
		unlink("{$this->_path}/template1.html.php");
		unlink("{$this->_path}/template2.html.php");
	}

	public function testRenderingWithExtraction() {
		$file = new File();

		$content = $file->render("{$this->_path}/template1.html.php", array('foo' => 'bar'));
		$this->assertEqual('bar', $content);

		$content = $file->render("{$this->_path}/template2.html.php", array('foo' => 'bar'));
		$this->assertEqual('bar', $content);
	}

	public function testRenderingWithNoExtraction() {
		$file = new File(array('extract' => false));
		$this->expectException('Undefined variable: foo');
		$content = $file->render("{$this->_path}/template1.html.php", array('foo' => 'bar'));
		$this->assertFalse($content);

		$content = $file->render("{$this->_path}/template2.html.php", array('foo' => 'bar'));
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

		$this->expectException('/Template not found/');
		$file->template('template', array(
			'controller' => 'pages', 'template' => 'foo', 'type' => 'html'
		));
	}

	public function testInvalidTemplateType() {
		$file = new File(array('compile' => false, 'paths' => array(
			'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
		)));

		$this->expectException("Invalid template type 'invalid'.");
		$template = $file->template('invalid', array('template' => 'foo'));
	}
}

?>