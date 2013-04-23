<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\socket;

use lithium\net\http\Request;
use lithium\net\socket\Curl;
use lithium\test\Mocker;

class CurlTest extends \lithium\test\Unit {

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

	public function skip() {
		$message = 'Your PHP installation was not compiled with curl support.';
		$this->skipIf(!function_exists('curl_init'), $message);
	}

	public function setUp() {
		$base = 'lithium\net\socket';
		Mocker::overwriteFunction("{$base}\curl_init", function($url) {
			return fopen("php://memory", "rw");
		});
		Mocker::overwriteFunction("{$base}\curl_setopt_array", function($resource, $options) {
			return count($options);
		});
		Mocker::overwriteFunction("{$base}\curl_setopt", function($resource, $key, $value) {
			return;
		});
		Mocker::overwriteFunction("{$base}\curl_close", function(&$resource) {
			$resource = null;
			return;
		});
		Mocker::overwriteFunction("{$base}\curl_exec", function($resource) {
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
		$stream = new Curl(array('scheme' => null));
		$this->assertFalse($stream->open());
		$this->assertTrue($stream->close());
		$this->assertFalse($stream->timeout(2));
		$this->assertEmpty($stream->encoding('UTF-8'));
		$this->assertFalse($stream->write(null));
		$this->assertFalse($stream->read());
	}

	public function testOpen() {
		$stream = new Curl($this->_testConfig);
		$result = $stream->open();
		$this->assertNotEmpty($result);

		$result = $stream->resource();
		$this->assertInternalType('resource', $result);
	}

	public function testClose() {
		$stream = new Curl($this->_testConfig);
		$result = $stream->open();
		$this->assertNotEmpty($result);

		$result = $stream->close();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertNotInternalType('resource', $result);
	}

	public function testTimeout() {
		$stream = new Curl($this->_testConfig);
		$result = $stream->open();
		$stream->timeout(10);
		$result = $stream->resource();
		$this->assertInternalType('resource', $result);
	}

	public function testEncoding() {
		$stream = new Curl($this->_testConfig);
		$result = $stream->open();
		$stream->encoding('UTF-8');
		$result = $stream->resource();
		$this->assertInternalType('resource', $result);

		$stream = new Curl($this->_testConfig + array('encoding' => 'UTF-8'));
		$result = $stream->open();
		$result = $stream->resource();
		$this->assertInternalType('resource', $result);
	}

	public function testWriteAndRead() {
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$this->assertInternalType('resource', $stream->resource());
		$this->assertEqual(1, $stream->write());
		$this->assertPattern("/^HTTP/", (string) $stream->read());
	}

	public function testSendWithNull() {
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send(
			new Request($this->_testConfig),
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
	}

	public function testSendWithArray() {
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send($this->_testConfig,
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
	}

	public function testSendWithObject() {
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$result = $stream->send(
			new Request($this->_testConfig),
			array('response' => 'lithium\net\http\Response')
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
	}

	public function testSettingOfOptions() {
		$stream = new Curl($this->_testConfig);
		$stream->set('DummyFlag', 'Dummy Value');
		$stream->set('DummyFlag', 'Changed Dummy Value');
		$this->assertEqual('Changed Dummy Value', $stream->options['DummyFlag']);
	}

	public function testSettingOfOptionsInConfig() {
		$config = $this->_testConfig + array('options' => array('DummyFlag' => 'Dummy Value'));
		$stream = new Curl($config);
		$stream->open();
		$this->assertEqual('Dummy Value', $stream->options['DummyFlag']);
	}

	public function testSettingOfOptionsInOpen() {
		$stream = new Curl($this->_testConfig);
		$stream->open(array('options' => array('DummyFlag' => 'Dummy Value')));
		$this->assertEqual('Dummy Value', $stream->options['DummyFlag']);
	}

	public function testSendPostThenGet() {
		$postConfig = array('method' => 'POST', 'body' => '{"body"}');
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$this->assertTrue($stream->write(new Request($postConfig + $this->_testConfig)));
		$this->assertTrue(isset($stream->options[CURLOPT_POST]));
		$this->assertTrue($stream->close());

		$this->assertInternalType('resource', $stream->open());
		$this->assertTrue($stream->write(new Request($this->_testConfig)));
		$this->assertFalse(isset($stream->options[CURLOPT_POST]));
		$this->assertTrue($stream->close());
	}

	public function testSendPutThenGet() {
		$postConfig = array('method' => 'PUT', 'body' => '{"body"}');
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('resource', $stream->open());
		$this->assertTrue($stream->write(new Request($postConfig + $this->_testConfig)));
		$this->assertTrue(isset($stream->options[CURLOPT_CUSTOMREQUEST]));
		$this->assertEqual($stream->options[CURLOPT_CUSTOMREQUEST],'PUT');
		$this->assertTrue(isset($stream->options[CURLOPT_POSTFIELDS]));
		$this->assertEqual($stream->options[CURLOPT_POSTFIELDS],$postConfig['body']);
		$this->assertTrue($stream->close());

		$this->assertInternalType('resource', $stream->open());
		$this->assertTrue($stream->write(new Request($this->_testConfig)));
		$this->assertFalse(isset($stream->options[CURLOPT_CUSTOMREQUEST]));
		$this->assertTrue($stream->close());
	}

	public function testCurlAdapter() {
		$socket = new Curl($this->_testConfig);
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