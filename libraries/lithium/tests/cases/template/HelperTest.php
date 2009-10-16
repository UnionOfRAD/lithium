<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template;

use \stdClass;
use \lithium\template\Helper;
use \lithium\template\view\Renderer;

class MyHelper extends Helper {

	/**
	 * Hack to expose protected properties for testing.
	 *
	 * @param string $property 
	 * @return mixed
	 */
	public function __get($property) {
		return isset($this->{$property}) ? $this->{$property} : null;
	}
}

class MyRenderer extends Renderer {}

class HelperTest extends \lithium\test\Unit {

	public function setUp() {
		$this->helper = new MyHelper();
	}

	/**
	 * Tests that constructor parameters are properly assigned to protected properties.
	 *
	 * @return void
	 */
	public function testObjectConstructionWithParameters() {
		$this->assertNull($this->helper->_context);

		$params = array(
			'context' => new MyRenderer(),
			'handlers' => array('content' => function($value) { return "\n{$value}\n"; })
		);
		$this->helper = new MyHelper($params);

		$this->assertEqual($this->helper->_context, $params['context']);
	}

	/**
	 * Tests the default escaping for HTML output.  When implementing helpers that do not output
	 * HTML/XML, the `escape()` method should be overridden accordingly.
	 *
	 * @return void
	 */
	public function testDefaultEscaping() {
		$result = $this->helper->escape('<script>alert("XSS!");</script>');
		$expected = '&lt;script&gt;alert(&quot;XSS!&quot;);&lt;/script&gt;';
		$this->assertEqual($expected, $result);

		$result = $this->helper->escape('<script>//alert("XSS!");</script>', null, array(
			'escape' => false
		));
		$expected = '<script>//alert("XSS!");</script>';
		$this->assertEqual($expected, $result);
	}
	
	/**
	 * Tests unescaped values passed through the escape() method. Unescaped values
	 * should be returned exactly the same as the original value.
	 *
	 * @return void
	 */
	public function testUnescapedValue() {
		$value  = '<blockquote>"Thou shalt not escape!"</blockquote>';
		$result = $this->helper->escape($value, null, array('escape' => false));
		$this->assertEqual($value, $result);
	}
}

?>