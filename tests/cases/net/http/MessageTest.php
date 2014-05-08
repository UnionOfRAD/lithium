<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2014, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use lithium\net\http\Message;

class MessageTest extends \lithium\test\Unit {

	public $request = null;

	public function setUp() {
		$this->message = new Message();
	}

	public function testHeaderKey() {
		$this->message->headers('Host: localhost:80');
		$expected = array('Host: localhost:80');
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
		$expected = array('Connection: Close');
		$result = $this->message->headers();
		$this->assertEqual($expected, $result);
	}

	public function testHeaderArrayValue() {
		$this->message->headers(array('User-Agent: Mozilla/5.0'));
		$expected = array('User-Agent: Mozilla/5.0');
		$result = $this->message->headers();
		$this->assertEqual($expected, $result);
	}

	public function testHeaderArrayKeyValue() {
		$this->message->headers(array('Cache-Control' => 'no-cache'));
		$expected = array('Cache-Control: no-cache');
		$result = $this->message->headers();
		$this->assertEqual($expected, $result);
	}

	public function testMultiValueHeader() {
		$this->message->headers(array(
			'Cache-Control' => array(
				'no-store, no-cache, must-revalidate',
				'post-check=0, pre-check=0',
				'max-age=0'
			)
		));
		$expected = array(
			'Cache-Control: no-store, no-cache, must-revalidate',
			'Cache-Control: post-check=0, pre-check=0',
			'Cache-Control: max-age=0'
		);
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
		$result = $this->message->body("", array('encode' => true));
		$this->assertIdentical('[""]', $result);
	}

	public function testReturnMergedJsonWithEmptyBody() {
		$this->message->type("json");
		$result = $this->message->body("", array('encode' => true));
		$this->assertIdentical('[""]', $result);

		$result = $this->message->body("", array('encode' => true));
		$this->assertIdentical('["",""]', $result);
	}

	public function testReturnMergedJson() {
		$this->message->type("json");
		$result = $this->message->body(array("myvar1" => "val1"), array('encode' => true));
		$this->assertIdentical('{"myvar1":"val1"}', $result);

		$result = $this->message->body(array("myvar2" => "val2"), array('encode' => true));
		$this->assertIdentical('{"myvar1":"val1","myvar2":"val2"}', $result);
	}

	public function testReturnJsonIfNoBufferAndArrayBody() {
		$this->message->type("json");
		$result = $this->message->body(array(""), array('encode' => true));
		$this->assertIdentical('[""]', $result);
	}

	public function testReturnProperlyWithEmptyValues() {
		$this->message->type("json");
		$result = $this->message->body(array("myvar" => ""), array('encode' => true));
		$this->assertIdentical('{"myvar":""}', $result);
	}

	public function testEmptyEncodeInJson() {
		$this->message->type("json");
		$result = $this->message->body(array(), array('encode' => true));
		$this->assertIdentical("[]", $result);
	}

	public function testEmptyJsonDecode() {
		$this->message->type("json");
		$result = $this->message->body("{}", array('decode' => true));
		$this->assertIdentical(array(), $result);
	}

	public function testEmptyJsonArrayDecode() {
		$this->message->type("json");
		$result = $this->message->body("[]", array('decode' => true));
		$this->assertIdentical(array(), $result);
	}
}

?>