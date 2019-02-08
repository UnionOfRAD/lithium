<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\source\database\adapter;

use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\tests\mocks\data\model\database\adapter\MockMySql;
use lithium\tests\mocks\data\model\MockDatabasePost;

class MySqlTest extends \lithium\test\Unit {

	protected $_db = null;

	protected $_model = 'lithium\tests\mocks\data\model\MockDatabasePost';

	public function setUp() {
		Connections::add('mock', [
			'object' => $this->_db = new MockMySql()
		]);
		MockDatabasePost::config([
			'meta' => ['connection' => 'mock']
		]);
	}

	public function tearDown() {
		Connections::remove('mock');
		MockDatabasePost::reset();
	}

	public function testDsnWithHostPort() {
		$db = new MockMySql([
			'autoConnect' => false,
			'database' => 'test',
			'host' => 'localhost:1234',
		]);
		$expected = 'mysql:host=localhost;port=1234;dbname=test';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);
	}

	public function testDsnWithHost() {
		$db = new MockMySql([
			'autoConnect' => false,
			'database' => 'test',
			'host' => 'localhost',
		]);
		$expected = 'mysql:host=localhost;port=3306;dbname=test';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);
	}

	public function testDsnWithPort() {
		$db = new MockMySql([
			'autoConnect' => false,
			'database' => 'test',
			'host' => ':1234',
		]);
		$expected = 'mysql:host=localhost;port=1234;dbname=test';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);
	}

	public function testDsnWithSocket() {
		$db = new MockMySql([
			'autoConnect' => false,
			'database' => 'test',
			'host' => '/tmp/foo/bar.socket',
		]);
		$expected = 'mysql:unix_socket=/tmp/foo/bar.socket;dbname=test';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);
	}

	/**
	 * We test only the operators that are added/removed/modified from the
	 * `Database::$_operators`. The latter are already tested in the Database case.
	 */
	public function testQueryOperators() {
		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'title' => ['regexp' => '^[a-z0-9]+(\w)*$']
		]]);
		$expected  = "SELECT * FROM `mock_database_posts` AS `MockDatabasePost` WHERE ";
		$expected .= "(`title` REGEXP '^[a-z0-9]+(\\w)*$');";
		$result = $this->_db->renderCommand($query);
		$this->assertEqual($expected, $result);

		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'title' => ['not regexp' => '^[a-z0-9]+(\w)*$']
		]]);
		$expected  = "SELECT * FROM `mock_database_posts` AS `MockDatabasePost` WHERE ";
		$expected .= "(`title` NOT REGEXP '^[a-z0-9]+(\\w)*$');";
		$result = $this->_db->renderCommand($query);
		$this->assertEqual($expected, $result);

		$query = new Query(['type' => 'read', 'model' => $this->_model, 'conditions' => [
			'title' => ['sounds like' => 'foo']
		]]);
		$expected  = "SELECT * FROM `mock_database_posts` AS `MockDatabasePost` WHERE ";
		$expected .= "(`title` SOUNDS LIKE 'foo');";
		$result = $this->_db->renderCommand($query);
		$this->assertEqual($expected, $result);
	}
}

?>