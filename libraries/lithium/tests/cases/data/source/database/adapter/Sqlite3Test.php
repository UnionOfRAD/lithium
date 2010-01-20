<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\database\adapter;

use \lithium\data\Connections;
use \lithium\data\source\database\adapter\Sqlite3;
use \lithium\tests\mocks\data\source\database\adapter\MockSqlite3;

class Sqlite3Test extends \lithium\test\Unit {

	protected $_dbConfig = array();

	public $db = null;

	/**
	 * Skip the test if a Sqlite adapter configuration is unavailable.
	 *
	 * @return void
	 * @todo Tie into the Environment class to ensure that the test database is being used.
	 */
	public function skip() {
		$this->_dbConfig = Connections::get('sqlite3', array('config' => true));
		$hasDb = (isset($this->_dbConfig['adapter']) && $this->_dbConfig['adapter'] == 'Sqlite3');
		$message = 'Test database is either unavailable, or not using a Sqlite adapter';
		$this->skipIf(!$hasDb, $message);
	}

	public function setUp() {
		$this->db = new Sqlite3($this->_dbConfig);
	}

	/**
	 * Tests that the object is initialized with the correct default values.
	 *
	 * @return void
	 */
	public function testConstructorDefaults() {
		$db = new MockSqlite3(array('autoConnect' => false));
		$result = $db->get('_config');
		$expected = array(
		  'autoConnect' => false,
		  'database' => '',
		  'flags' => NULL,
		  'key' => NULL,
		  'persistent' => true,
		  'host' => 'localhost',
		  'login' => 'root',
		  'password' => '',
		  'init' => true,
		);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that this adapter can connect to the database, and that the status is properly
	 * persisted.
	 *
	 * @return void
	 */
	public function testDatabaseConnection() {
		$db = new Sqlite3(array('autoConnect' => false) + $this->_dbConfig);
		$this->assertTrue($db->connect());
		$this->assertTrue($db->isConnected());

		$this->assertTrue($db->disconnect());
		$this->assertFalse($db->isConnected());
	}

	public function testDatabaseEncoding() {
		$this->assertTrue($this->db->isConnected());
		$this->assertTrue($this->db->encoding('utf8'));
		$this->assertEqual('UTF-8', $this->db->encoding());

		$this->assertTrue($this->db->encoding('UTF-8'));
		$this->assertEqual('UTF-8', $this->db->encoding());
	}

	public function testColumnAbstraction() {
		$result = $this->db->invokeMethod('_column', array('varchar'));
		$this->assertEqual(array('type' => 'string'), $result);

		$result = $this->db->invokeMethod('_column', array('tinyint(1)'));
		$this->assertEqual(array('type' => 'boolean'), $result);

		$result = $this->db->invokeMethod('_column', array('varchar(255)'));
		$this->assertEqual(array('type' => 'string', 'length' => '255'), $result);

		$result = $this->db->invokeMethod('_column', array('text'));
		$this->assertEqual(array('type' => 'text'), $result);

		$result = $this->db->invokeMethod('_column', array('text'));
		$this->assertEqual(array('type' => 'text'), $result);

		$result = $this->db->invokeMethod('_column', array('decimal(12,2)'));
		$this->assertEqual(array('type' => 'float', 'length' => '12,2'), $result);

		$result = $this->db->invokeMethod('_column', array('int(11)'));
		$this->assertEqual(array('type' => 'integer', 'length' => '11'), $result);
	}

	public function testAbstractColumnResolution() {

	}

	public function testExecuteException() {
		$this->expectException();
		$this->db->read('SELECT deliberate syntax error');
	}
}

?>