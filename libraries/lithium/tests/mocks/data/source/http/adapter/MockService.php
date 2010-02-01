<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\http\adapter;

use \lithium\net\http\Response;

class MockService extends \lithium\net\http\Service {

	public function send($method, $path = null, $data = array(), $options = array()) {
		$defaults = array('return' => 'body', 'type' => 'form');
		$options += $defaults;
		$request = $this->_request($method, $path, $data, $options);
		$response = new Response();

		$response->body = json_encode(array(
			'ok' => true,
			'id' => '12345',
			'rev' => '1-2',
			'body' => 'something'
		));
		$this->last = (object) compact('request', 'response');
		return ($options['return'] == 'body') ? $response->body() : $response;
	}
}

?>