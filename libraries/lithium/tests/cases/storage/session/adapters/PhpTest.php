<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\session\adapters;

use \lithium\storage\session\adapters\Php;

class PhpTest extends \lithium\test\Unit {

	public function setUp() {
		if (session_id()) {
			session_destroy();
		}
		$this->Php = new Php();

		/* Garbage collection */
		$this->_gc_divisor = ini_get('session.gc_divisor');
		ini_set('session.gc_divisor', '1');

	}

	public function tearDown() {
		if (session_id()) {
			session_destroy();
		}
		/* Revert to original garbage collection probability */
        ini_set('session.gc_divisor', $this->_gc_divisor);

	}

	public function testInit() {
		$id = session_id();
		$this->assertTrue(!empty($id));
		$this->assertEqual(session_cache_limiter(), "must-revalidate");

		$result = $_SESSION['_timestamp'];
		$expected = time();
		$this->assertEqual($expected, $result);

	}

	public function testIsStarted() {
		$result = $this->Php->isStarted();
		$this->assertTrue($result);

		unset($_SESSION);

		$result = $this->Php->isStarted();
		$this->assertFalse($result);

	}
}

?>