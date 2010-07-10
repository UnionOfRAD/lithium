<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\net\http;

use \lithium\net\http\Service;

class ServiceTest extends \lithium\test\Integration {

	public function testStreamGet() {
		$service = new Service(array(
			'classes' => array('socket' => '\lithium\net\socket\Stream')
		));
		$this->assertPattern('/localhost/', $service->get());
	}

	public function testContextGet() {
		$service = new Service(array(
			'classes' => array('socket' => '\lithium\net\socket\Context')
		));
		$this->assertPattern('/localhost/', $service->get());
	}

	public function testCurlGet() {
		$service = new Service(array(
			'classes' => array('socket' => '\lithium\net\socket\Curl')
		));
		$this->assertPattern('/localhost/', $service->get());
	}
}
