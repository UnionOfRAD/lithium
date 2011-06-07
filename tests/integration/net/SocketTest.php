<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\net;

use lithium\net\socket\Context;
use lithium\net\socket\Curl;
use lithium\net\socket\Stream;

class SocketTest extends \lithium\test\Integration {

	protected $_testConfig = array(
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'www.google.com',
		'port' => 80,
		'timeout' => 1,
		'classes' => array(
			'request' => 'lithium\net\http\Request',
			'response' => 'lithium\net\http\Response'
		)
	);

	public function skip() {
		$config = $this->_testConfig;
		$message = "Could not open {$config['host']} - skipping " . __CLASS__;
		$this->skipIf($config['host'] == gethostbyname($config['host']), $message);
	}

	public function testContextAdapter() {
		$socket = new Context($this->_testConfig);
		$this->assertTrue($socket->open());
		$response = $socket->send();
		$this->assertTrue($response instanceof \lithium\net\http\Response);

		$expected = 'www.google.com';
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>Google<\/title>/im", (string) $result);
	}

	public function testCurlAdapter() {
		$socket = new Curl($this->_testConfig);
		$this->assertTrue($socket->open());
		$response = $socket->send();
		$this->assertTrue($response instanceof \lithium\net\http\Response);

		$expected = 'www.google.com';
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>Google<\/title>/im", (string) $result);
	}

	public function testStreamAdapter() {
		$socket = new Stream($this->_testConfig);
		$this->assertTrue($socket->open());
		$response = $socket->send();
		$this->assertTrue($response instanceof \lithium\net\http\Response);

		$expected = 'www.google.com';
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>Google<\/title>/im", (string) $result);
	}
}

?>