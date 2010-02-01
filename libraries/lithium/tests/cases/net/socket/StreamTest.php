<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\socket;

use \lithium\tests\mocks\net\socket\MockStream;

class StreamTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2
	);

	public function testAllMethodsNoConnection() {
		$stream = new MockStream(array('protocol' => null));
		$this->assertFalse($stream->open());
		$this->assertTrue($stream->close());
		$this->assertFalse($stream->timeout(2));
		$this->assertFalse($stream->encoding('UTF-8'));
		$this->assertFalse($stream->write(null));
		$this->assertFalse($stream->read());
		$this->assertTrue($stream->eof());
		$this->assertNull($stream->send(''));
	}

	public function testOpen() {
		$stream = new MockStream($this->_testConfig);
		$result = $stream->open();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testClose() {
		$stream = new MockStream($this->_testConfig);
		$result = $stream->open();
		$this->assertTrue($result);

		$result = $stream->close();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertFalse(is_resource($result));
	}

	public function testTimeout() {
		$stream = new MockStream($this->_testConfig);
		$result = $stream->open();
		$stream->timeout(10);
		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testEncoding() {
		$stream = new MockStream($this->_testConfig);
		$result = $stream->open();
		$stream->encoding('UTF-8');
		$result = $stream->resource();
		$this->assertTrue(is_resource($result));

		$stream = new MockStream($this->_testConfig + array('encoding' => 'UTF-8'));
		$result = $stream->open();
		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testWriteAndRead() {
		$stream = new MockStream($this->_testConfig);
		$result = $stream->open();
		$data = "GET / HTTP/1.1\r\n";
		$data .= "Host: localhost\r\n";
		$data .= "Connection: Close\r\n\r\n";
		$this->assertTrue($stream->write($data));

		$result = $stream->eof();
		$this->assertFalse($result);

		$result = $stream->read();
		$this->assertPattern("/^HTTP/", $result);
	}

	public function testSend() {
		$stream = new MockStream($this->_testConfig);
		$result = $stream->open();
		$data = "GET / HTTP/1.1\r\n";
		$data .= "Host: localhost\r\n";
		$data .= "Connection: Close\r\n\r\n";

		$result = $stream->send($data, array('classes' => array(
			'response' => '\lithium\net\http\Response'
		)));
		$this->assertNotEqual(null, $result);
	}
}

?>