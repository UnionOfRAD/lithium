<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Integration\Net;

use Lithium\Net\Socket\Context;
use Lithium\Net\Socket\Curl;
use Lithium\Net\Socket\Stream;

class SocketTest extends \Lithium\Test\Integration {

	protected $_testConfig = array(
		'persistent' => false,
		'scheme' => 'http',
		'host' => 'www.lithify.me',
		'port' => 80,
		'timeout' => 1,
		'classes' => array(
			'request' => 'Lithium\Net\Http\Request',
			'response' => 'Lithium\Net\Http\Response'
		)
	);

	public function skip() {
		$message = "No internet connection established.";
		$this->skipIf(!$this->_hasNetwork($this->_testConfig), $message);
	}

	public function testContextAdapter() {
		$socket = new Context($this->_testConfig);
		$this->assertTrue($socket->open());
		$response = $socket->send();
		$this->assertTrue($response instanceof \Lithium\Net\Http\Response);

		$expected = 'www.lithify.me';
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
		$this->assertTrue($response instanceof \Lithium\Net\Http\Response);

		$expected = 'www.lithify.me';
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>.*Lithium.*<\/title>/im", (string) $result);
	}

	public function testStreamAdapter() {
		$socket = new Stream($this->_testConfig);
		$this->assertTrue($socket->open());
		$response = $socket->send();
		$this->assertTrue($response instanceof \Lithium\Net\Http\Response);

		$expected = 'www.lithify.me';
		$result = $response->host;
		$this->assertEqual($expected, $result);

		$result = $response->body();
		$this->assertPattern("/<title[^>]*>.*Lithium.*<\/title>/im", (string) $result);
	}
}

?>