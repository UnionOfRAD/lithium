<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\net\http;

class MockSocket extends \lithium\net\Socket {

	public $data = null;

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
		return $this->data;
	}

	public function write($data) {
		return $this->data = $data;
	}

	public function send($message, array $options = array()) {
		if ($this->write($message)) {
			$message = (string) $this->read();
			return compact('message');
		}
	}

	public function timeout($time) {
		return true;
	}

	public function encoding($charset) {
		return true;
	}
}

?>