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

	public $configs = array();

	public function __construct(array $config = array()) {
		$this->configs[] = $config;
		parent::__construct((array) $config);
	}

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
		if (is_object($this->data)) {
			$data = "HTTP/1.1 200 OK\r\n" .
				join("\r\n", $this->data->headers()) .
				"\r\n\r\n" .
				$this->data->body();
		}
		return $data;
	}

	public function write($data) {
		$this->data = $data;
		return true;
	}

	public function send($message, array $options = array()) {
		$defaults = array('response' => $this->_classes['response']);
		$options += $defaults;

		if ($this->write($message)) {
			$body = $this->read();
			return new $options['response'](compact('message'));
		}
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