<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\template;

use lithium\core\Libraries;
use lithium\template\View;
use lithium\action\Response;
use lithium\template\view\adapter\Simple;
use lithium\tests\mocks\template\MockView;
use lithium\tests\mocks\template\view\adapters\TestRenderer;

class ViewTest extends \lithium\test\Unit {

	protected $_view = null;

	public function setUp() {
		$this->_view = new View();
	}

	public function testInitialization() {
		$expected = new Simple();
		$this->_view = new MockView(['renderer' => $expected]);
		$result = $this->_view->renderer();
		$this->assertEqual($expected, $result);
	}

	public function testInitializationWithBadLoader() {
		$expected = "Class `Badness` of type `adapter.template.view` not found.";
		$this->assertException($expected, function() {
			new View(['loader' => 'Badness']);
		});
	}

	public function testInitializationWithBadRenderer() {
		$expected = "Class `Badness` of type `adapter.template.view` not found.";
		$this->assertException($expected, function() {
			new View(['renderer' => 'Badness']);
		});
	}

	public function testEscapeOutputFilter() {
		$h = $this->_view->outputFilters['h'];
		$expected = '&lt;p&gt;Foo, Bar &amp; Baz&lt;/p&gt;';
		$result = $h('<p>Foo, Bar & Baz</p>');
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that the output-escaping handler correctly inherits its encoding from the `Response`
	 * object, if provided.
	 */
	public function testEscapeOutputFilterWithInjectedEncoding() {
		$message = "Multibyte string support must be enabled to test character encodings.";
		$this->skipIf(!function_exists('mb_convert_encoding'), $message);

		$string = "Joël";

		$response = new Response();
		$response->encoding = 'UTF-8';
		$view = new View(compact('response'));
		$handler = $view->outputFilters['h'];
		$this->assertTrue(mb_check_encoding($handler($string), "UTF-8"));

		$response = new Response();
		$response->encoding = 'ISO-8859-1';
		$view = new View(compact('response'));
		$handler = $view->outputFilters['h'];
		$this->assertTrue(mb_check_encoding($handler($string), "ISO-8859-1"));
	}

	public function testBasicRenderModes() {
		$view = new View(['loader' => 'Simple', 'renderer' => 'Simple']);

		$result = $view->render('template', ['content' => 'world'], [
			'template' => 'Hello {:content}!'
		]);
		$expected = 'Hello world!';
		$this->assertEqual($expected, $result);

		$result = $view->render(['element' => 'Logged in as: {:name}.'], [
			'name' => "Cap'n Crunch"
		]);
		$expected = "Logged in as: Cap'n Crunch.";
		$this->assertEqual($expected, $result);

		$result = $view->render('element', ['name' => "Cap'n Crunch"], [
			'element' => 'Logged in as: {:name}.'
		]);
		$expected = "Logged in as: Cap'n Crunch.";
		$this->assertEqual($expected, $result);

		$xmlHeader = '<' . '?xml version="1.0" ?' . '>' . "\n";
		$result = $view->render('all', ['type' => 'auth', 'success' => 'true'], [
			'layout' => $xmlHeader . "\n{:content}\n",
			'template' => '<{:type}>{:success}</{:type}>'
		]);
		$expected = "{$xmlHeader}\n<auth>true</auth>\n";
		$this->assertEqual($expected, $result);
	}

	public function testTwoStepRenderWithVariableCapture() {
		$view = new View(['loader' => 'Simple', 'renderer' => 'Simple']);

		$result = $view->render(
			[
				['path' => 'element', 'capture' => ['data' => 'foo']],
				['path' => 'template']
			],
			['name' => "Cap'n Crunch"],
			['element' => 'Logged in as: {:name}.', 'template' => '--{:foo}--']
		);
		$this->assertEqual('--Logged in as: Cap\'n Crunch.--', $result);
	}

	public function testFullRenderNoLayout() {
		$view = new View(['loader' => 'Simple', 'renderer' => 'Simple']);
		$result = $view->render('all', ['type' => 'auth', 'success' => 'true'], [
			'template' => '<{:type}>{:success}</{:type}>'
		]);
		$expected = '<auth>true</auth>';
		$this->assertEqual($expected, $result);
	}

	public function testNolayout() {
		$view = new View([
			'loader' => 'lithium\tests\mocks\template\view\adapters\TestRenderer',
			'renderer' => 'lithium\tests\mocks\template\view\adapters\TestRenderer',
			'paths' => [
				'template' => '{:library}/tests/mocks/template/view/adapters/{:template}.html.php',
				'layout' => false
			]
		]);
		$options = [
			'template' => 'testFile',
			'library' => Libraries::get('lithium', 'path')
		];
		$result = $view->render('all', [], $options);
		$expected = 'This is a test.';
		$this->assertEqual($expected, $result);

		$templateData = TestRenderer::$templateData;
		$expectedPath = Libraries::get('lithium', 'path');
		$expectedPath .= '/tests/mocks/template/view/adapters/testFile.html.php';
		$expected = [[
			'type' => 'template',
			'params' => [
				'template' => 'testFile',
				'library' => Libraries::get('lithium', 'path'),
				'type' => 'html'
			],
			'return' => $expectedPath
		]];
		$this->assertEqual($expected, $templateData);

		$renderData = TestRenderer::$renderData;
		$expected = [[
			'template' => $expectedPath,
			'data' => [],
			'options' => [
				'template' => 'testFile',
				'library' => $options['library'],
				'type' => 'html',
				'layout' => null,
				'context' => []
			]
		]];
		$this->assertInstanceOf('Closure', $renderData[0]['data']['h']);
		unset($renderData[0]['data']['h']);
		$this->assertEqual($expected, $renderData);
	}

	public function testElementRenderingOptions() {
		$tmpDir = realpath(Libraries::get(true, 'resources') . '/tmp');
		$this->skipIf(!is_writable($tmpDir), "Can't write to resources directory.");

		$testApp = $tmpDir . '/tests/test_app';
		$viewDir = $testApp . '/views';
		mkdir($viewDir, 0777, true);
		Libraries::add('test_app', ['path' => $testApp]);

		$body = '<?php echo isset($this->_options[$option]) ? $this->_options[$option] : ""; ?>';
		$template = $viewDir . '/template.html.php';

		file_put_contents($template, $body);

		$view = new View([
			'paths' => [
				'template' => '{:library}/views/{:template}.html.php',
				'layout' => false
			]
		]);

		$options = [
			'template' => 'template',
			'library' => 'test_app'
		];
		$result = $view->render('all', ['option' => 'custom'], $options);
		$this->assertIdentical('', $result);
		$result = $view->render('all', ['option' => 'library'], $options);
		$this->assertIdentical('test_app', $result);

		$options = [
			'template' => 'template',
			'library' => 'test_app',
			'custom' => 'custom option'
		];
		$result = $view->render('all', ['option' => 'custom'], $options);
		$this->assertIdentical('custom option', $result);
		$result = $view->render('all', ['option' => 'library'], $options);
		$this->assertIdentical('test_app', $result);

		Libraries::remove('test_app');
		$this->_cleanUp();
	}

	public function testContextWithElementRenderingOptions() {
		$tmpDir = realpath(Libraries::get(true, 'resources') . '/tmp');
		$this->skipIf(!is_writable($tmpDir), "Can't write to resources directory.");

		$testApp = $tmpDir . '/tests/test_app';
		$viewDir = $testApp . '/views';
		mkdir($viewDir . '/elements', 0777, true);
		Libraries::add('test_app', ['path' => $testApp]);

		$testApp2 = $tmpDir . '/tests/test_app2';
		$viewDir2 = $testApp2 . '/views';
		mkdir($viewDir2 . '/elements', 0777, true);
		Libraries::add('test_app2', ['path' => $testApp2]);

		$body = "<?php ";
		$body .= "echo \$this->_render('element', 'element2', [], ";
		$body .= "array('library' => 'test_app2'));";
		$body .= "echo \$this->_render('element', 'element1');";
		$body .= "?>";

		file_put_contents($viewDir . '/template.html.php', $body);
		file_put_contents($viewDir . '/elements/element1.html.php', 'element1');
		file_put_contents($viewDir2 . '/elements/element2.html.php', 'element2');

		$view = new View([
			'compile' => false,
			'paths' => [
				'template' => '{:library}/views/{:template}.html.php',
				'element'  => '{:library}/views/elements/{:template}.html.php',
				'layout'   => false
			]
		]);

		$options = [
			'template' => 'template',
			'library' => 'test_app'
		];

		$result = $view->render('all', [], $options);
		$this->assertIdentical('element2element1', $result);

		$body = "<?php ";
		$body .= "echo \$this->_render('element', 'element1');";
		$body .= "echo \$this->_render('element', 'element2', [], ";
		$body .= "array('library' => 'test_app2'));";
		$body .= "?>";

		file_put_contents($viewDir . '/template.html.php', $body);

		$result = $view->render('all', [], $options);
		$this->assertIdentical('element1element2', $result);

		Libraries::remove('test_app');
		Libraries::remove('test_app2');
		$this->_cleanUp();
	}
}

?>