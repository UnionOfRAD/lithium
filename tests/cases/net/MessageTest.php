<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\net;

use lithium\net\Message;

class MessageTest extends \lithium\test\Unit {

	public $message = null;

	public function setUp() {
		$this->message = new Message();
	}

	public function testBody() {
		$expected = "Part 1";
		$result = $this->message->body('Part 1');
		$this->assertEqual($expected, $result);

		$expected = "Part 1\r\nPart 2";
		$result = $this->message->body('Part 2');
		$this->assertEqual($expected, $result);

		$expected = "Part 1\r\nPart 2\r\nPart 3\r\nPart 4";
		$result = $this->message->body(['Part 3', 'Part 4']);
		$this->assertEqual($expected, $result);

		$expected = ['Part 1', 'Part 2', 'Part 3', 'Part 4'];
		$result = $this->message->body;
		$this->assertEqual($expected, $result);
	}

	public function testBodyBuffer() {
		$expected = ['P', 'a', 'r', 't', ' ', '1'];
		$result = $this->message->body('Part 1', ['buffer' => 1]);
		$this->assertEqual($expected, $result);
	}

	public function testToArray() {
		$expected = [
			'scheme' => 'tcp',
			'host' => 'localhost',
			'port' => null,
			'path' => null,
			'username' => null,
			'password' => null,
			'body' => []
		];
		$result = $this->message->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testToUrl() {
		$expected = "tcp://localhost";
		$result = $this->message->to('url');
		$this->assertEqual($expected, $result);
	}

	public function testToContext() {
		$expected = ['tcp' => ['content' => null, 'ignore_errors' => true]];
		$result = $this->message->to('context');
		$this->assertEqual($expected, $result);
	}

	public function testToString() {
		$expected = "woohoo";
		$this->message->body($expected);
		$result = (string) $this->message;
		$this->assertEqual($expected, $result);

		$result = $this->message->to('string');
		$this->assertEqual($expected, $result);
	}

	public function testConstruct() {
		$expected = [
			'scheme' => 'http',
			'host' => 'localhost',
			'port' => '80',
			'path' => null,
			'username' => null,
			'password' => null,
			'body' => []
		];
		$message = new Message($expected);
		$result = $message->to('array');
		$this->assertEqual($expected, $result);
	}
}

?>