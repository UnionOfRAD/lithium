<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\net\http;

class MockService extends \lithium\net\http\Service {

	public function get($path = null, $data = array(), $options = array()) {
		$defaults = array('type' => 'form', 'return' => 'body');
		$options += $defaults;
		if ($this->connect() === false) {
			return false;
		}
		$request = $this->_request('get', $path, $data, $options);
		$message = join("\r\n", array(
			'HTTP/1.1 200 OK',
			'Header: Value',
			'Connection: close',
			'Content-Type: text/html;charset=UTF-8',
			'',
			'Test!'
		));
		$response = new $this->_classes['response'](compact('message'));
		$this->last = (object) compact('request', 'response');
		return ($options['return'] == 'body') ? $response->body() : $response;

	}

	public function reset() {
		parent::reset();
		$isJson = (
			!empty($this->request->headers['Content-Type']) &&
			$this->request->headers['Content-Type'] == 'application/json'
		);
		if ($isJson) {
			$this->response->body = json_encode(array('some' => 'json'));
		}
	}
}

?>