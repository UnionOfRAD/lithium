<?php
namespace lithium\tests\mocks\http;

class MockCouchService extends \lithium\http\Service {

	public function reset() {
		parent::reset();
		$this->response->body = json_encode(array(
			'ok' => true,
			'id' => '12345',
			'body' => 'something'
		));
	}
}
?>