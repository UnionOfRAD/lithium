<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\net\http;

use lithium\net\http\Auth;

class AuthTest extends \lithium\test\Unit {

	public function testBasicEncode() {
		$username = 'gwoo';
		$password = 'li3';
		$response = base64_encode("{$username}:{$password}");
		$expected = compact('username', 'response');
		$result = Auth::encode($username, $password);
		$this->assertEqual($expected, $result);
	}

	public function testDigestEncode() {
		$username = 'gwoo';
		$password = 'li3';
		$nc = '00000001';
		$cnonce = md5(time());
		$user = md5("gwoo:app:li3");
		$nonce = "4bca0fbca7bd0:{$nc}:{$cnonce}:auth";
		$req = md5("GET:/http_auth");
		$response = md5("{$user}:{$nonce}:{$req}");

		$data = [
			'realm' => 'app',
			'method' => 'GET',
			'uri' => '/http_auth',
			'qop' => 'auth',
			'nonce' => '4bca0fbca7bd0',
			'opaque' => 'd3fb67a7aa4d887ec4bf83040a820a46'
		];
		$expected = $data + compact('username', 'response', 'nc', 'cnonce');
		$result = Auth::encode($username, $password, $data);
		$this->assertEqual($expected, $result);
	}

	public function testBasicHeader() {
		$username = 'gwoo';
		$password = 'li3';
		$response = base64_encode("{$username}:{$password}");
		$data = Auth::encode($username, $password);
		$expected = "Basic " . $response;
		$result = Auth::header($data);
		$this->assertEqual($expected, $result);
	}

	public function testDigestHeader() {
		$username = 'gwoo';
		$password = 'li3';
		$nc = '00000001';
		$cnonce = md5(time());
		$user = md5("gwoo:app:li3");
		$nonce = "4bca0fbca7bd0:{$nc}:{$cnonce}:auth";
		$req = md5("GET:/http_auth");
		$hash = md5("{$user}:{$nonce}:{$req}");

		$data = [
			'realm' => 'app',
			'method' => 'GET',
			'uri' => '/http_auth',
			'qop' => 'auth',
			'nonce' => '4bca0fbca7bd0',
			'opaque' => 'd3fb67a7aa4d887ec4bf83040a820a46'
		];
		$data = Auth::encode($username, $password, $data);
		$header = Auth::header($data);
		$this->assertPattern('/Digest/', $header);
		preg_match('/response="(.*?)"/', $header, $matches);
		list($match, $response) = $matches;

		$expected = $hash;
		$result = $response;
		$this->assertEqual($expected, $result);
	}

	public function testDecode() {
		$header = 'qop="auth",nonce="4bca0fbca7bd0",';
		$header .= 'nc="00000001",cnonce="95b2cd1e179bf5414e52ed62811481cf",';
		$header .= 'uri="/http_auth",realm="app",';
		$header .= 'opaque="d3fb67a7aa4d887ec4bf83040a820a46",username="gwoo",';
		$header .= 'response="04d7d878c67f289f37e553d2025e3a52"';

		$expected = [
			'qop' => 'auth', 'nonce' => '4bca0fbca7bd0',
			'nc' => '00000001', 'cnonce' => '95b2cd1e179bf5414e52ed62811481cf',
			'uri' => '/http_auth', 'realm' => 'app',
			'opaque' => 'd3fb67a7aa4d887ec4bf83040a820a46', 'username' => 'gwoo',
			'response' => '04d7d878c67f289f37e553d2025e3a52'
		];
		$result = Auth::decode($header);
		$this->assertEqual($expected, $result);
	}
}

?>