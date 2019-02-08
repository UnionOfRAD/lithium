<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
		$response = new Response(['headers' => [
			'Content-Type' => 'text/xml;charset=UTF-8'
		]]);
		$this->assertEqual('xml', $response->type());
		$this->assertEqual('UTF-8', $response->encoding);

		$response = new Response(['headers' => [
			'Content-Type' => 'application/soap+xml; charset=iso-8859-1'
		]]);
		$this->assertEqual('xml', $response->type());
		$this->assertEqual('ISO-8859-1', $response->encoding);

		// Content type WITHOUT space between type and charset
		$response = new Response(['headers' => [
			'Content-Type' => 'application/json;charset=iso-8859-1'
		]]);
		$this->assertEqual('json', $response->type());
		$this->assertEqual('ISO-8859-1', $response->encoding);

		// Content type WITH ONE space between type and charset
		$response = new Response(['headers' => [
			'Content-Type' => 'application/json; charset=iso-8859-1'
		]]);
		$this->assertEqual('json', $response->type());
		$this->assertEqual('ISO-8859-1', $response->encoding);

		$response = new Response(['headers' => [
			'Content-Type' => 'application/json;     charset=iso-8859-1'
		]]);
		$this->assertEqual('json', $response->type());
		$this->assertEqual('ISO-8859-1', $response->encoding);
	}

	public function testParsingContentTypeWithoutEncoding() {
		$response = new Response(['headers' => [
			'Content-Type' => 'application/json'
		]]);
		$this->assertEqual('json', $response->type());
		$this->assertEqual('UTF-8', $response->encoding); //default
	}

	public function testParsingContentTypeWithVersionNumber() {
		$response = new Response(['headers' => [
			'Content-Type' => 'application/x-amz-json-1.0'
		]]);
		$this->assertEqual('application/x-amz-json-1.0', $response->type());
	}

	public function testConstructionWithBody() {
		$response = new Response(['message' => "Content-type: image/jpeg\r\n\r\nimage data"]);
		$this->assertEqual("image data", $response->body());

		$response = new Response(['body' => "image data"]);
		$this->assertEqual("image data", $response->body());
	}

	public function testParseMessage() {
		$message = join("\r\n", [
			'HTTP/1.1 404 Not Found',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/plain;charset=ISO-8859-1',
			'',
			'Test!'
		]);

		$response = new Response(compact('message'));
		$this->assertEqual($message, (string) $response);
		$this->assertEqual('text', $response->type());
		$this->assertEqual('ISO-8859-1', $response->encoding);
		$this->assertEqual('404', $response->status['code']);
		$this->assertEqual('Not Found', $response->status['message']);
		$this->assertEqual('HTTP/1.1 404 Not Found', $response->status());

		$body = 'Not a Message';
		$expected = join("\r\n", ['HTTP/1.1 200 OK', '', '', 'Not a Message']);
		$response = new Response(compact('body'));
		$this->assertEqual($expected, (string) $response);
	}

	public function testParseMessageWithRepeatingHeaderKeys() {
		$message = join("\r\n", [
			'HTTP/1.1 200 OK',
			'Connection: close',
			'Content-Type: text/plain;charset=UTF8',
			'Header: value1',
			'Header: value2',
			'',
			'Test!'
		]);
		$header = ['value1', 'value2'];

		$response = new Response(compact('message'));
		$this->assertEqual($header, $response->headers('Header'));
		$this->assertEqual($message, (string) $response);
	}

	public function testParseMessageWithCookies() {
		$message = join("\r\n", [
			'HTTP/1.1 200 OK',
			'Connection: close',
			'Content-Type: text/plain;charset=UTF8',
			'Set-Cookie: doctor=who; Path=/tardis; HttpOnly',
			'Set-Cookie: test=foo%20bar; Expires=Tue, 15-Jan-2013 21:47:38 GMT; Secure',
			'Set-Cookie: test=foo%2Bbin; Path=/test; Domain=li3.me',
			'',
			'Test!'
		]);
		$cookies = [
			'doctor' => ['value' => 'who', 'path' => '/tardis', 'httponly' => true],
			'test' => [
				['value' => 'foo bar', 'expires' => 1358286458, 'secure' => true],
				['value' => 'foo+bin', 'path' => '/test', 'domain' => 'li3.me']
			]
		];

		$response = new Response(compact('message'));
		$this->assertEqual($cookies, $response->cookies());
		$this->assertEqual($message, (string) $response);
	}

	public function testParseMessageWithContentTypeHeaderSetsType() {
		$response = new Response([
			'message' => join("\r\n", [
				'HTTP/1.1 200 OK',
				'Content-Type: text/x-test-a',
				'',
				'foo!'
			])
		]);
		$this->assertEqual('text/x-test-a', $response->headers('Content-Type'));
	}

	public function testContentTypeHeaderAndTypePropertyAreSynchronized() {
		$response = new Response([
			'message' => "Content-type: text/x-test-a\r\n\r\nfoo"
		]);
		$this->assertEqual($response->type(), $response->headers('Content-Type'));

		$response = new Response([
			'headers' => ['Content-Type' => 'text/x-test-a']
		]);
		$this->assertEqual($response->type(), $response->headers('Content-Type'));

		$response = new Response([
			'type' => 'text/x-test-a'
		]);
		$this->assertEqual($response->type(), $response->headers('Content-Type'));
	}

	public function testParseMessageHeadersMerging() {
		$response = new Response([
			'message' => "Content-type: text/x-test-a\r\nX-Test-A: foo\r\n\r\nfoo",
			'headers' => [
				'Content-Type' => 'text/x-test-b',
				'X-Test-B' => 'bar'
			]
		]);
		$expected = [
			'Content-Type: text/x-test-b',
			'X-Test-B: bar',
			'X-Test-A: foo'
		];
		$this->assertEqual($expected, $response->headers());
	}

	public function testEmptyResponse() {
		$response = new Response(['message' => "\n"]);
		$result = trim((string) $response);
		$expected = 'HTTP/1.1 200 OK';
		$this->assertEqual($expected, $result);
	}

	public function testToString() {
		$expected = join("\r\n", [
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'',
			'Test!'
		]);
		$config = [
			'protocol' => 'HTTP/1.1',
			'version' => '1.1',
			'status' => ['code' => '200', 'message' => 'OK'],
			'headers' => [
				'Header' => 'Value',
				'Connection' => 'close',
				'Content-Type' => 'text/html;charset=UTF-8'
			],
			'type' => 'text/html',
			'encoding' => 'UTF-8',
			'body' => 'Test!'
		];
		$response = new Response($config);
		$this->assertEqual($expected, (string) $response);
	}

	public function testToStringWithCookies() {
		$expected = join("\r\n", [
			'HTTP/1.1 200 OK',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'Set-Cookie: Name=Marty%20McFly; Domain=.hillvalley.us; Secure',
			'Set-Cookie: Destination=The%20Future; Expires=Wed, 21-Oct-2015 23:29:00 GMT',
			'',
			'Great Scott!'
		]);
		$config = [
			'protocol' => 'HTTP/1.1',
			'version' => '1.1',
			'status' => ['code' => '200', 'message' => 'OK'],
			'headers' => [
				'Connection' => 'close',
				'Content-Type' => 'text/html;charset=UTF-8'
			],
			'cookies' => [
				'Name' => ['value' => 'Marty McFly', 'domain' => '.hillvalley.us', 'secure' => true],
				'Destination' => ['value' => 'The Future', 'expires' => 'Oct 21 2015 4:29 PM PDT']
			],
			'type' => 'text/html',
			'encoding' => 'UTF-8',
			'body' => 'Great Scott!'
		];
		$response = new Response($config);
		$this->assertEqual($expected, (string) $response);
	}

	public function testToStringDoesNotAddContentTypeHeaderOnTextHtml() {
		$response = new Response();

		$expected = "HTTP/1.1 200 OK\r\n\r\n\r\n";
		$result = (string) $response;
		$this->assertEqual($expected, $result);

		/* Decide what to do with this */
		return "Is this test correct?";

		$response = new Response();
		$response->type('text/html');

		$expected = "HTTP/1.1 200 OK\r\n\r\n\r\n";
		$result = (string) $response;
		$this->assertEqual($expected, $result);

		$response = new Response();
		$response->type('text/plain');

		$expected = "HTTP/1.1 200 OK\r\nContent-Type: text/plain;charset=UTF-8\r\n\r\n";
		$result = (string) $response;
		$this->assertEqual($expected, $result);
	}

	public function testToStringTypeAlwaysUsesContentTypeHeader() {
		$response = new Response();
		$response->headers('Content-Type', 'text/html');

		$expected = "HTTP/1.1 200 OK\r\nContent-Type: text/html;charset=UTF-8\r\n\r\n";
		$result = (string) $response;
		$this->assertEqual($expected, $result);

		$response = new Response();
		$response->headers('Content-Type', 'text/plain');

		$expected = "HTTP/1.1 200 OK\r\nContent-Type: text/plain;charset=UTF-8\r\n\r\n";
		$result = (string) $response;
		$this->assertEqual($expected, $result);
	}

	public function testToStringPrefersHeadersContentTypeOverType() {

		/* Decide what to do with this */
		return "Is this test correct?";

		$response = new Response();
		$response->headers('Content-Type', 'text/plain');
		$response->type('text/html');

		$expected = "HTTP/1.1 200 OK\r\nContent-Type: text/plain;charset=UTF-8\r\n\r\n";
		$result = (string) $response;
		$this->assertEqual($expected, $result);
	}

	public function testTransferEncodingChunkedDecode()  {
		$headers = join("\r\n", [
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
		]);

		$message = $headers . join("\r\n", [
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
			''
		]);
		$response = new Response(compact('message'));

		$expected = join("\r\n", [
			'{"total_rows":1,"offset":0,"rows":[',
			'{"id":"88989cafcd81b09f81078eb523832e8e","key":"gwoo","value":' .
			'{"author":"gwoo","language":"php","preview":"test",' .
			'"created":"2009-10-27 12:14:12"}}',
			']}'
		]);
		$this->assertEqual($expected, $response->body());

		$message = $headers . "\r\nbody";

		$response = new Response(compact('message'));
		$result = $response->body();
		$this->assertEqual('body', $result);

		$message = join("\r\n", [
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'Transfer-Encoding: text',
			'',
			'Test!'
		]);
		$expected = 'Test!';
		$response = new Response(compact('message'));
		$result = $response->body();
		$this->assertEqual($expected, $result);

		$expected = '1+1 is 2, 10%40 is 20.';
		$message = $headers . join("\r\n", [
			'22',
			$expected,
			'',
		]);

		$response = new Response(compact('message'));
		$result = $response->body();
		$this->assertEqual($expected, $result);
	}

	public function testTypePriority() {

		/* Decide what to do with this */
		return "Is this test correct?";

		$response = new Response([
			'message' => "Content-type: text/x-test-a\r\n\r\nfoo",
			'type' => 'text/x-test-b',
			'headers' => ['Content-Type' => 'text/x-test-c']
		]);
		$this->assertEqual('text/x-test-c', $response->type());

		$response = new Response([
			'message' => "Content-type: text/x-test-a\r\n\r\nfoo",
			'type' => 'text/x-test-b'
		]);
		$this->assertEqual('text/x-test-b', $response->type());
	}

	public function testTypeHeader() {
		$response = new Response(['type' => 'application/json']);
		$result = (string) $response;
		$this->assertPattern('/^HTTP\/1\.1 200 OK/', $result);
		$this->assertPattern('/Content-Type: application\/json(.*)$/ms', $result);
	}

	/**
	 * Creates a chunked gzipped message to test response decoding.
	 *
	 * @param string $body Message body.
	 * @param array $headers Message headers.
	 * @return string Returns a raw HTTP message with headers and body.
	 */
	protected function _createMessage($body, array $headers = []) {
		$headers += [
			'Connection: close',
			'Content-Encoding: gzip',
			'Content-Type: text/html; charset=ISO-8859-15',
			'Server: Apache/2.2.16 (Debian) mod_ssl/2.2.16 OpenSSL/0.9.8o',
			'Transfer-Encoding: chunked',
			'Vary: Accept-Encoding'
		];
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
		$body .= "\n    <body>\n        <!-- (c) 1998 - 2012 Tweakers.net B.V. --> ";
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
		$body = "\n<!doctype html><!-- (c) 1998 - 2012 Tweakers.net B.V. --> \n<html lang=\"nl\"> ";
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
		$this->assertEmpty($response->headers());
		$this->assertEqual(trim($body), $response->body());
	}

	public function testDigestParsing() {
		$auth = 'Digest realm="app",';
		$auth .= 'qop="auth",nonce="4ee1617b8756e",opaque="dd7bcee161192cb8fba765eb595eba87"';
		$headers = ["WWW-Authenticate" => $auth];
		$response = new Response(compact('headers'));
		$expected = ["WWW-Authenticate" => $auth];
		$result = $response->headers;
		$this->assertEqual($expected, $result);

		$expected = [
			'realm' => 'app', 'qop' => 'auth', 'nonce' => '4ee1617b8756e',
			'opaque' => 'dd7bcee161192cb8fba765eb595eba87'
		];
		$result = array_filter($response->digest());
		$this->assertEqual($expected, $result);
	}

	public function testSetCookies() {
		$expected = [
			'foo' => ['value' => 'bar'],
			'bin' => ['value' => 'baz', 'path' => '/app', 'domain' => 'li3.me', 'httponly' => true]
		];
		$response = new Response();
		$response->cookies('foo', 'bar');
		$response->cookies('bin', [
			'value' => 'baz', 'path' => '/app', 'domain' => 'li3.me', 'httponly' => true
		]);

		$result = $response->cookies;
		$this->assertEqual($expected, $result);

		$response = new Response();
		$response->cookies('foo', 'bar');
		$response->cookies([
			'bin' => ['value' => 'baz', 'path' => '/app', 'domain' => 'li3.me', 'httponly' => true]
		]);
		$result = $response->cookies;
		$this->assertEqual($expected, $result);

		$response = new Response();
		$response->cookies([
			'foo' => 'bar',
			'bin' => ['value' => 'baz', 'path' => '/app', 'domain' => 'li3.me', 'httponly' => true]
		]);
		$result = $response->cookies;
		$this->assertEqual($expected, $result);

		$result = $response->cookies();
		$this->assertEqual($expected, $result);

		$expected = ['value' => 'bar'];
		$result = $response->cookies('foo');
		$this->assertEqual($expected, $result);

		$expected = ['value' => 'baz', 'path' => '/app', 'domain' => 'li3.me', 'httponly' => true];
		$result = $response->cookies('bin');
		$this->assertEqual($expected, $result);

		$result = $response->cookies('bla');
		$this->assertNull($result);
	}

	public function testSetCookiesMultipleValues() {
		$response = new Response();
		$response->cookies(['foo' => 'bar', 'bin' => 'baz']);
		$response->cookies('foo', ['value' => 'bin', 'path' => '/foo']);

		$expected = [
			'foo' => [
				['value' => 'bar'],
				['value' => 'bin', 'path' => '/foo']
			],
			'bin' => ['value' => 'baz']
		];
		$result = $response->cookies;
		$this->assertEqual($expected, $result);

		$response = new Response();
		$response->cookies([
			'foo' => [
				'bar',
				['value' => 'bin', 'path' => '/foo']
			],
			'bin' => 'baz'
		]);
		$result = $response->cookies;
		$this->assertEqual($expected, $result);
	}

	public function testMalformedStatus() {
		$expected = "HTTP/1.1 304 Not Modified";

		$message = join("\r\n", [
			'HTTP/1.1 304',
			'Header: Value',
			'Connection: close',
			'Content-Type: application/json;charset=iso-8859-1',
			'',
			'Test!'
		]);

		$response = new Response(compact('message'));
		$result = $response->status();
		$this->assertEqual($expected, $result);

		$expected = "HTTP/1.1 500 Internal Server Error";

		$message = join("\r\n", [
			'HTTP/1.1 500',
			'Header: Value',
			'Connection: close',
			'Content-Type: application/json;charset=iso-8859-1',
			'',
			'Test!'
		]);

		$response = new Response(compact('message'));
		$result = $response->status();
		$this->assertEqual($expected, $result);
	}
}

?>