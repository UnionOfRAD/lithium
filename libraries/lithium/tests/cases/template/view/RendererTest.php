<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\view;

use \lithium\template\View;
use \lithium\action\Request;
use \lithium\template\Helper;
use \lithium\template\helper\Html;
use \lithium\template\view\adapter\Simple;

class RendererTest extends \lithium\test\Unit {

	public function setUp() {
		$this->subject = new Simple();
	}

	public function testInitialization() {
		$expected = array('url', 'path', 'options', 'content', 'title', 'scripts', 'styles');
		$result = array_keys($this->subject->handlers());
		$this->assertEqual($expected, $result);

		$expected = array('content', 'title', 'scripts', 'styles');
		$result = array_keys($this->subject->context());
		$this->assertEqual($expected, $result);
	}

	public function testContextQuerying() {
		$expected = array(
			'content' => '', 'title' => '', 'scripts' => array(), 'styles' => array()
		);
		$this->assertEqual($expected, $this->subject->context());
		$this->assertEqual('', $this->subject->context('title'));
		$this->assertEqual(array(), $this->subject->context('scripts'));
		$this->assertEqual(array(), $this->subject->scripts);
		$this->assertNull($this->subject->foo());
		$this->assertFalse(isset($this->subject->foo));

		$this->subject = new Simple(array('context' => array(
			'content' => '', 'title' => '', 'scripts' => array(), 'styles' => array(), 'foo' => '!'
		)));
		$result = $this->subject->foo();
		$this->assertEqual('!', $result);
		$this->assertTrue(isset($this->subject->foo));
	}

	/**
	 * Tests built-in content handlers for generating URLs, paths to static assets, and handling
	 * output of elements written to the request context.
	 *
	 * @return void
	 */
	public function testCoreHandlers() {
		$url = $this->subject->applyHandler(null, null, 'url', array(
			'controller' => 'foo', 'action' => 'bar'
		));
		$this->assertEqual('/foo/bar', $url);

		$helper = new Html();
		$class = get_class($helper);
		$path = $this->subject->applyHandler($helper, "{$class}::script", 'path', 'foo/file');
		$this->assertEqual('/js/foo/file.js', $path);
	}

	public function testHandlerInsertion() {
		$this->subject = new Simple(array('context' => array(
			'content' => '', 'title' => '', 'scripts' => array(), 'styles' => array(), 'foo' => '!'
		)));

		$foo = function($value) { return "Foo: {$value}"; };

		$expected = array('url', 'path', 'options', 'content', 'title', 'scripts', 'styles', 'foo');
		$result = array_keys($this->subject->handlers(compact('foo')));
		$this->assertEqual($expected, $result);

		$result = $this->subject->applyHandler(null, null, 'foo', 'test value');
		$this->assertEqual('Foo: test value', $result);
		$this->assertEqual('Foo: !', $this->subject->foo());

		$this->assertEqual($foo, $this->subject->handlers('foo'));
		$this->assertNull($this->subject->handlers('bar'));

		$bar = function($value) { return "Bar: {$value}"; };

		$this->subject->handlers(compact('bar'));
		$result = $this->subject->applyHandler(null, null, 'bar', 'test value');
		$expected = 'Bar: test value';
		$this->assertEqual($expected, $result);

		$result = $this->subject->bar('test value');
		$this->assertEqual($expected, $result);
	}

	public function testHandlerQuerying() {
		$result = $this->subject->nonExistent('test value');
		$expected = 'test value';
		$this->assertEqual($expected, $result);

		$result = $this->subject->applyHandler(null, null, 'nonExistent', 'test value');
		$this->assertEqual($expected, $result);

		$html = new Html();
		$script = '<script>alert("XSS!");</script>';
		$escaped = '&lt;script&gt;alert(&quot;XSS!&quot;);&lt;/script&gt;';

		$result = $this->subject->applyHandler($html, null, 'title', $script);
		$expected = $escaped;
		$this->assertEqual($expected, $result);

		$result = $this->subject->applyHandler($html, null, 'foo', $script);
		$expected = $script;
		$this->assertEqual($expected, $result);

		$result = $this->subject->applyHandler($html, null, 'bar', $script);
		$this->assertEqual($expected, $result);

		$this->subject->handlers(array('foo' => array($html, 'escape'), 'bar' => 42));

		$result = $this->subject->applyHandler($html, null, 'bar', $script);
		$this->assertEqual($expected, $result);

		$result = $this->subject->applyHandler($html, null, 'foo', $script);
		$expected = $escaped;
		$this->assertEqual($expected, $result);
	}

	public function testHelperLoading() {
		$helper = $this->subject->helper('html');
		$this->assertTrue($helper instanceof Helper);

		$this->expectException('/invalidFoo/');
		$this->assertNull($this->subject->helper('invalidFoo'));
	}

	public function testHelperQuerying() {
		$helper = $this->subject->html;
		$this->assertTrue($helper instanceof Helper);
	}

	public function testTemplateStrings() {
		$result = $this->subject->strings();
		$this->assertTrue(is_array($result));
		$this->assertFalse($result);

		$expected = array('data' => 'The data goes here: {:data}');
		$result = $this->subject->strings($expected);
		$this->assertEqual($expected, $result);

		$result = $this->subject->strings();
		$this->assertEqual($expected, $result);

		$result = $this->subject->strings('data');
		$expected = $expected['data'];
		$this->assertEqual($expected, $result);
	}

	public function testGetters() {
		$this->assertNull($this->subject->request());
		$this->subject = new Simple(array('request' => new Request()));
		$this->assertTrue($this->subject->request() instanceof Request);
	}
}

?>