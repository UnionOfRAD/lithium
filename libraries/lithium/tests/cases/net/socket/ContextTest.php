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

	public function testGetSetTimeout() {
		$this->assertEqual(30, $this->subject->timeout());
		$this->assertEqual(25, $this->subject->timeout(25));
		$this->assertEqual(25, $this->subject->timeout());
	}
}

?>