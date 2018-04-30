<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\net\http;

use lithium\net\http\Request;

class RequestTest extends \lithium\test\Unit {

	public $request = null;

	public function setUp() {
		$this->request = new Request();
	}

	public function testConstruct() {
		$request = new Request([
			'host' => 'localhost',
			'port' => 443,
			'headers' => ['Header' => 'Value'],
			'body' => ['Part 1']
		]);

		$expected = 'localhost';
		$result = $request->host;
		$this->assertEqual($expected, $result);

		$expected = 443;
		$result = $request->port;
		$this->assertEqual($expected, $result);

		$expected = 'GET';
		$result = $request->method;
		$this->assertEqual($expected, $result);

		$expected = 'HTTP/1.1';
		$result = $request->protocol;
		$this->assertEqual($expected, $result);

		$expected = '1.1';
		$result = $request->version;
		$this->assertEqual($expected, $result);

		$expected = '/';
		$result = $request->path;
		$this->assertEqual($expected, $result);

		$expected = [
			'Host: localhost:443',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Header: Value'
		];
		$result = $request->headers();
		$this->assertEqual($expected, $result);

		$expected = [];
		$result = $request->cookies;
		$this->assertEqual($expected, $result);

		$expected = 'Part 1';
		$result = $request->body();
		$this->assertEqual($expected, $result);
	}

	public function testConstructWithCookies() {
		$request = new Request([
			'host' => 'localhost',
			'port' => 443,
			'headers' => ['Cookie' => 'name1=value1; name2=value2'],
			'body' => ['Part 1'],
			'params' => ['param' => 'value']
		]);

		$expected = ['name1' => 'value1', 'name2' => 'value2'];
		$this->assertEqual($expected, $request->cookies());
	}

	public function testConstructWithPath() {
		$request = new Request([
			'host' => 'localhost/base/path',
			'port' => 443,
			'headers' => ['Header' => 'Value'],
			'body' => ['Part 1'],
			'params' => ['param' => 'value']
		]);

		$expected = '/base/path';
		$result = $request->path;
		$this->assertEqual($expected, $result);
	}

	public function testQueryStringDefault() {
		$expected = "?param=value&param1=value1";
		$result = $this->request->queryString(['param' => 'value', 'param1' => 'value1']);
		$this->assertEqual($expected, $result);
	}

	public function testQueryStringFormat() {
		$expected = "?param:value;param1:value1";
		$result = $this->request->queryString(
			['param' => 'value', 'param1' => 'value1'], "{:key}:{:value};"
		);
		$this->assertEqual($expected, $result);
	}

	public function testQueryStringSetup() {
		$expected = "?param=value";
		$result = $this->request->queryString(['param' => 'value']);
		$this->assertEqual($expected, $result);

		$expected = "?param=value";
		$this->request->query = ['param' => 'value'];
		$result = $this->request->queryString();
		$this->assertEqual($expected, $result);

		$expected = "?param=value&param2=value2";
		$result = $this->request->queryString(['param2' => 'value2']);
		$this->assertEqual($expected, $result);
	}

	public function testQueryStringMerge() {
		$expected = "?param=foo";
		$this->request->query = ['param' => 'value'];
		$result = $this->request->queryString(['param' => 'foo']);
		$this->assertEqual($expected, $result);

		$expected = "?param=foo&param2=bar";
		$result = $this->request->queryString(['param' => 'foo', 'param2' => 'bar']);
		$this->assertEqual($expected, $result);
	}

	public function testToString() {
		$expected = join("\r\n", [
			'GET / HTTP/1.1',
			'Host: localhost',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'', ''
		]);
		$result = (string) $this->request;
		$this->assertEqual($expected, $result);

		$result = $this->request->to('string');
		$this->assertEqual($expected, $result);
	}

	public function testPostToString() {
		$this->request->method = 'POST';
		$expected = join("\r\n", [
			'POST / HTTP/1.1',
			'Host: localhost',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Length: 0',
			'', ''
		]);
		$result = (string) $this->request;
		$this->assertEqual($expected, $result);

		$result = $this->request->to('string');
		$this->assertEqual($expected, $result);
	}

	public function testToStringWithCookies() {
		$request = new Request([
			'cookies' => ['foo' => 'bar', 'bin' => 'baz']
		]);
		$expected = join("\r\n", [
			'GET / HTTP/1.1',
			'Host: localhost',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Cookie: foo=bar; bin=baz',
			'', ''
		]);
		$result = (string) $request;
		$this->assertEqual($expected, $result);
	}

	public function testToContextWithCookies() {
		$request = new Request([
			'cookies' => ['sid' => '8f02d50ec2c4d47ab021d2a9a6aba4bb']
		]);
		$expected = ['http' => [
			'method' => 'GET',
			'header' => [
				'Host: localhost',
				'Connection: Close',
				'User-Agent: Mozilla/5.0',
				'Cookie: sid=8f02d50ec2c4d47ab021d2a9a6aba4bb'
			],
			'content' => '',
			'protocol_version' => '1.1',
			'ignore_errors' => true,
			'follow_location' => true,
			'request_fulluri' => false,
			'proxy' => null
		]];
		$this->assertEqual($expected, $request->to('context'));
	}

	public function testToStringWithAuth() {
		$request = new Request([
			'auth' => 'Basic',
			'username' => 'root',
			'password' => 'something'
		]);
		$expected = join("\r\n", [
			'GET / HTTP/1.1',
			'Host: localhost',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Authorization: Basic ' . base64_encode('root:something'),
			'', ''
		]);
		$result = (string) $request;
		$this->assertEqual($expected, $result);
	}

	public function testToContextWithAuth() {
		$request = new Request([
			'auth' => 'Basic',
			'username' => 'Aladdin',
			'password' => 'open sesame'
		]);
		$expected = ['http' => [
			'method' => 'GET',
			'header' => [
				'Host: localhost',
				'Connection: Close',
				'User-Agent: Mozilla/5.0',
				'Authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ=='
			],
			'content' => '',
			'protocol_version' => '1.1',
			'ignore_errors' => true,
			'follow_location' => true,
			'request_fulluri' => false,
			'proxy' => null
		]];
		$this->assertEqual($expected, $request->to('context'));
	}

	public function testToStringWithBody() {
		$expected = join("\r\n", [
			'GET / HTTP/1.1',
			'Host: localhost',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Length: 11',
			'', 'status=cool'
		]);
		$this->request->body(['status=cool']);
		$result = (string) $this->request;
		$this->assertEqual($expected, $result);
	}

	public function testToArray() {
		$expected = [
			'method' => 'GET',
			'query' => [],
			'headers' => [
				'Host' => 'localhost',
				'Connection' => 'Close',
				'User-Agent' => 'Mozilla/5.0'
			],
			'cookies' => [],
			'protocol' => 'HTTP/1.1',
			'version' => '1.1',
			'body' => [],
			'scheme' => 'http',
			'host' => 'localhost',
			'port' => null,
			'path' => '/',
			'auth' => null,
			'username' => null,
			'password' => null
		];
		$result = $this->request->to('array');
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that creating a `Request` with a proxy configuration correctly modifies the results
	 * of exporting the `Request` to a stream context configuration.
	 */
	public function testWithProxy() {
		$request = new Request(['proxy' => 'tcp://proxy.example.com:5100']);
		$expected = ['http' => [
			'content' => '',
			'method' => 'GET',
			'header' => ['Host: localhost', 'Connection: Close', 'User-Agent: Mozilla/5.0'],
			'protocol_version' => '1.1',
			'ignore_errors' => true,
			'follow_location' => true,
			'request_fulluri' => true,
			'proxy' => 'tcp://proxy.example.com:5100'
		]];
		$this->assertEqual($expected, $request->to('context'));
	}

	public function testToUrl() {
		$expected = 'http://localhost/';
		$result = $this->request->to('url');
		$this->assertEqual($expected, $result);

		$this->request = new Request(['scheme' => 'https', 'port' => 443]);
		$expected = 'https://localhost:443/';
		$result = $this->request->to('url');
		$this->assertEqual($expected, $result);
	}

	public function testToUrlOverride() {
		$request = new Request([
			'scheme' => 'http',
			'host' => 'localhost',
			'port' => 80,
			'query' => ['foo' => 'bar', 'bin' => 'baz']
		]);

		$result = $request->to('url', [
			'scheme' => 'https',
			'host' => 'lithium.com',
			'port' => 443,
			'query' => ['foo' => 'you']
		]);
		$expected = 'https://lithium.com:443/?foo=you';

		$this->assertEqual($expected, $result);
	}

	public function testToContext() {
		$expected = ['http' => [
			'method' => 'GET',
			'content' => '',
			'header' => [
				'Host: localhost',
				'Connection: Close',
				'User-Agent: Mozilla/5.0'
			],
			'protocol_version' => '1.1',
			'ignore_errors' => true,
			'follow_location' => true,
			'request_fulluri' => false,
			'proxy' => null
		]];
		$result = $this->request->to('context');
		$this->assertEqual($expected, $result);
	}

	public function testQueryStringWithArrayValues() {
		$expected = "?param%5B0%5D=value1&param%5B1%5D=value2";
		$result = $this->request->queryString(['param' => ['value1', 'value2']]);
		$this->assertEqual($expected, $result);
	}

	public function testQueryStringWithArrayValuesCustomFormat() {
		$expected = "?param%5B%5D:value1/param%5B%5D:value2";
		$result = $this->request->queryString(
			['param' => ['value1', 'value2']],
			"{:key}:{:value}/"
		);
		$this->assertEqual($expected, $result);
	}

	public function testDigest() {
		$request = new Request([
			'path' => '/http_auth',
			'auth' => [
				'realm' => 'app',
				'qop' => 'auth',
				'nonce' => '4bca0fbca7bd0',
				'opaque' => 'd3fb67a7aa4d887ec4bf83040a820a46'
			],
			'username' => 'gwoo',
			'password' => 'li3'
		]);
		$cnonce = md5(time());
		$user = md5("gwoo:app:li3");
		$nonce = "4bca0fbca7bd0:00000001:{$cnonce}:auth";
		$req = md5("GET:/http_auth");
		$hash = md5("{$user}:{$nonce}:{$req}");

		$request->to('url');
		preg_match('/response="(.*?)"/', $request->headers('Authorization'), $matches);
		list($match, $response) = $matches;

		$expected = $hash;
		$result = $response;
		$this->assertEqual($expected, $result);
	}

	public function testParseUrlToConfig() {
		$url = "http://localhost/path/one.php?param=1&param=2";
		$config = parse_url($url);
		$request = new Request($config);

		$expected = $url;
		$result = $request->to('url');
		$this->assertEqual($expected, $result);

		$url = "http://localhost:80/path/one.php?param=1&param=2";
		$config = parse_url($url);
		$request = new Request($config);

		$expected = $url;
		$result = $request->to('url');
		$this->assertEqual($expected, $result);
	}

	public function testQueryParamsConstructed() {
		$url = "http://localhost/path/one.php?param=1&param=2";
		$config = parse_url($url);
		$request = new Request($config);

		$expected = "?param=1&param=2";
		$result = $request->queryString();
		$this->assertEqual($expected, $result);

		$expected = "?param=1&param=2&param3=3";
		$result = $request->queryString(['param3' => 3]);
		$this->assertEqual($expected, $result);
	}

	public function testKeepDefinedContentTypeHeaderOnPost() {
		$request = new Request([
			'method' => 'POST',
			'headers' => ['Content-Type' => 'text/x-test']
		]);
		$expected = 'Content-Type: text/x-test';
		$result = $request->headers();
		$message = "Expected value `{$expected}` not found in result.";
		$this->assertTrue(in_array($expected, $result), $message);

		$expected = '#Content-Type: text/x-test#';
		$result = $request->to('string');
		$this->assertPattern($expected, $result);
	}

	public function testKeepDefinedContentTypeHeaderWhenTypeIsSet() {
		$request = new Request([
			'method' => 'POST',
			'type' => 'json',
			'headers' => ['Content-Type' => 'text/x-test']
		]);
		$expected = 'Content-Type: text/x-test';
		$result = $request->headers();
		$message = "Expected value `{$expected}` not found in result.";
		$this->assertTrue(in_array($expected, $result), $message);

		$expected = '#Content-Type: text/x-test#';
		$result = $request->to('string');
		$this->assertPattern($expected, $result);
	}
}

?>