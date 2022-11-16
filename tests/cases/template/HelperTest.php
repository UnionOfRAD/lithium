<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\template;

use lithium\tests\mocks\template\MockHelper;
use lithium\tests\mocks\template\MockRenderer;

class HelperTest extends \lithium\test\Unit {

	public $helper;

	public function setUp() {
		$this->helper = new MockHelper();
	}

	/**
	 * Tests that constructor parameters are properly assigned to protected properties.
	 */
	public function testObjectConstructionWithParameters() {
		$this->assertNull($this->helper->_context);

		$params = [
			'context' => new MockRenderer(),
			'handlers' => ['content' => function($value) { return "\n{$value}\n"; }]
		];
		$helper = new MockHelper($params);
		$this->assertEqual($helper->_context, $params['context']);
	}

	/**
	 * Tests the default escaping for HTML output.  When implementing helpers that do not output
	 * HTML/XML, the `escape()` method should be overridden accordingly.
	 */
	public function testDefaultEscaping() {
		$result = $this->helper->escape('<script>alert("XSS!");</script>');
		$expected = '&lt;script&gt;alert(&quot;XSS!&quot;);&lt;/script&gt;';
		$this->assertEqual($expected, $result);

		$result = $this->helper->escape('<script>//alert("XSS!");</script>', null, [
			'escape' => false
		]);
		$expected = '<script>//alert("XSS!");</script>';
		$this->assertEqual($expected, $result);

		$result = $this->helper->escape([
			'<script>alert("XSS!");</script>', '<script>alert("XSS!");</script>'
		]);
		$expected = [
			'&lt;script&gt;alert(&quot;XSS!&quot;);&lt;/script&gt;',
			'&lt;script&gt;alert(&quot;XSS!&quot;);&lt;/script&gt;'
		];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests unescaped values passed through the escape() method. Unescaped values
	 * should be returned exactly the same as the original value.
	 */
	public function testUnescapedValue() {
		$value  = '<blockquote>"Thou shalt not escape!"</blockquote>';
		$result = $this->helper->escape($value, null, ['escape' => false]);
		$this->assertEqual($value, $result);
	}

	public function testOptions() {
		$defaults = ['value' => null];
		$options = ['value' => 1, 'title' => 'one'];
		$expected = [
			['value' => 1, 'title' => 'one'],
			['title' => 'one']
		];
		$result = $this->helper->testOptions($defaults, $options);
		$this->assertEqual($expected, $result);
	}

	public function testAttributes() {
		$attributes = ['value' => 1, 'title' => 'one'];
		$expected = ' value="1" title="one"';
		$result = $this->helper->attributes($attributes);
		$this->assertEqual($expected, $result);

		$attributes = ' value="1" title="one"';
		$result = $this->helper->attributes('value="1" title="one"');
		$this->assertEqual($expected, $result);

		$attributes = ['checked' => true, 'title' => 'one'];
		$expected = ' checked="checked" title="one"';
		$result = $this->helper->attributes($attributes);
		$this->assertEqual($expected, $result);

		$attributes = ['checked' => false];
		$result = $this->helper->attributes($attributes);
		$this->assertEqual('', $result);
	}

	public function testAttributeEscaping() {
		$attributes = ['checked' => true, 'title' => '<foo>'];
		$expected = ' checked="checked" title="&lt;foo&gt;"';
		$result = $this->helper->attributes($attributes);
		$this->assertEqual($expected, $result);

		$attributes = ['checked' => true, 'title' => '<foo>'];
		$expected = ' checked="checked" title="<foo>"';
		$result = $this->helper->attributes($attributes, null, ['escape' => false]);
		$this->assertEqual($expected, $result);
	}

	public function testAttributeMinimization() {
		$attributes = ['selected' => 1];
		$expected = ' selected="selected"';
		$result = $this->helper->attributes($attributes);
		$this->assertEqual($expected, $result);

		$attributes = ['selected' => true];
		$expected = ' selected="selected"';
		$result = $this->helper->attributes($attributes);
		$this->assertEqual($expected, $result);

		$attributes = ['selected' => 'true'];
		$expected = ' selected="true"';
		$result = $this->helper->attributes($attributes);
		$this->assertEqual($expected, $result);
	}

	public function testInstantiationWithNoContext() {
		$this->helper = new MockHelper();
		$result = $this->helper->testRender(null, "foo {:bar}", ['bar' => 'baz']);
		$this->assertEqual("foo baz", $result);
	}

	public function testRender() {
		$params = [
			'context' => new MockRenderer(),
			'handlers' => ['content' => function($value) { return "\n{$value}\n"; }]
		];
		$helper = new MockHelper($params);
		$config = [
			'title' => 'cool',
			'url' => '/here',
			'options' => ['value' => 1, 'title' => 'one']
		];
		$expected = '<a href="/here" value="1" title="one">cool</a>';
		$result = $helper->testRender('link', 'link', $config);
		$this->assertEqual($expected, $result);

		$handlers = ['path' => function($path) { return "/webroot{$path}"; }];
		$params = ['context' => new MockRenderer(compact('handlers'))];
		$helper = new MockHelper($params);
		$handlers = ['url' => 'path'];
		$expected = '<a href="/webroot/here" value="1" title="one">cool</a>';
		$result = $helper->testRender('link', 'link', $config, compact('handlers'));
		$this->assertEqual($expected, $result);
	}
}

?>