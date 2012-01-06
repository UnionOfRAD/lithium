<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Integration\Net\Http;

use Lithium\Net\Http\Service;

class ServiceTest extends \Lithium\Test\Integration {

	public function testStreamGet() {
		$service = new Service(array(
			'classes' => array('socket' => '\Lithium\Net\Socket\Stream')
		));
		$service->head();

		$expected = array('code' => 200, 'message' => 'OK');
		$result = $service->last->response->status;
		$this->assertEqual($expected, $result);
	}

	public function testContextGet() {
		$service = new Service(array(
			'classes' => array('socket' => '\Lithium\Net\Socket\Context')
		));
		$service->head();

		$expected = array('code' => 200, 'message' => 'OK');
		$result = $service->last->response->status;
		$this->assertEqual($expected, $result);
	}

	public function testCurlGet() {
		$service = new Service(array(
			'classes' => array('socket' => '\Lithium\Net\Socket\Curl')
		));
		$service->head();

		$expected = array('code' => 200, 'message' => 'OK');
		$result = $service->last->response->status;
		$this->assertEqual($expected, $result);
	}
}

?>