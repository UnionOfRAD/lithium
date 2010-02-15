<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test\reporter;

use \lithium\net\http\Router;
use \lithium\action\Request;
use \lithium\test\reporter\Html;
use \lithium\tests\mocks\test\reporter\MockHtml;

class HtmlTest extends \lithium\test\Unit {

	public function setUp() {
		$this->html = new Html();
		$this->mock = new MockHtml();
		$this->_routes = Router::get();
		Router::connect(null);
		Router::connect('/test/{:args}', array('controller' => '\lithium\test\Controller'));
		Router::connect('/test', array('controller' => '\lithium\test\Controller'));
		$this->request = new Request(array(
			'base' => null,
			'env' => array('PHP_SELF' => '/', 'DOCUMENT_ROOT' => '/')
		));
	}

	public function tearDown() {
		Router::connect(null);

		foreach ($this->_routes as $route) {
			Router::connect($route);
		}
	}

	public function testMenuWithoutData() {
		$expected = '<ul></ul>';
		$result = $this->html->menu(array());
		$this->assertEqual($expected, $result);
	}

	public function testFormatGroup() {
		$expected = '<ul><li><a href="/test/lithium/tests">lithium</a>';
		$expected .= '<ul><li><a href="/test/lithium/tests/cases">cases</a>';
		$expected .= '<ul><li><a href="/test/lithium/tests/cases/core">core</a>';
		$expected .= '<ul><li><a href="/test/lithium/tests/cases/core/LibrariesTest">'
			. 'LibrariesTest</a></li>';
		$expected .= '</ul></li></ul></li></ul></li></ul>';

		$result = $this->html->menu(array('lithium\tests\cases\core\LibrariesTest'), array(
			'tree' => true, 'request' => $this->request
		));
		$this->assertEqual($expected, $result);
	}

	public function testFormatCase() {
		$tests = array('lithium\tests\cases\test\reporter\HtmlTest');
		$expected = '<ul><li><a href="/test/lithium/tests/cases/test/reporter/HtmlTest">'
			. 'HtmlTest</a></li></ul>';
		$result = $this->html->menu($tests, array('request' => $this->request));
		$this->assertEqual($expected, $result);
	}

	public function testFormatCaseWithRequestParams() {
		$this->request->params = array('args' => array('lithium', 'tests', 'cases'));

		$tests = array('lithium\tests\cases\test\reporter\HtmlTest');
		$expected = '<ul><li><a href="/test/lithium/tests/cases/test/reporter/HtmlTest">'
			. 'HtmlTest</a></li></ul>';
		$result = $this->html->menu($tests, array('request' => $this->request));
		$this->assertEqual($expected, $result);
	}

	public function testResult() {
		$stats = array(
			'success' => false,
			'passes' => 1, 'asserts' => 2, 'fails' => 1, 'exceptions' => 0
		);
		$expected = "<div class=\"test-result test-result-fail\">";
		$expected .= "1 / 2 passes, 1 fail and 0 exceptions";
		$expected .= "</div>";
		$result = $this->mock->result($stats);
		$this->assertEqual($expected, $result);
	}

	public function testFail() {
		$fail = array(
			'assertion' => 'assertEqual',
			'class' => 'MockTest', 'method' => 'testNothing', 'line' => 8,
			'message' => 'the message',
		);
		$expected = "<div class=\"test-assert test-assert-failed\">";
		$expected .= "Assertion 'assertEqual' failed in MockTest::testNothing() on line 8: ";
		$expected .= "<span class=\"content\">the message</span>";
		$expected .= "</div>";
		$result = $this->mock->fail($fail);
		$this->assertEqual($expected, $result);
	}

	public function testException() {
		$exception = array(
			'class' => 'MockTest', 'method' => 'testNothing', 'line' => 8,
			'message' => 'the message', 'trace' => 'the trace'
		);

		$expected = "<div class=\"test-exception\">";
		$expected .= "Exception thrown in MockTest::testNothing() on line 8: ";
		$expected .= "<span class=\"content\">the message</span>";
		$expected .= "Trace: <span class=\"trace\">the trace</span>";
		$expected .= "</div>";

		$result = $this->mock->exception($exception);
		$this->assertEqual($expected, $result);
	}

	public function testSkip() {
		$exception = array(
			'trace' => array(array(), array(
				'class' => 'MockTest', 'function' => 'testNothing', 'line' => 8
			)),
			'message' => 'skip this test',
		);
		$expected = "<div class=\"test-skip\">";
		$expected .= "Skip MockTest::testNothing() on line 8: ";
		$expected .= "<span class=\"content\">skip this test</span>";
		$expected .= "</div>";
		$result = $this->mock->skip($exception);
		$this->assertEqual($expected, $result);
	}
}

?>