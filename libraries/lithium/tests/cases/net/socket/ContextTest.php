<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\socket;

use lithium\net\http\Request;
use lithium\net\socket\Context;

class ContextTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'localhost',
		'port' => 80,
		'timeout' => 30,
		'classes' => array('request' => 'lithium\net\http\Request')
	);

	public function skip() {
		$config = $this->_testConfig;
		$url = "{$config['scheme']}://{$config['host']}";
		$message = "Could not open {$url} - skipping " . __CLASS__;
		$this->skipIf(!fopen($url, 'r'), $message);
	}

	public function testConstruct() {
		$subject = new Context(array('timeout' => 300));
		$this->assertTrue(300, $subject->timeout());
		unset($subject);
	}

	public function testGetSetTimeout() {
		$stream = new Context($this->_testConfig);
		$this->assertEqual(30, $stream->timeout());
		$this->assertEqual(25, $stream->timeout(25));
		$this->assertEqual(25, $stream->timeout());

		$stream->open();
		$this->assertEqual(25, $stream->timeout());

		$result = stream_context_get_options($stream->resource());
		$this->assertEqual(25, $result['http']['timeout']);
	}

	public function testOpen() {
		$stream = new Context($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));
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
		$socket = new Context(array('message' => new Request()));
		$this->assertTrue(is_resource($socket->open()));
	}

	public function testWriteAndRead() {
		$stream = new Context($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));
		$this->assertTrue(is_resource($stream->resource()));
		$this->assertEqual(1, $stream->write());
		$this->assertPattern("/^HTTP/", (string) $stream->read());
	}

	public function testSendWithNull() {
		$stream = new Context($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));
		$result = $stream->send(
			new Request($this->_testConfig),
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertTrue($result instanceof \lithium\net\http\Response);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}

	public function testSendWithArray() {
		$stream = new Context($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));
		$result = $stream->send($this->_testConfig,
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertTrue($result instanceof \lithium\net\http\Response);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}

	public function testSendWithObject() {
		$stream = new Context($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));
		$result = $stream->send(
			new Request($this->_testConfig),
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertTrue($result instanceof \lithium\net\http\Response);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}
}

?>