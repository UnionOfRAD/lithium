<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\database\adapter;

use \lithium\data\Connections;
use \lithium\data\source\database\adapter\MySQLi;
use \lithium\tests\mocks\data\source\database\adapter\MockMySQLi;

class MySQLiTest extends \lithium\test\Unit {

	protected $_dbConfig = array();

	public $db = null;

	/**
	 * Skip the test if a MySQL adapter configuration is unavailable.
	 *
	 * @return void
	 * @todo Tie into the Environment class to ensure that the test database is being used.
	 */
	public function skip() {
		$message = 'MySQLi extension is not available for testing the adapter.';
		$hasClass = class_exists('\mysqli');
		$this->skipIf(!$hasClass, $message);

		$this->_dbConfig = Connections::get('MySQLi-tests', array('config' => true));
		$hasDb = (isset($this->_dbConfig['adapter']) && $this->_dbConfig['adapter'] == 'MySQLi');
		$message = 'Test database is either unavailable, or not using a MySQLi adapter';
		$this->skipIf(!$hasDb, $message);
	}

	public function setUp() {
		$this->db = new MySQLi($this->_dbConfig);
	}

	/**
	 * Tests that native field types are resolved to Lithiums abstracted versions
	 */
	public function testColumnAbstraction() {
		$result = $this->db->invokeMethod('_column', array('decimal(12,2)'));
		$this->assertEqual(array('type' => 'float', 'length' => '12,2'), $result);

		$result = $this->db->invokeMethod('_column', array('int(11)'));
		$this->assertEqual(array('type' => 'integer', 'length' => '11'), $result);

		$result = $this->db->invokeMethod('_column', array('text'));
		$this->assertEqual(array('type' => 'text'), $result);

		$result = $this->db->invokeMethod('_column', array('text'));
		$this->assertEqual(array('type' => 'text'), $result);

		$result = $this->db->invokeMethod('_column', array('tinyint(1)'));
		$this->assertEqual(array('type' => 'boolean'), $result);

		$result = $this->db->invokeMethod('_column', array('varchar'));
		$this->assertEqual(array('type' => 'string'), $result);

		$result = $this->db->invokeMethod('_column', array('varchar(255)'));
		$this->assertEqual(array('type' => 'string', 'length' => '255'), $result);
	}

	public function testAbstractColumnResolution() {
	}

	/**
	 * Tests that the object is initialized with the correct default values.
	 *
	 * @return void
	 */
	public function testConstructorDefaults() {
		$db = new MockMySQLi(array('autoConnect' => false));
		$result = $db->get('_config');
		$expected = array(
			'autoConnect'	=> false,
			'database'		=> 'lithium',
			'host'			=> 'localhost',
			'init'			=> true,
			'login'			=> 'root',
			'password'		=> '',
			'persistent'	=> true,
			'port'			=> '3306'
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
		$db = new MySQLi(array('autoConnect' => false) + $this->_dbConfig);
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

	public function testExecuteException() {
		$this->expectException();
		$this->db->read('SELECT deliberate syntax error');
	}
}

?>