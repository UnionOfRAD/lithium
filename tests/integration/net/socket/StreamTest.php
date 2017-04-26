<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\net\socket;

use lithium\net\http\Request;
use lithium\net\socket\Stream;

class StreamTest extends \lithium\test\Integration {

	protected $_testConfig = array(
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'example.org',
		'port' => 80,
		'timeout' => 2,
		'classes' => array(
			'request' => 'lithium\net\http\Request',
			'response' => 'lithium\net\http\Response'
		)
	);

	public function skip() {
		$this->skipIf(!$this->_hasNetwork(), 'No network connection.');
	}

	public function testAllMethodsNoConnection() {
		$stream = new Stream(array('scheme' => null));
		$this->assertFalse($stream->open());
		$this->assertTrue($stream->close());
		$this->assertFalse($stream->timeout(2));
		$this->assertFalse($stream->encoding('UTF-8'));
		$this->assertFalse($stream->write(null));
		$this->assertFalse($stream->read());
		$this->assertTrue($stream->eof());
		$this->assertNull($stream->send(new Request()));
	}

	public function testOpen() {
		$stream = new Stream($this->_testConfig);
		$result = $stream->open();
		$this->assertNotEmpty($result);

		$result = $stream->resource();
		$this->assertInternalType('resource', $result);
	}

	public function testClose() {
		$stream = new Stream($this->_testConfig);
		$result = $stream->open();
		$this->assertNotEmpty($result);

		$result = $stream->close();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertNotInternalType('resource', $result);
	}

	public function testTimeout() {
		$stream = new Stream($this->_testConfig);
		$result = $stream->open();
		$stream->timeout(10);
		$result = $stream->resource();
		$this->assertInternalType('resource', $result);
	}

	public function testEncoding() {
		$stream = new Stream($this->_testConfig);
		$result = $stream->open();
		$stream->encoding('UTF-8');
		$result = $stream->resource();
		$this->assertInternalType('resource', $result);

		$stream = new Stream($this->_testConfig + array('encoding' => 'UTF-8'));
		$result = $stream->open();
		$result = $stream->resource();
		$this->assertInternalType('resource', $result);
	}

	public function testWriteAndRead() {
		$stream = new Stream($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$this->assertInternalType('resource', $stream->resource());

		$result = $stream->write();
		$this->assertEqual(84, $result);
		$this->assertPattern("/^HTTP/", (string) $stream->read());
	}

	public function testSendWithNull() {
		$stream = new Stream($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send(
			new Request($this->_testConfig),
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}

	public function testSendWithArray() {
		$stream = new Stream($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send($this->_testConfig,
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}

	public function testSendWithObject() {
		$stream = new Stream($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send(
			new Request($this->_testConfig),
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}

	public function testStreamAdapter() {
		$socket = new Stream($this->_testConfig);
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