<?php
namespace lithium\tests\mocks\http;

class MockService extends \lithium\http\Service {

	public $testRequest = null;

	public function response($message) {
		$this->response = new $this->_classes['response'](compact('message'));
		return $this->_response->body();
	}

	protected function _send($path = null) {
		$this->testRequest = $this->request;
		return parent::_send($path);
	}
}

?>