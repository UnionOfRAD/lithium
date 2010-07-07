<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\socket;

use \lithium\net\socket\Context;

class ContextTest extends \lithium\test\Unit {

	public $subject;

	public function setUp() {
		$this->subject = new Context();
	}

	public function tearDown() {
		unset($this->subject);
	}

	public function testConstruct() {
		$subject = new Context(array('timeout' => 300));
		$this->assertTrue(300, $subject->timeout());
		$subject->close();
		unset($subject);
	}

	public function testGetSetTimeout() {
		$this->assertEqual(30, $this->subject->timeout());
		$this->assertEqual(25, $this->subject->timeout(25));
		$this->assertEqual(25, $this->subject->timeout());
	}

	public function testConnect() {
		$this->assertEqual(true, $this->subject->open());
	}

	public function testClose() {
		$this->subject->connection = fopen('php://temp', 'r');
		$this->assertEqual(true, $this->subject->close());
	}

	public function testRead() {

	}

	public function testWrite() {

	}

	public function testEof() {

	}

	public function testEncoding() {

	}

	public function testSend() {
		$this->assertEqual('', $this->subject->send('php://temp'));
	}
}

?>