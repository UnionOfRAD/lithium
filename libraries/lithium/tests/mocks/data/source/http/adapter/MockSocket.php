<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
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
		return join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'',
			'Test!'
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

	public function send($message, $options = array()) {
		if ($this->write($message)) {
			$message = $this->read();
			$response = new $options['classes']['response'](compact('message'));
			return $response;
		}
	}
}

?>