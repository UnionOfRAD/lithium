<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\data\source\database\adapter\pdo;

use PDOStatement;
use lithium\data\Schema;
use lithium\data\source\database\adapter\pdo\Result;

class ResultTest extends \lithium\tests\integration\data\Base {

	protected $_schema = [
		'fields' => [
			'id' => ['type' => 'id'],
			'name' => ['type' => 'string', 'length' => 255],
			'active' => ['type' => 'boolean'],
			'created' => ['type' => 'datetime', 'null' => true],
			'modified' => ['type' => 'datetime', 'null' => true]
		]
	];

	protected $_mockData = [
		1 => [1, 'Foo Gallery'],
		2 => [2, 'Bar Gallery']
	];

	/**
	 * Skip the test if a MySQL adapter configuration is unavailable.
	 *
	 * @todo Tie into the Environment class to ensure that the test database is being used.
	 */
	public function skip() {
		parent::connect($this->_connection);
		$this->skipIf(!$this->with(['MySql', 'PostgreSql', 'Sqlite3']));
	}

	/**
	 * Creating the test database
	 */
	public function setUp() {
		$this->_db->dropSchema('galleries');
		$schema = new Schema($this->_schema);
		$this->_db->createSchema('galleries', $schema);
		foreach ($this->_mockData as $entry) {
			$sql = "INSERT INTO galleries (name) VALUES ('" . $entry[1] . "')";
			$this->_db->read($sql, ['return' => 'resource']);
		}
	}

	/**
	 * Dropping the test database
	 */
	public function tearDown() {
		$this->_db->dropSchema('galleries');
	}

	public function testConstruct() {
		$result = new Result();
		$this->assertNull($result->resource());

		$resource = new PDOStatement;
		$result = new Result(compact('resource'));
		$this->assertInstanceOf('PDOStatement', $result->resource());
	}

	public function testNext() {
		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));

		$this->assertEqual($this->_mockData[1], $result->current());
		$this->assertEqual($this->_mockData[2], $result->next());
		$this->assertNull($result->next());
	}

	public function testValid() {
		$result = new Result();
		$this->assertFalse($result->valid());

		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));
		$this->assertTrue($result->valid());
	}

	public function testRewindIsNoop() {
		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));

		$this->assertEqual($this->_mockData[1], $result->current());
		$this->assertEqual($this->_mockData[2], $result->next());
		$result->rewind();
		$this->assertEqual($this->_mockData[2], $result->current());
	}

	public function testCurrent() {
		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));

		$this->assertEqual($this->_mockData[1], $result->current());
		$this->assertEqual($this->_mockData[1], $result->current());
		$this->assertEqual($this->_mockData[2], $result->next());
		$this->assertEqual($this->_mockData[2], $result->current());
	}

	public function testKey() {
		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));

		$this->assertIdentical(0, $result->key());
		$result->next();
		$this->assertIdentical(1, $result->key());
		$result->next();
		$this->assertIdentical(null, $result->key());
	}

	/**
	 * Test that a Result object can be used in a foreach loop
	 */
	public function testResultForeach() {

		$result = $this->_db->read('SELECT name, active FROM galleries', [
			'return' => 'resource'
		]);

		$rows = [];
		foreach ($result as $row) {
			$rows[] = $row;
		}

		$expected = [
			['Foo Gallery', null],
			['Bar Gallery', null]
		];

		$this->assertEqual($expected, $rows);
	}

	/**
	 * Test that an empty Result object can be used in a foreach loop
	 */
	public function testEmptyResultForeach() {

		$this->_db->delete('DELETE FROM galleries');

		$result = $this->_db->read('SELECT name, active FROM galleries', [
			'return' => 'resource'
		]);

		$rows = [];
		foreach ($result as $row) {
			$rows[] = $row;
		}

		$expected = [];

		$this->assertEqual($expected, $rows);
	}
}

?>