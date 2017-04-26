<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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
		Connections::add('mock', array(
			'object' => $this->_db = new MockMySql()
		));
		MockDatabasePost::config(array(
			'meta' => array('connection' => 'mock')
		));
	}

	public function tearDown() {
		Connections::remove('mock');
		MockDatabasePost::reset();
	}

	/**
	 * We test only the operators that are added/removed/modified from the
	 * `Database::$_operators`. The latter are already tested in the Database case.
	 */
	public function testQueryOperators() {
		$query = new Query(array('type' => 'read', 'model' => $this->_model, 'conditions' => array(
			'title' => array('regexp' => '^[a-z0-9]+(\w)*$')
		)));
		$expected  = "SELECT * FROM `mock_database_posts` AS `MockDatabasePost` WHERE ";
		$expected .= "(`title` REGEXP '^[a-z0-9]+(\\w)*$');";
		$result = $this->_db->renderCommand($query);
		$this->assertEqual($expected, $result);

		$query = new Query(array('type' => 'read', 'model' => $this->_model, 'conditions' => array(
			'title' => array('not regexp' => '^[a-z0-9]+(\w)*$')
		)));
		$expected  = "SELECT * FROM `mock_database_posts` AS `MockDatabasePost` WHERE ";
		$expected .= "(`title` NOT REGEXP '^[a-z0-9]+(\\w)*$');";
		$result = $this->_db->renderCommand($query);
		$this->assertEqual($expected, $result);

		$query = new Query(array('type' => 'read', 'model' => $this->_model, 'conditions' => array(
			'title' => array('sounds like' => 'foo')
		)));
		$expected  = "SELECT * FROM `mock_database_posts` AS `MockDatabasePost` WHERE ";
		$expected .= "(`title` SOUNDS LIKE 'foo');";
		$result = $this->_db->renderCommand($query);
		$this->assertEqual($expected, $result);
	}
}

?>