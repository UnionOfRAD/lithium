<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\net\http;

class MockSocket extends \lithium\net\Socket {

	public $data = null;

	public $configs = [];

	public function __construct(array $config = []) {
		parent::__construct((array) $config);
	}

	public function open(array $options = []) {
		parent::open($options);
		return true;
	}

	public function close() {
		return true;
	}

	public function eof() {
		return true;
	}

	public function read() {
		if ($this->data->path === '/http_auth/') {
			if (is_array($this->data->auth)) {
				$request = $this->data->to('array');
				$data = $this->data->auth;
				$data['nc'] = '00000001';
				$data['cnonce'] = md5(time());
				$username = $this->data->username;
				$password = $this->data->password;
				$part1 = md5("{$username}:{$data['realm']}:{$password}");
				$part2 = "{$data['nonce']}:{$data['nc']}:{$data['cnonce']}:{$data['qop']}";
				$part3 = md5($this->data->method . ':' . $this->data->path);
				$hash = md5("{$part1}:{$part2}:{$part3}");
				preg_match('/response="(.*?)"/', $this->data->headers('Authorization'), $matches);
				list($match, $response) = $matches;

				if ($hash === $response) {
					return 'success';
				}
			}
			$header = 'Digest realm="app",qop="auth",nonce="4bca0fbca7bd0",';
			$header .= 'opaque="d3fb67a7aa4d887ec4bf83040a820a46";';
			$this->data->headers('WWW-Authenticate', $header);
			$status = "GET HTTP/1.1 401 Authorization Required";
			$response = [$status, join("\r\n", $this->data->headers()), "", "not authorized"];
			return join("\r\n", $response);
		}
		return (string) $this->data;
	}

	public function write($data) {
		if (!is_object($data)) {
			$data = $this->_instance($this->_classes['request'], (array) $data + $this->_config);
		}
		$this->data = $data;
		return true;
	}

	public function timeout($time) {
		return true;
	}

	public function encoding($charset) {
		return true;
	}

	public function config() {
		return $this->_config;
	}
}

?>