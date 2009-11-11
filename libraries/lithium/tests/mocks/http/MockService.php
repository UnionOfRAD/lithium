<?php
namespace lithium\tests\mocks\http;

class MockService extends \lithium\http\Service {

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