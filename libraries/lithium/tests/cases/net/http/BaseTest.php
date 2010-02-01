<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use \lithium\net\http\Base;

class BaseTest extends \lithium\test\Unit {

	public $request = null;

	public function setUp() {
		$this->base = new Base();
	}

	public function testHeaderKey() {
		$expected = array(
			'Host: localhost:80',
		);
		$result = $this->base->headers('Host: localhost:80');
		$this->assertEqual($expected, $result);

		$expected = 'localhost:80';
		$result = $this->base->headers('Host');
		$this->assertEqual($expected, $result);

		$expected = null;
		$result = $this->base->headers('Host', false);
		$this->assertEqual($expected, $result);
	}

	public function testHeaderKeyValue() {
		$expected = array(
			'Connection: Close',
		);
		$result = $this->base->headers('Connection', 'Close');
		$this->assertEqual($expected, $result);
	}

	public function testHeaderArrayValue() {
		$expected = array(
			'User-Agent: Mozilla/5.0 (Lithium)',
		);
		$result = $this->base->headers(array('User-Agent: Mozilla/5.0 (Lithium)'));
		$this->assertEqual($expected, $result);
	}

	public function testHeaderArrayKeyValue() {
		$expected = array(
			'Cache-Control: no-cache'
		);
		$result = $this->base->headers(array('Cache-Control' => 'no-cache'));
		$this->assertEqual($expected, $result);
	}

	public function testBody() {
		$expected = "Part 1";
		$result = $this->base->body('Part 1');
		$this->assertEqual($expected, $result);

		$expected = "Part 1\r\nPart 2";
		$result = $this->base->body('Part 2');
		$this->assertEqual($expected, $result);

		$expected = "Part 1\r\nPart 2\r\nPart 3\r\nPart 4";
		$result = $this->base->body(array('Part 3', 'Part 4'));
		$this->assertEqual($expected, $result);

		$expected = array('Part 1', 'Part 2', 'Part 3', 'Part 4');
		$result = $this->base->body;
		$this->assertEqual($expected, $result);
	}
}

?>