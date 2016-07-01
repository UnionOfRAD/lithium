<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\net\socket;

use lithium\net\http\Request;
use lithium\net\socket\Context;

class ContextTest extends \lithium\test\Integration {

	protected $_testConfig = [
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'example.org',
		'port' => 80,
		'timeout' => 4,
		'classes' => [
			'request' => 'lithium\net\http\Request',
			'response' => 'lithium\net\http\Response'
		]
	];

	public function skip() {
		$this->skipIf(!$this->_hasNetwork(), 'No network connection.');
	}

	public function testConstruct() {
		$subject = new Context(['timeout' => 300] + $this->_testConfig);
		$this->assertEqual(300, $subject->timeout());
		unset($subject);
	}

	public function testGetSetTimeout() {
		$subject = new Context($this->_testConfig);
		$this->assertEqual(4, $subject->timeout());
		$this->assertEqual(25, $subject->timeout(25));
		$this->assertEqual(25, $subject->timeout());

		$subject->open();
		$this->assertEqual(25, $subject->timeout());

		$result = stream_context_get_options($subject->resource());
		$this->assertEqual(25, $result['http']['timeout']);
	}

	public function testOpen() {
		$stream = new Context($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
	}

	public function testClose() {
		$stream = new Context($this->_testConfig);
		$this->assertEqual(true, $stream->close());
	}

	public function testEncoding() {
		$stream = new Context($this->_testConfig);
		$this->assertEqual(false, $stream->encoding());
	}

	public function testEof() {
		$stream = new Context($this->_testConfig);
		$this->assertTrue(true, $stream->eof());
	}

	public function testMessageInConfig() {
		$socket = new Context(['message' => new Request($this->_testConfig)]);
		$this->assertInternalType('resource', $socket->open());
	}

	public function testWriteAndRead() {
		$stream = new Context($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$this->assertInternalType('resource', $stream->resource());
		$this->assertEqual(1, $stream->write());
		$this->assertPattern("/^HTTP/", (string) $stream->read());
	}

	public function testSendWithNull() {
		$stream = new Context($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send(
			new Request($this->_testConfig),
			['response' => 'lithium\net\http\Response']
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}

	public function testSendWithArray() {
		$stream = new Context($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send($this->_testConfig,
			['response' => 'lithium\net\http\Response']
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}

	public function testSendWithObject() {
		$stream = new Context($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send(
			new Request($this->_testConfig),
			['response' => 'lithium\net\http\Response']
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}

	public function testContextAdapter() {
		$socket = new Context($this->_testConfig);
		$this->assertNotEmpty($socket->open());
		$response = $socket->send();
		$this->assertInstanceOf('lithium\net\http\Response', $response);

		$expected = 'example.org';
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>Example Domain<\/title>/im", (string) $result);
	}
}

?>