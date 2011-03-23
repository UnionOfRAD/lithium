<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\action;

use lithium\action\Response;
use lithium\tests\mocks\action\MockResponse;

class ResponseTest extends \lithium\test\Unit {

	public $response = null;

	public function setUp() {
		$this->response = new MockResponse(array('init' => false));
	}

	public function testTypeManipulation() {
		$this->assertEqual('html', $this->response->type());
		$this->assertEqual('html', $this->response->type('html'));
		$this->assertEqual('json', $this->response->type('json'));
		$this->assertEqual('json', $this->response->type());
		$this->assertEqual(false, $this->response->type(false));
		$this->assertEqual(false, $this->response->type());
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

		$expires = strtotime('+1 hour');
		$this->response->cache($expires);
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$headers = array (
			'HTTP/1.1 200 OK',
			'Expires: ' . gmdate('D, d M Y H:i:s', $expires) . ' GMT',
			'Cache-Control: max-age=' . ($expires - time()),
			'Pragma: cache'
		);
		$this->assertEqual($headers, $this->response->testHeaders);

		$expires = '+2 hours';
		$this->response->cache($expires);
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$headers = array (
			'HTTP/1.1 200 OK',
			'Expires: ' . gmdate('D, d M Y H:i:s', strtotime($expires)) . ' GMT',
			'Cache-Control: max-age=' . (strtotime($expires) - time()),
			'Pragma: cache'
		);
		$this->assertEqual($headers, $this->response->testHeaders);

		$this->response->body = 'Created';
		$this->response->status(201);
		$this->response->cache(false);

		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertEqual('Created', $result);

		$headers = array (
			'HTTP/1.1 201 Created',
			'Expires: Mon, 26 Jul 1997 05:00:00 GMT',
			array(
				'Cache-Control: no-store, no-cache, must-revalidate',
				'Cache-Control: post-check=0, pre-check=0',
				'Cache-Control: max-age=0'
			),
			'Pragma: no-cache'
		);
		$this->assertEqual($headers, $this->response->testHeaders);

		$this->expectException('/^`Request::disableCache\(\)`.+`Request::cache\(false\)`/');
		$this->response->disableCache();
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

		$this->response->status('foobar');
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$expected = array('HTTP/1.1 500 Internal Server Error');
		$this->assertEqual($expected, $this->response->testHeaders);
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

	public function testLocationHeaderStatus() {
		$this->response = new MockResponse();
		$this->response->status(301);
		$this->response->headers('location', '/');
		ob_start();
		$this->response->render();
		ob_end_clean();

		$headers = array('HTTP/1.1 301 Moved Permanently', 'Location: /');
		$this->assertEqual($headers, $this->response->testHeaders);

		$this->response = new Response(array(
			'classes' => array('router' => __CLASS__),
			'location' => array('controller' => 'foo_bar', 'action' => 'index')
		));
		$this->assertEqual(array('location: /foo_bar'), $this->response->headers());
	}

	public static function match($url) {
		if ($url == array('controller' => 'foo_bar', 'action' => 'index')) {
			return '/foo_bar';
		}
	}
}

?>