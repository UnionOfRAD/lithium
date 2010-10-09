<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\socket;

use lithium\net\http\Request;
use lithium\net\http\Response;
use lithium\net\socket\Context;

class ContextTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'localhost',
		'port' => 80,
		'timeout' => 30
	);

	protected $_testUrl = 'http://localhost';

	public function setUp() {
		$this->socket = new Context($this->_testConfig);
		$message = "Could not open {$this->_testUrl} - skipping " . __CLASS__;
		$this->skipIf(!fopen($this->_testUrl, 'r'), $message);
	}

	public function tearDown() {
		unset($this->socket);
	}

	public function testConstruct() {
		$subject = new Context(array('timeout' => 300));
		$this->assertTrue(300, $subject->timeout());
		$subject->close();
		unset($subject);
	}

	public function testGetSetTimeout() {
		$this->assertEqual(30, $this->socket->timeout());
		$this->assertEqual(25, $this->socket->timeout(25));
		$this->assertEqual(25, $this->socket->timeout());

		$this->socket->open();
		$this->assertEqual(25, $this->socket->timeout());

		$result = stream_context_get_options($this->socket->resource());
		$this->assertEqual(25, $result['http']['timeout']);
	}

	public function testOpen() {
		$this->assertTrue(is_resource($this->socket->open()));
	}

	public function testClose() {
		$this->assertEqual(true, $this->socket->close());
	}

	public function testEncoding() {
		$this->assertEqual(false, $this->socket->encoding());
	}

	public function testMessageInConfig() {
		$socket = new Context(array('message' => new Request()));
		$this->assertTrue(is_resource($socket->open()));
	}

	public function testWriteAndRead() {
		$stream = new Context($this->_testConfig);
		$this->assertTrue(is_resource($stream->open()));
		$this->assertTrue(is_resource($stream->resource()));

		$response = $stream->send(new Request(), array('response' => 'lithium\net\http\Response'));
		$this->assertTrue($response instanceof Response);

		$this->assertEqual(trim(file_get_contents($this->_testUrl)), trim($response->body()));
		$this->assertTrue($stream->eof());
	}
}

?>