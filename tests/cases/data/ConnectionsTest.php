<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data;

use Exception;
use lithium\data\Connections;
use lithium\data\source\database\adapter\MySql;
use lithium\data\source\database\adapter\PostgreSql;

class ConnectionsTest extends \lithium\test\Unit {

	public $config = [
		'type'     => 'Mock',
		'adapter'  => null,
		'host'     => 'localhost',
		'login'    => '--user--',
		'password' => '--pass--',
		'database' => 'db'
	];

	protected $_port = null;

	protected $_backup = [];

	public function setUp() {
		if (!$this->_backup) {
			foreach (Connections::get() as $conn) {
				$this->_backup[$conn] = Connections::get($conn, ['config' => true]);
			}
		}
		Connections::reset();
	}

	public function tearDown() {
		Connections::reset();
		Connections::config($this->_backup);
	}

	public function testConnectionCreate() {
		$result = Connections::add('conn-test', ['type' => 'Mock'] + $this->config);
		$expected = $this->config + ['type' => 'Mock'];
		$this->assertEqual($expected, $result);

		$result = Connections::get('conn-test');
		$this->assertInstanceOf('lithium\data\source\Mock', $result);

		$result = Connections::add('conn-test-2', $this->config);
		$this->assertEqual($expected, $result);

		$result = Connections::get('conn-test-2');
		$this->assertInstanceOf('lithium\data\source\Mock', $result);
	}

	public function testConnectionGetAndReset() {
		Connections::add('conn-test', $this->config);
		Connections::add('conn-test-2', $this->config);
		$this->assertEqual(['conn-test', 'conn-test-2'], Connections::get());

		$enabled = (MySql::enabled() || PostgreSql::enabled());
		$this->skipIf(!$enabled, 'MySql or PostgreSQL is not enabled');

		if (MySql::enabled()) {
			$this->_port = 3306;
		}
		if (PostgreSql::enabled()) {
			$this->_port = 5432;
		}

		$msg = "Cannot connect to localhost:{$this->_port}";
		$this->skipIf(!$this->_canConnect('localhost', $this->_port), $msg);

		$expected = $this->config + ['type' => 'database', 'filters' => []];
		$this->assertEqual($expected, Connections::get('conn-test', ['config' => true]));

		$this->assertNull(Connections::reset());
		$this->assertEmpty(Connections::get());

		$this->assertInternalType('array', Connections::get());
	}

	public function testConnectionAutoInstantiation() {
		Connections::add('conn-test', $this->config);
		Connections::add('conn-test-2', $this->config);

		$result = Connections::get('conn-test');
		$this->assertInstanceOf('lithium\data\source\Mock', $result);

		$result = Connections::get('conn-test');
		$this->assertInstanceOf('lithium\data\source\Mock', $result);

		$this->assertNull(Connections::get('conn-test-2', ['autoCreate' => false]));
	}

	public function testInvalidConnection() {
		$this->assertNull(Connections::get('conn-invalid'));
	}

	public function testStreamConnection() {
		$config = [
			'type' => 'Http',
			'socket' => 'Stream',
			'host' => 'localhost',
			'login' => 'root',
			'password' => '',
			'port' => '80'
		];

		Connections::add('stream-test', $config);
		$result = Connections::get('stream-test');
		$this->assertInstanceOf('lithium\data\source\Http', $result);
		Connections::config(['stream-test' => false]);
	}

	public function testErrorExceptions() {
		$config = [
			'adapter' => 'None',
			'type' => 'Error'
		];
		Connections::add('NoConnection', $config);
		$result = false;

		try {
			Connections::get('NoConnection');
		} catch(Exception $e) {
			$result = true;
		}
		$this->assertTrue($result, 'Exception is not thrown');
	}

	public function testGetNullAdapter() {
		Connections::reset();
		$this->assertInstanceOf('lithium\data\source\Mock', Connections::get(false));
	}

	public function testConnectionRemove() {
		$result = Connections::add('conn-to-remove', ['type' => 'Mock'] + $this->config);
		$expected = $this->config + ['type' => 'Mock'];
		$this->assertEqual($expected, $result);

		$result = Connections::get('conn-to-remove');
		$this->assertInstanceOf('lithium\data\source\Mock', $result);

		Connections::remove('conn-to-remove');

		$result = Connections::get('conn-to-remove');
		$this->assertNull($result);
	}

	protected function _canConnect($host, $port) {
		$success = false;
		set_error_handler(function() {});

		if ($conn = fsockopen($host, $port)) {
			fclose($conn);
			$success = true;
		}
		restore_error_handler();
		return $success;
	}
}

?>