<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\action;

use \lithium\action\Response;

class TestRequestType extends \lithium\action\Request {

	public function type() {
		return 'foo';
	}
}

class MockResponse extends Response {

	public $testHeaders = array();

	public function render() {
		$this->testHeaders = array();
		parent::render();
		$this->headers = array();
	}

	protected function _writeHeader($header, $code = null) {
		$this->testHeaders[] = $header;
	}
}

class ResponseTest extends \lithium\test\Unit {

	public $response = null;

	public function setUp() {
		$this->response = new MockResponse(array('init' => false));
	}

	public function testDefaultTypeInitialization() {
		$this->response = new Response(array('request' => new TestRequestType()));
		$this->assertEqual('foo', $this->response->type());
	}

	public function testTypeManipulation() {
		$this->assertEqual('text/html', $this->response->type());
		$this->assertEqual('html', $this->response->type('html'));
		$this->assertEqual('json', $this->response->type('json'));
		$this->assertEqual('json', $this->response->type());
	}

	public function testResponseRendering() {
		$this->response->body = 'Document body';

		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertEqual('Document body', $result);
		$this->assertEqual(array('HTTP/1.1 200 OK'), $this->response->testHeaders);

		ob_start();
		echo $this->response;
		$result = ob_get_clean();
		$this->assertEqual('Document body', $result);
		$this->assertEqual(array('HTTP/1.1 200 OK'), $this->response->testHeaders);

		$this->response->body = 'Created';
		$this->response->status(201);
		$this->response->disableCache();

		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertEqual('Created', $result);

		$headers = array (
			'HTTP/1.1 201 Created',
			'Expires: Mon, 26 Jul 1997 05:00:00 GMT',
			'Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT',
			array(
				'Cache-Control: no-store, no-cache, must-revalidate',
				'Cache-Control: post-check=0, pre-check=0'
			),
			'Pragma: no-cache'
		);
		$this->assertEqual($headers, $this->response->testHeaders);
	}

	/**
	 * Tests various methods of specifying HTTP status codes.
	 *
	 * @return void
	 */
	public function testStatusCodes() {
		$this->response->status('Created');
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertEqual(array('HTTP/1.1 201 Created'), $this->response->testHeaders);

		$this->response->status('See Other');
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertEqual(array('HTTP/1.1 303 See Other'), $this->response->testHeaders);

		$this->expectException('/Invalid status code/');
		$this->response->status('foobar');
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertFalse($this->response->testHeaders);
	}

	/**
	 * Tests location headers and custom header add-ons, like 'download'.
	 *
	 * @return void
	 */
	public function testHeaderTypes() {
		$this->response->headers('download', 'report.csv');
		ob_start();
		$this->response->render();
		ob_end_clean();

		$headers = array(
			'HTTP/1.1 200 OK',
			'Content-Disposition: attachment; filename="report.csv"'
		);
		$this->assertEqual($headers, $this->response->testHeaders);

		$this->response = new MockResponse();
		$this->response->headers('location', '/');
		ob_start();
		$this->response->render();
		ob_end_clean();

		$headers = array('HTTP/1.1 302 Found', 'Location: /');
		$this->assertEqual($headers, $this->response->testHeaders);
	}
}

?>