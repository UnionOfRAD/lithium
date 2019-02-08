<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\security\auth\adapter;

use lithium\tests\mocks\security\auth\adapter\MockHttp;
use lithium\action\Request;
use lithium\core\Libraries;

class HttpTest extends \lithium\test\Unit {

	public function testCheckBasicIsFalseRequestsAuth() {
		$request = new Request();
		$http = new MockHttp(['method' => 'basic', 'users' => ['gwoo' => 'li3']]);
		$result = $http->check($request);
		$this->assertEmpty($result);

		$basic = basename(Libraries::get(true, 'path'));
		$expected = ['WWW-Authenticate: Basic realm="' . $basic . '"'];
		$result = $http->headers;
		$this->assertEqual($expected, $result);
	}

	public function testCheckBasicIsTrueProcessesAuthAndSucceeds() {
		$request = new Request([
			'env' => ['PHP_AUTH_USER' => 'gwoo', 'PHP_AUTH_PW' => 'li3']
		]);
		$http = new MockHttp(['method' => 'basic', 'users' => ['gwoo' => 'li3']]);
		$result = $http->check($request);
		$this->assertNotEmpty($result);

		$expected = [];
		$result = $http->headers;
		$this->assertEqual($expected, $result);
	}

	public function testCheckBasicIsTrueProcessesAuthAndSucceedsCgi() {
		$basic = 'Z3dvbzpsaTM=';

		$request = new Request([
			'env' => ['HTTP_AUTHORIZATION' => "Basic {$basic}"]
		]);
		$http = new MockHttp(['method' => 'basic', 'users' => ['gwoo' => 'li3']]);
		$result = $http->check($request);
		$this->assertNotEmpty($result);

		$expected = [];
		$result = $http->headers;
		$this->assertEqual($expected, $result);

		$request = new Request([
			'env' => ['REDIRECT_HTTP_AUTHORIZATION' => "Basic {$basic}"]
		]);
		$http = new MockHttp(['method' => 'basic', 'users' => ['gwoo' => 'li3']]);
		$result = $http->check($request);
		$this->assertNotEmpty($result);

		$expected = [];
		$result = $http->headers;
		$this->assertEqual($expected, $result);
	}

	public function testCheckDigestIsFalseRequestsAuth() {
		$request = new Request();
		$http = new MockHttp(['realm' => 'app', 'users' => ['gwoo' => 'li3']]);
		$result = $http->check($request);
		$this->assertFalse($result);
		$this->assertPattern('/Digest/', $http->headers[0]);
		$this->assertPattern('/realm="app",/', $http->headers[0]);
		$this->assertPattern('/qop="auth",/', $http->headers[0]);
		$this->assertPattern('/nonce=/', $http->headers[0]);
	}

	public function testCheckDigestIsTrueProcessesAuthAndSucceeds() {
		$digest  = 'qop="auth",nonce="4bca0fbca7bd0",';
		$digest .= 'nc="00000001",cnonce="95b2cd1e179bf5414e52ed62811481cf",';
		$digest .= 'uri="/http_auth",realm="app",';
		$digest .= 'opaque="d3fb67a7aa4d887ec4bf83040a820a46",username="gwoo",';
		$digest .= 'response="04d7d878c67f289f37e553d2025e3a52"';

		$request = new Request(['env' => ['PHP_AUTH_DIGEST' => $digest]]);
		$http = new MockHttp(['realm' => 'app', 'users' => ['gwoo' => 'li3']]);
		$result = $http->check($request);
		$this->assertNotEmpty($result);

		$expected = [];
		$result = $http->headers;
		$this->assertEqual($expected, $result);
	}

	public function testCheckDigestIsTrueProcessesAuthAndSucceedsCgi() {
		$digest  = 'qop="auth",nonce="4bca0fbca7bd0",';
		$digest .= 'nc="00000001",cnonce="95b2cd1e179bf5414e52ed62811481cf",';
		$digest .= 'uri="/http_auth",realm="app",';
		$digest .= 'opaque="d3fb67a7aa4d887ec4bf83040a820a46",username="gwoo",';
		$digest .= 'response="04d7d878c67f289f37e553d2025e3a52"';

		$request = new Request([
			'env' => ['HTTP_AUTHORIZATION' => "Digest {$digest}"]
		]);
		$http = new MockHttp(['realm' => 'app', 'users' => ['gwoo' => 'li3']]);
		$result = $http->check($request);
		$this->assertNotEmpty($result);

		$expected = [];
		$result = $http->headers;
		$this->assertEqual($expected, $result);

		$request = new Request([
			'env' => ['REDIRECT_HTTP_AUTHORIZATION' => "Digest {$digest}"]
		]);
		$http = new MockHttp(['realm' => 'app', 'users' => ['gwoo' => 'li3']]);
		$result = $http->check($request);
		$this->assertNotEmpty($result);

		$expected = [];
		$result = $http->headers;
		$this->assertEqual($expected, $result);
	}
}

?>