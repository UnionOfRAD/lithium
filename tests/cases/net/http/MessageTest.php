<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\net\http;

use lithium\net\http\Message;

class MessageTest extends \lithium\test\Unit {

	public $message = null;

	public function setUp() {
		$this->message = new Message();
	}

	public function testHeaderKey() {
		$this->message->headers('Host: localhost:80');
		$expected = ['Host: localhost:80'];
		$result = $this->message->headers();
		$this->assertEqual($expected, $result);

		$this->message->headers('Host');
		$expected = 'localhost:80';
		$result = $this->message->headers('Host');
		$this->assertEqual($expected, $result);

		$this->message->headers('Host', false);
		$result = $this->message->headers();
		$this->assertEmpty($result);
	}

	public function testHeaderKeyValue() {
		$this->message->headers('Connection', 'Close');
		$expected = ['Connection: Close'];
		$result = $this->message->headers();
		$this->assertEqual($expected, $result);
	}

	public function testHeaderArrayValue() {
		$this->message->headers(['User-Agent: Mozilla/5.0']);
		$expected = ['User-Agent: Mozilla/5.0'];
		$result = $this->message->headers();
		$this->assertEqual($expected, $result);
	}

	public function testHeaderArrayKeyValue() {
		$this->message->headers(['Cache-Control' => 'no-cache']);
		$expected = ['Cache-Control: no-cache'];
		$result = $this->message->headers();
		$this->assertEqual($expected, $result);
	}

	public function testMultiValueHeader() {
		$this->message->headers([
			'Cache-Control' => [
				'no-store, no-cache, must-revalidate',
				'post-check=0, pre-check=0',
				'max-age=0'
			]
		]);
		$expected = [
			'Cache-Control: no-store, no-cache, must-revalidate',
			'Cache-Control: post-check=0, pre-check=0',
			'Cache-Control: max-age=0'
		];
		$result = $this->message->headers();
		$this->assertEqual($expected, $result);
	}

	public function testType() {
		$this->assertEqual('json', $this->message->type("json"));
		$this->assertEqual('json', $this->message->type());

		$expected = 'json';
		$result = $this->message->type("application/json; charset=UTF-8");
		$this->assertEqual($expected, $result);
	}

	public function testReturnJsonIfNoBufferAndEmptyBody() {
		$this->message->type("json");
		$result = $this->message->body("", ['encode' => true]);
		$this->assertIdentical('[""]', $result);
	}

	public function testReturnMergedJsonWithEmptyBody() {
		$this->message->type("json");
		$result = $this->message->body("", ['encode' => true]);
		$this->assertIdentical('[""]', $result);

		$result = $this->message->body("", ['encode' => true]);
		$this->assertIdentical('["",""]', $result);
	}

	public function testReturnMergedJson() {
		$this->message->type("json");
		$result = $this->message->body(["myvar1" => "val1"], ['encode' => true]);
		$this->assertIdentical('{"myvar1":"val1"}', $result);

		$result = $this->message->body(["myvar2" => "val2"], ['encode' => true]);
		$this->assertIdentical('{"myvar1":"val1","myvar2":"val2"}', $result);
	}

	public function testReturnJsonIfNoBufferAndArrayBody() {
		$this->message->type("json");
		$result = $this->message->body([""], ['encode' => true]);
		$this->assertIdentical('[""]', $result);
	}

	public function testReturnProperlyWithEmptyValues() {
		$this->message->type("json");

		$result = $this->message->body([
			'active' => '0'
		], ['encode' => true]);
		$this->assertIdentical('{"active":"0"}', $result);

		$this->message = new Message();
		$this->message->type("json");

		$result = $this->message->body([
			'myvar' => ''
		], ['encode' => true]);
		$this->assertIdentical('{"myvar":""}', $result);
	}

	public function testEmptyEncodeInJson() {
		$this->message->type("json");
		$result = $this->message->body(null, ['encode' => true]);
		$this->assertIdentical("", $result);
	}

	public function testEmptyArrayEncodeInJson() {
		$this->message->type("json");
		$result = $this->message->body([], ['encode' => true]);
		$this->assertIdentical("[]", $result);
	}

	public function testEmptyJsonDecode() {
		$this->message->type("json");
		$result = $this->message->body("{}", ['decode' => true]);
		$this->assertIdentical([], $result);
	}

	public function testEmptyJsonArrayDecode() {
		$this->message->type("json");
		$result = $this->message->body("[]", ['decode' => true]);
		$this->assertIdentical([], $result);
	}
}

?>