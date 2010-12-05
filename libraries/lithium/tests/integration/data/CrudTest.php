<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use Exception;
use lithium\data\Connections;
use lithium\tests\mocks\data\Company;

class CrudTest extends \lithium\test\Integration {

	protected $_connection = null;

	protected $_key = null;

	public $companyData = array(
		array('name' => 'StuffMart', 'active' => true),
		array('name' => 'Ma \'n Pa\'s Data Warehousing & Bait Shop', 'active' => false)
	);

	public function setUp() {
		$this->_connection = Connections::get('test');
		Company::config();
		$this->_key = Company::meta('key');
	}

	/**
	 * Skip the test if no test database connection available.
	 *
	 * @return void
	 */
	public function skip() {
		$isAvailable = (
			Connections::get('test', array('config' => true)) &&
			Connections::get('test')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available.");
	}

	/**
	 * Tests that a single record with a manually specified primary key can be created, persisted
	 * to an arbitrary data store, re-read and updated.
	 *
	 * @return void
	 */
	public function testCreate() {
		$new = Company::create(array($this->_key => 12345, 'name' => 'Acme, Inc.'));

		$result = $new->data();
		$expected = array($this->_key => 12345, 'name' => 'Acme, Inc.');
		$this->assertEqual($expected[$this->_key], $result[$this->_key]);
		$this->assertEqual($expected['name'], $result['name']);

		$this->assertFalse($new->exists());
		$this->assertTrue($new->save());
		$this->assertTrue($new->exists());
	}

	public function testRead() {
		$existing = Company::find(12345);
		$expected = array($this->_key => 12345, 'name' => 'Acme, Inc.');
		$result = $existing->data();
		$this->assertEqual($expected[$this->_key], $result[$this->_key]);
		$this->assertEqual($expected['name'], $result['name']);
		$this->assertTrue($existing->exists());
	}

	public function testUpdate() {
		$existing = Company::find(12345);
		$existing->name = 'Big Brother and the Holding Company';
		$result = $existing->save();
		$this->assertTrue($result);

		$existing = Company::find(12345);
		$result = $existing->data();
		$expected = array($this->_key => 12345, 'name' => 'Big Brother and the Holding Company');
		$this->assertEqual($expected[$this->_key], $result[$this->_key]);
		$this->assertEqual($expected['name'], $result['name']);
	}

	public function testDelete() {
		$existing = Company::find(12345);
		$this->assertTrue($existing->delete());
	}
}