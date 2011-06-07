<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\http\adapter;

class MockSocket extends \lithium\net\Socket {

	protected $_data = null;

	public function open() {
		return true;
	}

	public function close() {
		return true;
	}

	public function eof() {
		return true;
	}

	public function read() {
		$data = json_encode(array(
			'ok' => true,
			'id' => '12345',
			'rev' => '1-2',
			'body' => 'something'
		));
		return join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'',
			$data
		));
	}

	public function write($data) {
		return $this->_data = $data;
	}

	public function timeout($time) {
		return true;
	}

	public function encoding($charset) {
		return true;
	}

	public function send($message = null, array $options = array()) {
		$message = $this->read();
		return new $options['classes']['response'](compact('message'));
	}
}

?>