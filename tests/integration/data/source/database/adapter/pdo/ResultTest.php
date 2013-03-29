<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data\source\database\adapter\pdo;

use PDOStatement;
use lithium\data\Schema;
use lithium\data\source\database\adapter\pdo\Result;

class ResultTest extends \lithium\tests\integration\data\Base {

	protected $_schema = array(
		'fields' => array(
			'id' => array('type' => 'id'),
			'name' => array('type' => 'string', 'length' => 255),
			'active' => array('type' => 'boolean'),
			'created' => array('type' => 'datetime', 'null' => true),
			'modified' => array('type' => 'datetime', 'null' => true)
		)
	);

	protected $_mockData = array(
		1 => array(1, 'Foo Gallery'),
		2 => array(2, 'Bar Gallery')
	);

	/**
	 * Skip the test if a MySQL adapter configuration is unavailable.
	 *
	 * @todo Tie into the Environment class to ensure that the test database is being used.
	 */
	public function skip() {
		parent::connect($this->_connection);
		$this->skipIf(!$this->with(array('MySql', 'PostgreSql', 'Sqlite3')));
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
			$this->_db->read($sql, array('return' => 'resource'));
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

		$this->assertEqual($this->_mockData[1], $result->next());
		$this->assertEqual($this->_mockData[2], $result->next());
		$this->assertFalse($result->next());
	}

	public function testPrev() {
		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));

		$this->assertNull($result->prev());
		$this->assertEqual($this->_mockData[1], $result->next());
		$this->assertEqual($this->_mockData[2], $result->next());
		$this->assertEqual($this->_mockData[1], $result->prev());
		$this->assertEqual($this->_mockData[2], $result->next());
		$this->assertEqual($this->_mockData[1], $result->prev());
		$this->assertFalse($result->prev());
	}

	public function testValid() {
		$result = new Result();
		$this->assertFalse($result->valid());

		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));
		$this->assertTrue($result->valid());
	}

	public function testRewind() {
		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));

		$this->assertEqual($this->_mockData[1], $result->next());
		$this->assertEqual($this->_mockData[2], $result->next());
		$result->rewind();
		$this->assertEqual($this->_mockData[1], $result->current());
	}

	public function testCurrent() {
		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));

		$this->assertEqual($this->_mockData[1], $result->next());
		$this->assertEqual($this->_mockData[1], $result->current());
		$this->assertEqual($this->_mockData[2], $result->next());
		$this->assertEqual($this->_mockData[2], $result->current());
		$this->assertEqual($this->_mockData[1], $result->prev());
		$this->assertEqual($this->_mockData[1], $result->current());
	}

	public function testKey() {
		$resource = $this->_db->connection->query("SELECT id, name FROM galleries;");
		$result = new Result(compact('resource'));

		$this->assertIdentical(0, $result->key());
		$result->next();
		$this->assertIdentical(1, $result->key());
		$result->prev();
		$this->assertIdentical(0, $result->key());
		$result->next();
		$this->assertIdentical(1, $result->key());
		$result->next();
		$this->assertIdentical(null, $result->key());
		$result->rewind();
		$this->assertIdentical(0, $result->key());
	}

	/**
	 * Test that a Result object can be used in a foreach loop
	 */
	public function testResultForeach() {

		$result = $this->_db->read('SELECT name, active FROM galleries', array(
			'return' => 'resource'
		));

		$rows = array();
		foreach ($result as $row) {
			$rows[] = $row;
		}

		$expected = array(
			array('Foo Gallery', null),
			array('Bar Gallery', null)
		);

		$this->assertEqual($expected, $rows);
	}

	/**
	 * Test that an empty Result object can be used in a foreach loop
	 */
	public function testEmptyResultForeach() {

		$this->_db->delete('DELETE FROM galleries');

		$result = $this->_db->read('SELECT name, active FROM galleries', array(
			'return' => 'resource'
		));

		$rows = array();
		foreach ($result as $row) {
			$rows[] = $row;
		}

		$expected = array();

		$this->assertEqual($expected, $rows);
	}
}

?>