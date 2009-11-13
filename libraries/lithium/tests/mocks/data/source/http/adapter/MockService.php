<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\http\adapter;

class MockService extends \lithium\http\Service {

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