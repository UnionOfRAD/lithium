<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\socket;

use \lithium\tests\mocks\net\socket\MockCurl;

class CurlTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2
	);

	/**
	 * Skip the test if curl is not available in your PHP installation.
	 *
	 * @return void
	 */
	public function skip() {
		$extensionExists = function_exists('curl_init');
		$message = 'Your PHP installation was not compiled with curl support.';
		$this->skipIf(!$extensionExists, $message);
	}

	public function testAllMethodsNoConnection() {
		$stream = new MockCurl(array('protocol' => null));
		$this->assertFalse($stream->open());
		$this->assertTrue($stream->close());
		$this->assertFalse($stream->timeout(2));
		$this->assertFalse($stream->encoding('UTF-8'));
		$this->assertFalse($stream->write(null));
		$this->assertFalse($stream->read());
	}

	public function testOpen() {
		$stream = new MockCurl($this->_testConfig);
		$result = $stream->open();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testClose() {
		$stream = new MockCurl($this->_testConfig);
		$result = $stream->open();
		$this->assertTrue($result);

		$result = $stream->close();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertFalse(is_resource($result));
	}

	public function testTimeout() {
		$stream = new MockCurl($this->_testConfig);
		$result = $stream->open();
		$stream->timeout(10);
		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testEncoding() {
		$stream = new MockCurl($this->_testConfig);
		$result = $stream->open();
		$stream->encoding('UTF-8');
		$result = $stream->resource();
		$this->assertTrue(is_resource($result));

		$stream = new MockCurl($this->_testConfig + array('encoding' => 'UTF-8'));
		$result = $stream->open();
		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testWriteAndRead() {
		$stream = new MockCurl($this->_testConfig);
		$result = $stream->open();
		$this->assertTrue(is_resource($result));

		$result = $stream->resource();
		$this->assertTrue(is_resource($result));

		$url = 'http://localhost';

		$stream->set(CURLOPT_URL, $url);
		$this->assertTrue($stream->write(null));

		$result = $stream->read();
		$this->assertEqual(file_get_contents($url), $result);
	}
}

?>
