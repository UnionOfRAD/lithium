<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test\reporter;

use \lithium\test\reporter\Text;
use \lithium\tests\mocks\test\reporter\MockText;

class TextTest extends \lithium\test\Unit {

	public $text = null;

	public $mock = null;

	public function setUp() {
		$this->text = new Text();
		$this->mock = new MockText();
	}

	public function testMenu() {
		$tests = array('lithium\tests\cases\test\reporter\HtmlTest');
		$expected = "\n-case lithium.tests.cases.test.reporter.HtmlTest\n\n";
		$result = $this->text->menu($tests, array('format' => 'text'));
		$this->assertEqual($expected, $result);
	}

	public function testResult() {
		$stats = array('passes' => 1, 'asserts' => 2, 'fails' => 1, 'exceptions' => 0);
		$expected = "1 / 2 passes\n1 fail and 0 exceptions";
		$result = $this->mock->result($stats);
		$this->assertEqual($expected, $result);
	}

	public function testFail() {
		$fail = array(
			'assertion' => 'assertEqual',
			'class' => 'MockTest', 'method' => 'testNothing', 'line' => 8,
			'message' => 'the message',
		);
		$expected = "Assertion `assertEqual` failed in `MockTest::testNothing()` on line 8: ";
		$expected .= "\nthe message";
		$result = $this->mock->fail($fail);
		$this->assertEqual($expected, $result);
	}

	public function testException() {
		$exception = array(
			'class' => 'MockTest', 'method' => 'testNothing', 'line' => 8,
			'message' => 'the message', 'trace' => 'the trace'
		);
		$expected = "Exception thrown in `MockTest::testNothing()` on line 8:\n";
		$expected .= "the message\nTrace: the trace";
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
		$expected = "Skip MockTest::testNothing() on line 8:\n";
		$expected .= "skip this test";
		$result = $this->mock->skip($exception);
		$this->assertEqual($expected, $result);
	}
}

?>