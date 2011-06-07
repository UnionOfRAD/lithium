<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
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

		$result = $response->status('foobar');
		$this->assertFalse($result);
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

	public function testMessageContentTypeParsing() {
		// Content type WITHOUT space between type and charset
		$message = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Content-Type: application/json;charset=iso-8859-1',
			'',
			'Test!'
		));
		$response = new Response(array('message' => $message));
		$this->assertEqual('application/json', $response->type);
		$this->assertEqual('ISO-8859-1', $response->encoding);

		// Content type WITH ONE space between type and charset
		$message = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Content-Type: application/json; charset=iso-8859-1',
			'',
			'Test!'
		));
		$response = new Response(array('message' => $message));
		$this->assertEqual('application/json', $response->type);
		$this->assertEqual('ISO-8859-1', $response->encoding);

		$message = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Content-Type: application/json;     charset=iso-8859-1',
			'',
			'Test!'
		));
		$response = new Response(array('message' => $message));
		$this->assertEqual('application/json', $response->type);
		$this->assertEqual('ISO-8859-1', $response->encoding);
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
			''
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
			'',
		));
		$response = new Response(compact('message'));

		$expected = join("\r\n", array(
			'{"total_rows":1,"offset":0,"rows":[',
			'{"id":"88989cafcd81b09f81078eb523832e8e","key":"gwoo","value":' .
			'{"author":"gwoo","language":"php","preview":"test",' .
			'"created":"2009-10-27 12:14:12"}}',
			']}'
		));
		$this->assertEqual($expected, $response->body());

		$message = $headers . "\r\nbody";

		$response = new Response(compact('message'));
		$result = $response->body();
		$this->assertEqual('body', $result);

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

	/**
	 * Creates a chunked gzipped message to test response decoding.
	 *
	 * @param string $body Message body.
	 * @param array $headers Message headers.
	 * @return string Returns a raw HTTP message with headers and body.
	 */
	protected function _createMessage($body, array $headers = array()) {
		$headers += array(
			'Connection: close',
			'Content-Encoding: gzip',
			'Content-Type: text/html; charset=ISO-8859-15',
			'Server: Apache/2.2.16 (Debian) mod_ssl/2.2.16 OpenSSL/0.9.8o',
			'Transfer-Encoding: chunked',
			'Vary: Accept-Encoding',
		);
		return join("\r\n", $headers) . "\r\n\r\n" . $body;
	}

	public function testWithoutChunksAndComment() {
		$body = "\n<html>\n    <head>\n        <title>Simple site</title>\n    </head>\n";
		$body .= "<body>\n        <h1>Simple site</h1>\n        <p>\n            But awesome\n";
		$body .= "        </p>\n    </body>\n</html>\n";
		$message =  $this->_createMessage($body);
		$response = new Response(compact('message'));
		$this->assertEqual(trim($body), $response->body());
	}

	public function testWithoutChunksAndCommentInBody() {
		$body = "\n<html>\n    <head>\n        <title>Simple site</title>\n    </head>";
		$body .= "\n    <body>\n        <!-- (c) 1998 - 2011 Tweakers.net B.V. --> ";
		$body .= "\n        <h1>Simple site</h1>\n        <p>\n            But awesome";
		$body .= "\n        </p>\n    </body>\n</html>\n";
		$message =  $this->_createMessage($body);
		$response = new Response(compact('message'));
		$this->assertEqual(trim($body), $response->body());
	}

	public function testWithoutChunksAndRandomCommentInHtmlRoot() {
		$body = "\n<html><!-- This is some random comment -->\n    <head>";
		$body .= "\n        <title>Simple site</title>\n    </head>\n    <body>";
		$body .= "\n        <h1>Simple site</h1>\n        <p>\n            But awesome";
		$body .= "\n        </p>\n    </body>\n</html>\n";
		$message = $this->_createMessage($body);
		$response = new Response(compact('message'));
		$this->assertEqual(trim($body), $response->body());
	}

	public function testWithoutChunksAndCommentInHtmlRoot() {
		$body = "\n<!doctype html><!-- (c) 1998 - 2011 Tweakers.net B.V. --> \n<html lang=\"nl\"> ";
		$body .= "\n    <head>\n        <title>Simple site</title>\n    </head>";
		$body .= "\n    <body>\n        <h1>Simple site</h1>\n        <p>\n            But awesome";
		$body .= "\n        </p>\n    </body>\n</html>\n";
		$message =  $this->_createMessage($body);
		$response = new Response(compact('message'));
		$this->assertEqual(trim($body), $response->body());
	}

	public function testMessageWithNoHeaders() {
		$body = "\n<html>...</html>\n";
		$message = "\r\n\r\n{$body}";
		$response = new Response(compact('message'));
		$this->assertFalse($response->headers());
		$this->assertEqual(trim($body), $response->body());
	}
}

?>