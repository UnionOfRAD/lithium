<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\net\http;

use lithium\net\http\Service;

class ServiceTest extends \lithium\test\Integration {

	public function testStreamGet() {
		$service = new Service([
			'host' => 'example.org',
			'classes' => ['socket' => 'lithium\net\socket\Stream']
		]);
		$service->head();

		$expected = ['code' => 200, 'message' => 'OK'];
		$result = $service->last->response->status;
		$this->assertEqual($expected, $result);
	}

	public function testContextGet() {
		$service = new Service([
			'host' => 'example.org',
			'classes' => ['socket' => 'lithium\net\socket\Context']
		]);
		$service->head();

		$expected = ['code' => 200, 'message' => 'OK'];
		$result = $service->last->response->status;
		$this->assertEqual($expected, $result);
	}

	public function testCurlGet() {
		$service = new Service([
			'host' => 'example.org',
			'classes' => ['socket' => 'lithium\net\socket\Curl']
		]);
		$service->head();

		$expected = ['code' => 200, 'message' => 'OK'];
		$result = $service->last->response->status;
		$this->assertEqual($expected, $result);
	}
}

?>