<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source\http\adapter;

class MockSocket extends \lithium\net\Socket {

	protected $_data = null;

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
		return join("\r\n", [
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'',
			json_encode($this->_data)
		]);
	}

	public function write($data) {
		$url = $data->to('url');
		$data = ['ok' => true, 'id' => '12345', 'rev' => '1-2', 'body' => 'something'];

		if (strpos($url, '_all_docs')) {
			$data = ['total_rows' => 3, 'offset' => 0, 'rows' => [
				['doc' => [
					'_id' => 'a1', '_rev' => '1-1',
					'author' => 'author 1',
					'body' => 'body 1'
				]],
				['doc' => [
					'_id' => 'a2', '_rev' => '1-2',
					'author' => 'author 2',
					'body' => 'body 2'
				]],
				['doc' => [
					'_id' => 'a3', '_rev' => '1-3',
					'author' => 'author 3',
					'body' => 'body 3'
				]]
			]];
		} elseif (strpos($url, 'lithium-test/_design/latest/_view/all')) {
			$data = ['total_rows' => 3, 'offset' => 0, 'rows' => [
				['value' => [
					'_id' => 'a1', '_rev' => '1-1',
					'author' => 'author 1',
					'body' => 'body 1'
				]],
				['value' => [
					'_id' => 'a2', '_rev' => '1-2',
					'author' => 'author 2',
					'body' => 'body 2'
				]],
				['value' => [
					'_id' => 'a3', '_rev' => '1-3',
					'author' => 'author 3',
					'body' => 'body 3'
				]]
			]];
		} elseif (strpos($url, 'lithium-test/12345?rev=1-1')) {
			$data = [
				'ok' => true, '_id' => '12345', '_rev' => '1-1'
			];
		} elseif (strpos($url, 'lithium-test/12345')) {
			$data = [
				'_id' => '12345', '_rev' => '1-2', 'author' => 'author 1', 'body' => 'body 1'
			];
		}
		return $this->_data = $data;
	}

	public function timeout($time) {
		return true;
	}

	public function encoding($charset) {
		return true;
	}
}

?>