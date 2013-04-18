<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\socket;

use lithium\net\http\Request;
use lithium\net\socket\Stream;
use lithium\test\Mocker;

class StreamTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'google.com',
		'port' => 80,
		'timeout' => 2,
		'classes' => array(
			'request' => 'lithium\net\http\Request',
			'response' => 'lithium\net\http\Response'
		)
	);

	public function setUp() {
		$base = 'lithium\net\socket';
		Mocker::overwriteFunction("{$base}\\stream_socket_client", function() {
			return fopen("php://memory", "rw");
		});
		Mocker::overwriteFunction("{$base}\\feof", function($resource) {
			return true;
		});
		Mocker::overwriteFunction("{$base}\stream_get_contents", function($resource) {
			return <<<EOD
HTTP/1.1 301 Moved Permanently
Location: http://www.google.com/
Content-Type: text/html; charset=UTF-8
Date: Thu, 28 Feb 2013 07:05:10 GMT
Expires: Sat, 30 Mar 2013 07:05:10 GMT
Cache-Control: public, max-age=2592000
Server: gws
Content-Length: 219
X-XSS-Protection: 1; mode=block
X-Frame-Options: SAMEORIGIN
Connection: close

<HTML><HEAD><meta http-equiv="content-type" content="text/html;charset=utf-8">
<TITLE>301 Moved</TITLE></HEAD><BODY>
<H1>301 Moved</H1>
The document has moved
<A HREF="http://www.google.com/">here</A>.
</BODY></HTML>
EOD;
		});
	}

	public function tearDown() {
		Mocker::overwriteFunction(false);
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
		$this->assertEqual(83, $result);
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

		$expected = 'google.com';
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>301 Moved<\/title>/im", (string) $result);
	}
}

?>