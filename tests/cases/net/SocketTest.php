<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\net;

use lithium\tests\mocks\net\http\MockSocket;
use lithium\net\http\Request;

class SocketTest extends \lithium\test\Unit {

	public function testInitialization() {
		$socket = new MockSocket();
		$socket->open(['test' => true]);
		$config = $socket->config();
		$this->assertTrue($config['test']);
	}

	public function testSend() {
		$socket = new MockSocket();
		$message = new Request();
		$response = $socket->send($message, ['response' => 'lithium\net\http\Response']);
		$this->assertInstanceOf('lithium\net\http\Response', $response);
		$this->assertInstanceOf('lithium\net\http\Request', $socket->data);
	}
}

?>