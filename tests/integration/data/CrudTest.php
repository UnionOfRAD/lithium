<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\data\Connections;
use lithium\tests\mocks\data\Companies;

class CrudTest extends \lithium\test\Integration {

	protected $_connection = null;

	protected $_key = null;

	public $companyData = array(
		array('name' => 'StuffMart', 'active' => true),
		array('name' => 'Ma \'n Pa\'s Data Warehousing & Bait Shop', 'active' => false)
	);

	public function setUp() {
		Companies::config();
		$this->_key = Companies::key();
		$this->_connection = Connections::get('test');
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
		Companies::all()->delete();
		$this->assertIdentical(0, Companies::count());

		$new = Companies::create(array('name' => 'Acme, Inc.', 'active' => true));
		$this->assertEqual($new->data(), array('name' => 'Acme, Inc.', 'active' => true));

		$this->assertEqual(
			array(false, true, true),
			array($new->exists(), $new->save(), $new->exists())
		);
		$this->assertIdentical(1, Companies::count());
	}

	public function testRead() {
		$existing = Companies::first();

		foreach (Companies::key($existing) as $val) {
			$this->assertTrue($val);
		}
		$this->assertEqual('Acme, Inc.', $existing->name);
		$this->assertTrue($existing->active);
		$this->assertTrue($existing->exists());
	}

	public function testUpdate() {
		$existing = Companies::first();
		$this->assertEqual($existing->name, 'Acme, Inc.');
		$existing->name = 'Big Brother and the Holding Company';
		$result = $existing->save();
		$this->assertTrue($result);

		$existing = Companies::first();
		foreach (Companies::key($existing) as $val) {
			$this->assertTrue($val);
		}
		$this->assertTrue($existing->active);
		$this->assertEqual('Big Brother and the Holding Company', $existing->name);
	}

	public function testDelete() {
		$existing = Companies::first();
		$this->assertTrue($existing->exists());
		$this->assertTrue($existing->delete());
		$this->assertNull(Companies::first(array('conditions' => Companies::key($existing))));
		$this->assertIdentical(0, Companies::count());
	}

	public function testCrudMulti() {
		$large  = Companies::create(array('name' => 'BigBoxMart', 'active' => true));
		$medium = Companies::create(array('name' => 'Acme, Inc.', 'active' => true));
		$small  = Companies::create(array('name' => 'Ma & Pa\'s', 'active' => true));

		foreach (array('large', 'medium', 'small') as $key) {
			$this->assertFalse(${$key}->exists());
			$this->assertTrue(${$key}->save());
			$this->assertTrue(${$key}->exists());
		}
		$this->assertEqual(3, Companies::count());

		$all = Companies::all();
		$this->assertEqual(3, $all->count());

		$match = 'BigBoxMart';
		$filter = function($entity) use (&$match) { return $entity->name == $match; };

		foreach (array('BigBoxMart', 'Acme, Inc.', 'Ma & Pa\'s') as $match) {
			$this->assertTrue($all->first($filter)->exists());
		}
		$this->assertEqual(array(true, true, true), array_values($all->delete()));
		$this->assertEqual(0, Companies::count());
	}
}

?>