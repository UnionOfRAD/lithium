<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use \lithium\net\http\Request;

class RequestTest extends \lithium\test\Unit {

	public $request = null;

	public function setUp() {
		$this->request = new Request(array('init' => false));
	}

	public function testConstruct() {
		$request = new Request(array(
			'host' => 'localhost',
			'port' => 443,
			'headers' => array('Header' => 'Value'),
			'body' => array('Part 1'),
			'params' => array('param' => 'value')
		));

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

		$expected = array('param' => 'value');
		$result = $request->params;
		$this->assertEqual($expected, $result);

		$expected = array(
			'Host: localhost:443',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'Header: Value'
		);
		$result = $request->headers();
		$this->assertEqual($expected, $result);

		$expected = array();
		$result = $request->cookies;
		$this->assertEqual($expected, $result);

		$expected = 'Part 1';
		$result = $request->body();
		$this->assertEqual($expected, $result);
	}

	public function testConstructWithPath() {
		$request = new Request(array(
			'host' => 'localhost/base/path',
			'port' => 443,
			'headers' => array('Header' => 'Value'),
			'body' => array('Part 1'),
			'params' => array('param' => 'value')
		));

		$expected = '/base/path/';
		$result = $request->path;
		$this->assertEqual($expected, $result);
	}

	public function testQueryStringDefault() {
		$expected = "?param=value&param1=value1";
		$result = $this->request->queryString(array('param' => 'value', 'param1' => 'value1'));
		$this->assertEqual($expected, $result);
	}

	public function testQueryStringFormat() {
		$expected = "?param:value;param1:value1";
		$result = $this->request->queryString(
			array('param' => 'value', 'param1' => 'value1'), "{:key}:{:value};"
		);
		$this->assertEqual($expected, $result);
	}

	public function testQueryStringSetup() {
		$expected = "?param=value";
		$result = $this->request->queryString(array('param' => 'value'));
		$this->assertEqual($expected, $result);

		$result = $this->request->queryString();
		$this->assertEqual($expected, $result);

		$expected = "?param2=value2";
		$result = $this->request->queryString(array('param2' => 'value2'));
		$this->assertEqual($expected, $result);
	}

	public function testToString() {
		$expected = join("\r\n", array(
			'GET / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'', ''
		));
		$result = (string) $this->request;
		$this->assertEqual($expected, $result);
	}

	public function testToStringWithAuth() {
		$request = new Request(array('auth' => array(
			'method' => 'Basic',
			'username' => 'root', 'password' => 'something'
		)));
		$expected = join("\r\n", array(
			'GET / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'Authorization: Basic ' . base64_encode('root:something'),
			'', ''
		));
		$result = (string) $request;
		$this->assertEqual($expected, $result);
	}

	public function testToStringWithBody() {
		$expected = join("\r\n", array(
			'GET / HTTP/1.1',
			'Host: localhost:80',
			'Connection: Close',
			'User-Agent: Mozilla/5.0 (Lithium)',
			'Content-Length: 11',
			'', 'status=cool'
		));
		$this->request->body(array('status=cool'));
		$result = (string) $this->request;
		$this->assertEqual($expected, $result);
	}
}

?>