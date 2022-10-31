<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\net\socket;

use lithium\net\http\Request;
use lithium\net\socket\Curl;

class CurlTest extends \lithium\test\Integration {

	protected $_testConfig = [
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'example.org',
		'port' => 80,
		'timeout' => 2,
		'classes' => [
			'request' => 'lithium\net\http\Request',
			'response' => 'lithium\net\http\Response'
		]
	];

	public function skip() {
		$this->skipIf(!$this->_hasNetwork(), 'No network connection.');

		$message = 'Your PHP installation was not compiled with curl support.';
		$this->skipIf(!function_exists('curl_init'), $message);
	}

	public function testAllMethodsNoConnection() {
		$stream = new Curl(['scheme' => null]);
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
		$this->assertInternalType('object', $result);
	}

	public function testClose() {
		$stream = new Curl($this->_testConfig);
		$result = $stream->open();
		$this->assertNotEmpty($result);

		$result = $stream->close();
		if (!$result) {
			sleep(2);

			$message = 'Cannot reliably test connection closing. ';
			$this->skipIf(!$result = $stream->close(), $message);
		}
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertNotInternalType('object', $result);
	}

	public function testTimeout() {
		$stream = new Curl($this->_testConfig);
		$result = $stream->open();
		$stream->timeout(10);
		$result = $stream->resource();
		$this->assertInternalType('object', $result);
	}

	public function testEncoding() {
		$stream = new Curl($this->_testConfig);
		$result = $stream->open();
		$stream->encoding('UTF-8');
		$result = $stream->resource();
		$this->assertInternalType('object', $result);

		$stream = new Curl($this->_testConfig + ['encoding' => 'UTF-8']);
		$result = $stream->open();
		$result = $stream->resource();
		$this->assertInternalType('object', $result);
	}

	public function testWriteAndRead() {
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('object', $stream->open());
		$this->assertInternalType('object', $stream->resource());
		$this->assertEqual(1, $stream->write());
		$this->assertPattern("/^HTTP/", (string) $stream->read());
	}

	public function testSendWithNull() {
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('object', $stream->open());
		$result = $stream->send(
			new Request($this->_testConfig),
			['response' => 'lithium\net\http\Response']
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
	}

	public function testSendWithArray() {
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('object', $stream->open());
		$result = $stream->send($this->_testConfig,
			['response' => 'lithium\net\http\Response']
		);
		$this->assertInstanceOf('lithium\net\http\Response', $result);
		$this->assertPattern("/^HTTP/", (string) $result);
	}

	public function testSendWithObject() {
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('object', $stream->open());
		$result = $stream->send(
			new Request($this->_testConfig),
			['response' => 'lithium\net\http\Response']
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
		$config = $this->_testConfig + ['options' => ['DummyFlag' => 'Dummy Value']];
		$stream = new Curl($config);
		$stream->open();
		$this->assertEqual('Dummy Value', $stream->options['DummyFlag']);
	}

	public function testSettingOfOptionsInOpen() {
		$stream = new Curl($this->_testConfig);
		$stream->open(['options' => ['DummyFlag' => 'Dummy Value']]);
		$this->assertEqual('Dummy Value', $stream->options['DummyFlag']);
	}

	public function testSendPostThenGet() {
		$postConfig = ['method' => 'POST', 'body' => '{"body"}'];
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('object', $stream->open());
		$this->assertTrue($stream->write(new Request($postConfig + $this->_testConfig)));
		$this->assertTrue(isset($stream->options[CURLOPT_POST]));
		$this->assertTrue($stream->close());

		$this->assertInternalType('object', $stream->open());
		$this->assertTrue($stream->write(new Request($this->_testConfig)));
		$this->assertFalse(isset($stream->options[CURLOPT_POST]));
		$this->assertTrue($stream->close());
	}

	public function testSendPutThenGet() {
		$postConfig = ['method' => 'PUT', 'body' => '{"body"}'];
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('object', $stream->open());
		$this->assertTrue($stream->write(new Request($postConfig + $this->_testConfig)));
		$this->assertTrue(isset($stream->options[CURLOPT_CUSTOMREQUEST]));
		$this->assertEqual($stream->options[CURLOPT_CUSTOMREQUEST],'PUT');
		$this->assertTrue(isset($stream->options[CURLOPT_POSTFIELDS]));
		$this->assertEqual($stream->options[CURLOPT_POSTFIELDS],$postConfig['body']);
		$this->assertTrue($stream->close());

		$this->assertInternalType('object', $stream->open());
		$this->assertTrue($stream->write(new Request($this->_testConfig)));
		$this->assertFalse(isset($stream->options[CURLOPT_CUSTOMREQUEST]));
		$this->assertTrue($stream->close());
	}

	public function testSendPatchThenGet() {
		$postConfig = ['method' => 'PATCH', 'body' => '{"body"}'];
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('object', $stream->open());
		$this->assertTrue($stream->write(new Request($postConfig + $this->_testConfig)));
		$this->assertTrue(isset($stream->options[CURLOPT_CUSTOMREQUEST]));
		$this->assertEqual($stream->options[CURLOPT_CUSTOMREQUEST],'PATCH');
		$this->assertTrue(isset($stream->options[CURLOPT_POSTFIELDS]));
		$this->assertEqual($stream->options[CURLOPT_POSTFIELDS],$postConfig['body']);
		$this->assertTrue($stream->close());

		$this->assertInternalType('object', $stream->open());
		$this->assertTrue($stream->write(new Request($this->_testConfig)));
		$this->assertFalse(isset($stream->options[CURLOPT_CUSTOMREQUEST]));
		$this->assertTrue($stream->close());
	}

	public function testSendDeleteThenGet() {
		$postConfig = ['method' => 'DELETE', 'body' => ''];
		$stream = new Curl($this->_testConfig);
		$this->assertInternalType('object', $stream->open());
		$this->assertTrue($stream->write(new Request($postConfig + $this->_testConfig)));
		$this->assertTrue(isset($stream->options[CURLOPT_CUSTOMREQUEST]));
		$this->assertEqual($stream->options[CURLOPT_CUSTOMREQUEST],'DELETE');
		$this->assertTrue(isset($stream->options[CURLOPT_POSTFIELDS]));
		$this->assertEqual($stream->options[CURLOPT_POSTFIELDS],$postConfig['body']);
		$this->assertTrue($stream->close());

		$this->assertInternalType('object', $stream->open());
		$this->assertTrue($stream->write(new Request($this->_testConfig)));
		$this->assertFalse(isset($stream->options[CURLOPT_CUSTOMREQUEST]));
		$this->assertTrue($stream->close());
	}

	public function testCurlAdapter() {
		$socket = new Curl($this->_testConfig);
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