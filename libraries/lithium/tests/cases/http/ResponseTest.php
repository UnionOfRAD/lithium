<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\http;

use \lithium\http\Response;

class ResponseTest extends \lithium\test\Unit {

	public function testStatus() {
		$response = new Response();

		$expected = 'HTTP/1.1 500 Internal Server Error';
		$result = $response->status(500);
		$this->assertEqual($expected, $result);

		$expected = 'HTTP/1.1 500 Internal Server Error';
		$result = $response->status('500');
		$this->assertEqual($expected, $result);

		$expected = 'HTTP/1.1 500 Internal Server Error';
		$result = $response->status('Internal Server Error');
		$this->assertEqual($expected, $result);

		$expected = 500;
		$result = $response->status('code', 'Internal Server Error');
		$this->assertEqual($expected, $result);

		$expected = 'Internal Server Error';
		$result = $response->status('message', 500);
		$this->assertEqual($expected, $result);

		$expected = 'HTTP/1.1 500 Internal Server Error';
		$result = $response->status();
		$this->assertEqual($expected, $result);

		$expected = 'HTTP/1.1 303 See Other';
		$result = $response->status('See Other');
		$this->assertEqual($expected, $result);

		$expected = false;
		$result = $response->status('foobar');
		$this->assertEqual($expected, $result);
	}

	public function testParseMessage() {
		$message = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'',
			'Test!'
		));

		$response = new Response(compact('message'));
		$this->assertEqual($message, (string)$response);
	}

	public function testEmptyResponse() {
		$response = new Response(array('message' => "\n"));
		$result = trim((string)$response);
		$expected = 'HTTP/1.1 200 OK';
		$this->assertEqual($expected, $result);
	}

	function testToString() {
		$expected = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'',
			'Test!'
		));
		$config = array(
			'protocol' => 'HTTP/1.1',
			'version' => '1.1',
			'status' => array('code' => '200', 'message' => 'OK'),
			'headers' => array(
				'Header' => 'Value',
				'Connection' => 'close',
				'Content-Type' => 'text/html;charset=UTF-8'
			),
			'type' => 'text/html',
			'charset' => 'UTF-8',
			'body' => 'Test!'
		);
		$response = new Response($config);
		$this->assertEqual($expected, (string)$response);
	}
}

?>