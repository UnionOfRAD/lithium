<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\http\adapter;

use lithium\data\source\http\adapter\Curl;

class CurlMock extends \lithium\data\source\http\adapter\Curl {
		
	public function resource() {
		return $this->_connection->resource();
	}
}

class CurlTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2
	);

	public function testAllMethodsNoConnection() {
		$stream = new CurlMock(array('protocol' => null));
		$this->assertFalse($stream->connect());
		$this->assertTrue($stream->disconnect());
	}

	public function testConnect() {
		$stream = new CurlMock($this->_testConfig);
		$result = $stream->connect();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testDisconnect() {
		$stream = new CurlMock($this->_testConfig);
		$result = $stream->connect();
		$this->assertTrue($result);

		$result = $stream->disconnect();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertFalse(is_resource($result));
	}

	public function testGet() {
		$this->skipIf(true, 'Curl adapter is not implemented');
		$stream = new CurlMock($this->_testConfig);
		$result = $stream->connect();
		$this->assertTrue($result);

		$result = $stream->get();
		$this->assertPattern("/^HTTP/", $result);
	}
}
?>