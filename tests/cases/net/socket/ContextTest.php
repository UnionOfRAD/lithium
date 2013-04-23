<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\socket;

use lithium\net\http\Request;
use lithium\net\socket\Context;
use lithium\test\Mocker;

class ContextTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'google.com',
		'port' => 80,
		'timeout' => 4,
		'classes' => array(
			'request' => 'lithium\net\http\Request',
			'response' => 'lithium\net\http\Response'
		)
	);

	public function setUp() {
		$base = 'lithium\net\socket';
		$namespace = __NAMESPACE__;
		Mocker::overwriteFunction("{$namespace}\\stream_context_get_options", function($resource) {
			rewind($resource);
			return unserialize(stream_get_contents($resource));
		});
		Mocker::overwriteFunction("{$base}\\stream_context_create", function($options) {
			return $options;
		});
		Mocker::overwriteFunction("{$base}\\fopen", function($file, $mode, $includePath, $context) {
			$handle = fopen("php://memory", "rw");
			fputs($handle, serialize($context));
			return $handle;
		});
		Mocker::overwriteFunction("{$base}\\stream_get_meta_data", function($resource) {
			return array(
				'wrapper_data' => array(
					'HTTP/1.1 301 Moved Permanently',
					'Location: http://www.google.com/',
					'Content-Type: text/html; charset=UTF-8',
					'Date: Thu, 28 Feb 2013 07:05:10 GMT',
					'Expires: Sat, 30 Mar 2013 07:05:10 GMT',
					'Cache-Control: public, max-age=2592000',
					'Server: gws',
					'Content-Length: 219',
					'X-XSS-Protection: 1; mode=block',
					'X-Frame-Options: SAMEORIGIN',
					'Connection: close',
				),
			);
		});
		Mocker::overwriteFunction("{$base}\\stream_get_contents", function($resource) {
			return <<<EOD
<HTML><HEAD><meta http-equiv="content-type" content="text/html;charset=utf-8">
<TITLE>301 Moved</TITLE></HEAD><BODY>
<H1>301 Moved</H1>
The document has moved
<A HREF="http://www.google.com/">here</A>.
</BODY></HTML>
EOD;
		});
		Mocker::overwriteFunction("{$base}\\feof", function($resource) {
			return true;
		});
	}

	public function tearDown() {
		Mocker::overwriteFunction(false);
	}

	public function testConstruct() {
		$subject = new Context(array('timeout' => 300) + $this->_testConfig);
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
		$socket = new Context(array('message' => new Request($this->_testConfig)));
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
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
		$this->assertTrue($stream->eof());
	}

	public function testSendWithArray() {
		$stream = new Context($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send($this->_testConfig,
			array('response' => 'lithium\net\http\Response')
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
			array('response' => 'lithium\net\http\Response')
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

		$expected = 'google.com';
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>301 Moved<\/title>/im", (string) $result);
	}
}

?>