<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use lithium\net\http\Response;

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

	public function testParsingContentTypeWithEncoding() {
		$response = new Response(array('headers' => array(
			'Content-Type' => 'text/xml;charset=UTF-8'
		)));
		$this->assertEqual('text/xml', $response->type);
		$this->assertEqual('UTF-8', $response->encoding);

		$response = new Response(array('headers' => array(
			'Content-Type' => 'text/xml;charset=UTF-8'
		)));
		$this->assertEqual('text/xml', $response->type);
		$this->assertEqual('UTF-8', $response->encoding);
	}

	public function testConstructionWithBody() {
		$response = new Response(array('message' => "Content-type: image/jpeg\r\n\r\nimage data"));
		$this->assertEqual("image data", $response->body());

		$response = new Response(array('body' => "image data"));
		$this->assertEqual("image data", $response->body());
	}

	public function testParseMessage() {
		$message = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=iso-8859-1',
			'',
			'Test!'
		));

		$response = new Response(compact('message'));
		$this->assertEqual($message, (string) $response);
		$this->assertEqual('ISO-8859-1', $response->encoding);

		$body = 'Not a Message';
		$expected = join("\r\n", array('HTTP/1.1 200 OK', '', '', 'Not a Message'));
		$response = new Response(compact('body'));
		$this->assertEqual($expected, (string) $response);
	}

	public function testEmptyResponse() {
		$response = new Response(array('message' => "\n"));
		$result = trim((string) $response);
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
			'encoding' => 'UTF-8',
			'body' => 'Test!'
		);
		$response = new Response($config);
		$this->assertEqual($expected, (string) $response);
	}

	function testTransferEncodingChunkedDecode()  {
		$headers = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Server: CouchDB/0.10.0 (Erlang OTP/R13B)',
			'Etag: "DWGTHR79JLSOGACPLVIZBJUBP"',
			'Date: Wed, 11 Nov 2009 19:49:41 GMT',
			'Content-Type: text/plain;charset=utf-8',
			'Cache-Control: must-revalidate',
			'Transfer-Encoding: chunked',
			'Connection: Keep-alive',
			'',
			'',
		));

		$message = $headers . join("\r\n", array(
			'b7',
			'{"total_rows":1,"offset":0,"rows":[',
			'{"id":"88989cafcd81b09f81078eb523832e8e","key":"gwoo","value":' .
			 '{"author":"gwoo","language":"php","preview":"test",' .
			 '"created":"2009-10-27 12:14:12"}}',
			'4',
			'',
			']}',
			'1',
			'',
			'',
			'0',
			'',
			'',
		));
		$response = new Response(compact('message'));

		$expected = join("\r\n", array(
			'{"total_rows":1,"offset":0,"rows":[',
			'{"id":"88989cafcd81b09f81078eb523832e8e","key":"gwoo","value":' .
			'{"author":"gwoo","language":"php","preview":"test",' .
			'"created":"2009-10-27 12:14:12"}}',
			']}',
		));
		$this->assertEqual($expected, $response->body());

		$message = $headers . join("\r\n", array('body'));

		$response = new Response(compact('message'));
		$result = $response->body();
		$this->assertEqual('body', $result);

		$message = $headers . join("\r\n", array('[part one];', '[part two]'));
		$expected = '[part two]';
		$response = new Response(compact('message'));

		$result = $response->body();
		$this->assertEqual($expected, $result);

		$message = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'Transfer-Encoding: text',
			'',
			'Test!'
		));
		$expected = 'Test!';
		$response = new Response(compact('message'));
		$result = $response->body();
		$this->assertEqual($expected, $result);
	}

	public function testTypeHeader() {
		$response = new Response(array('type' => 'application/json'));
		$result = (string) $response;
		$this->assertPattern('/^HTTP\/1\.1 200 OK/', $result);
		$this->assertPattern('/Content-Type: application\/json\s+$/ms', $result);
	}
}

?>