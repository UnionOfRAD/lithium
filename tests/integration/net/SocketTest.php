<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\net;

use \ErrorException;
use lithium\net\socket\Context;
use lithium\net\socket\Curl;
use lithium\net\socket\Stream;

class SocketTest extends \lithium\test\Integration {

	protected $_testConfig = array(
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'lithify.me',
		'port' => 80,
		'timeout' => 4,
		'classes' => array(
			'request' => 'lithium\net\http\Request',
			'response' => 'lithium\net\http\Response'
		)
	);

	/**
	 * Skip the integration `SocketTest` if no internet connection is found or the
	 * host is unreadable.
	 *
	 * In some cases (maybe if the tests are run behind a proxy) `dns_check_record()` returns
	 * a positive result but the file is not readable. Therefore, an `error_handler` has to be
	 * registered in order to check for readability and (in case of an error) no warning is
	 * printed out on the test suite.
	 */
	public function skip() {
		$message = "No internet connection established.";
		$this->skipIf(!$this->_hasNetwork($this->_testConfig), $message);
	}

	/**
	 * Tests the socket `Context` adapter.
	 *
	 * @see lithium\net\socket\Context
	 */
	public function testContextAdapter() {
		$socket = new Context($this->_testConfig);
		$this->assertTrue($socket->open());
		$response = $socket->send();
		$this->assertTrue($response instanceof \lithium\net\http\Response);

		$expected = $this->_testConfig['host'];
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>.*Lithium.*<\/title>/im", (string) $result);
	}

	public function testCurlAdapter() {
		$message = 'Your PHP installation was not compiled with curl support.';
		$this->skipIf(!function_exists('curl_init'), $message);

		$socket = new Curl($this->_testConfig);
		$this->assertTrue($socket->open());
		$response = $socket->send();
		$this->assertTrue($response instanceof \lithium\net\http\Response);

		$expected = $this->_testConfig['host'];
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>.*Lithium.*<\/title>/im", (string) $result);
	}

	public function testStreamAdapter() {
		$socket = new Stream($this->_testConfig);
		$this->assertTrue($socket->open());
		$response = $socket->send();
		$this->assertTrue($response instanceof \lithium\net\http\Response);

		$expected = $this->_testConfig['host'];
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>.*Lithium.*<\/title>/im", (string) $result);
	}
}

?>