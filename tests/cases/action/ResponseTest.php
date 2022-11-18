<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\action;

use lithium\action\Response;
use lithium\tests\mocks\action\MockResponse;

class ResponseTest extends \lithium\test\Unit {

	public $response = null;

	public function setUp() {
		$this->response = new MockResponse([AUTO_INIT_CLASS => false]);
	}

	public function testTypeManipulation() {
		$this->assertEqual('html', $this->response->type());
		$this->assertEqual('html', $this->response->type('html'));
		$this->assertEqual('json', $this->response->type('json'));
		$this->assertEqual('json', $this->response->type());
		$this->assertEqual(false, $this->response->type(false));
		$this->assertEqual(false, $this->response->type());
	}

	public function testResponseRenderString() {
		$this->response->body = 'Document body';

		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertIdentical('Document body', $result);
		$this->assertIdentical(['HTTP/1.1 200 OK'], $this->response->testHeaders);
	}

	public function testResponseRenderJson() {
		$this->response->type('json');
		$this->response->body[] = '{"message": "Hello World"}';

		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertIdentical('{"message": "Hello World"}', $result);
		$this->assertIdentical('HTTP/1.1 200 OK', $this->response->testHeaders[0]);
	}

	public function testResponseRenderWithCookies() {
		$this->response->cookies([
			'Name' => ['value' => 'Ali', 'domain' => '.li3.me', 'secure' => true],
			'Destination' => ['value' => 'The Future', 'expires' => 'Oct 21 2015 4:29 PM PDT']
		]);
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$expected = [
			'HTTP/1.1 200 OK',
			'Set-Cookie: Name=Ali; Domain=.li3.me; Secure',
			'Set-Cookie: Destination=The%20Future; Expires=Wed, 21-Oct-2015 23:29:00 GMT',
		];
		$this->assertIdentical($expected, $this->response->testHeaders);
	}

	public function testResponseToString() {
		$this->response->type(false);
		$this->response->body = 'Document body';

		ob_start();
		echo $this->response;
		$result = ob_get_clean();
		$this->assertIdentical('Document body', $result);
		$this->assertIdentical(['HTTP/1.1 200 OK'], $this->response->testHeaders);
	}

	public function testResponseCaching() {
		$this->response->body = 'Document body';

		$time = time();
		$expires = strtotime("@{$time} +1 hour");
		$this->response->cache($expires);
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$headers = [
			'HTTP/1.1 200 OK',
			'Expires: ' . gmdate('D, d M Y H:i:s', $expires) . ' GMT',
			'Cache-Control: max-age=' . ($expires - $time),
			'Pragma: cache'
		];
		$this->assertIdentical($headers, $this->response->testHeaders);

		$expires = strtotime("@{$time} +2 hours");
		$this->response->cache($expires);
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$headers = [
			'HTTP/1.1 200 OK',
			'Expires: ' . gmdate('D, d M Y H:i:s', $expires) . ' GMT',
			'Cache-Control: max-age=' . ($expires - time()),
			'Pragma: cache'
		];
		$this->assertIdentical($headers, $this->response->testHeaders);

		$this->response->body = 'Created';
		$this->response->status(201);
		$this->response->cache(false);

		$result = $this->response->headers();

		$expected = [
			'Expires: Mon, 26 Jul 1997 05:00:00 GMT',
			'Cache-Control: no-store, no-cache, must-revalidate',
			'Cache-Control: post-check=0, pre-check=0',
			'Cache-Control: max-age=0',
			'Pragma: no-cache'
		];
		$this->assertIdentical($expected, $result);

		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertIdentical('Created', $result);

		$headers = [
			'HTTP/1.1 201 Created',
			'Expires: Mon, 26 Jul 1997 05:00:00 GMT',
			'Cache-Control: no-store, no-cache, must-revalidate',
			'Cache-Control: post-check=0, pre-check=0',
			'Cache-Control: max-age=0',
			'Pragma: no-cache'
		];
		$this->assertIdentical($headers, $this->response->testHeaders);
	}

	/**
	 * Tests various methods of specifying HTTP status codes.
	 */
	public function testStatusCodes() {
		$this->response->status('Created');
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertEqual(['HTTP/1.1 201 Created'], $this->response->testHeaders);

		$this->response->status('See Other');
		ob_start();
		$this->response->render();
		$result = ob_get_clean();
		$this->assertEqual(['HTTP/1.1 303 See Other'], $this->response->testHeaders);

		$this->response->status('foobar');
		ob_start();
		$this->response->render();
		ob_get_clean();
		$expected = ['HTTP/1.1 500 Internal Server Error'];
		$this->assertEqual($expected, $this->response->testHeaders);
	}

	/**
	 * Tests location headers.
	 */
	public function testLocationHeader() {
		$this->response = new MockResponse();
		$this->response->status(301);
		$this->response->headers('Location', '/');
		ob_start();
		$this->response->render();
		ob_get_clean();

		$headers = ['HTTP/1.1 301 Moved Permanently', 'Location: /'];
		$this->assertEqual($headers, $this->response->testHeaders);

		$this->response = new MockResponse();
		$this->response->headers('Location', '/');
		ob_start();
		$this->response->render();
		ob_get_clean();

		$headers = ['HTTP/1.1 302 Found', 'Location: /'];
		$this->assertEqual($headers, $this->response->testHeaders);

		$this->response = new Response([
			'classes' => ['router' => __CLASS__],
			'location' => ['controller' => 'foo_bar', 'action' => 'index']
		]);
		$this->assertEqual(['Location: /foo_bar'], $this->response->headers());
	}

	/**
	 * Tests that, when a location is assigned without a status code being set, that the status code
	 * will be automatically set to 302 when the response is rendered.
	 */
	public function testBrowserRedirection() {
		$this->response = new MockResponse(['location' => '/']);
		ob_start();
		$this->response->render();
		ob_get_clean();
		$this->assertEqual('HTTP/1.1 302 Found', $this->response->status());
	}

	public static function match($url) {
		if ($url === ['controller' => 'foo_bar', 'action' => 'index']) {
			return '/foo_bar';
		}
	}
}

?>