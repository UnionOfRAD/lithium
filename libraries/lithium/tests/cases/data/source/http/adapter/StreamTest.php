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

use lithium\data\source\http\adapter\Stream;

class StreamMock extends \lithium\data\source\http\adapter\Stream {

	public function resource() {
		return $this->_connection->resource();
	}
}

class StreamTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'adapter' => 'Stream',
		'persistent' => false,
		'protocol' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2
	);

	public function testAllMethodsNoConnection() {
		$stream = new StreamMock(array('protocol' => null));
		$this->assertFalse($stream->connect());
		$this->assertTrue($stream->disconnect());
	}

	public function testConnect() {
		$stream = new StreamMock($this->_testConfig);
		$result = $stream->connect();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertTrue(is_resource($result));
	}

	public function testDisconnect() {
		$stream = new StreamMock($this->_testConfig);
		$result = $stream->connect();
		$this->assertTrue($result);

		$result = $stream->disconnect();
		$this->assertTrue($result);

		$result = $stream->resource();
		$this->assertFalse(is_resource($result));
	}

	public function testGet() {
		$stream = new StreamMock($this->_testConfig);
		$result = $stream->connect();
		$this->assertTrue($result);

		$result = $stream->get();
		$this->assertTrue($result);

		$expected = 'HTTP/1.1';
		$result = $stream->response->protocol;
		$this->assertEqual($expected, $result);

		$expected = '200';
		$result = $stream->response->status['code'];
		$this->assertEqual($expected, $result);

		$expected = 'OK';
		$result = $stream->response->status['message'];
		$this->assertEqual($expected, $result);

		$expected = 'text/html';
		$result = $stream->response->type;
		$this->assertEqual($expected, $result);

		$expected = 'ISO-8859-1';
		$result = $stream->response->charset;
		$this->assertEqual($expected, $result);
	}
}
?>