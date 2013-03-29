<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\tests\fixture\model\gallery\Galleries;
use li3_fixtures\test\Fixtures;

class CrudTest extends \lithium\tests\integration\data\Base {

	protected $_fixtures = array(
		'images' => 'lithium\tests\fixture\model\gallery\ImagesFixture',
		'galleries' => 'lithium\tests\fixture\model\gallery\GalleriesFixture',
	);

	/**
	 * Skip the test if no test database connection available.
	 */
	public function skip() {
		parent::connect($this->_connection);
		if (!class_exists('li3_fixtures\test\Fixtures')) {
			$this->skipIf(true, "These tests need `'li3_fixtures'` to be runned.");
		}
	}

	/**
	 * Creating the test database
	 */
	public function setUp() {
		Fixtures::config(array(
			'db' => array(
				'adapter' => 'Connection',
				'connection' => $this->_connection,
				'fixtures' => $this->_fixtures
			)
		));
		Fixtures::create('db', array('galleries'));
	}

	/**
	 * Dropping the test database
	 */
	public function tearDown() {
		Fixtures::clear('db');
	}

	/**
	 * Tests that a single record with a manually specified primary key can be created, persisted
	 * to an arbitrary data store, re-read and updated.
	 */
	public function testCreate() {
		$this->assertIdentical(0, Galleries::count());
		$new = Galleries::create(array('name' => 'Flowers', 'active' => true));
		$expected = array('name' => 'Flowers', 'active' => true);
		$result = $new->data();
		$this->assertEqual($expected['name'], $result['name']);
		$this->assertEqual($expected['active'], $result['active']);

		$this->assertEqual(
			array(false, true, true),
			array($new->exists(), $new->save(), $new->exists())
		);
		$this->assertIdentical(1, Galleries::count());
	}

	public function testRead() {
		Galleries::create(array('name' => 'Flowers', 'active' => true))->save();
		$existing = Galleries::first();
		foreach (Galleries::key($existing) as $val) {
			$this->assertNotEmpty($val);
		}
		$this->assertEqual('Flowers', $existing->name);
		$this->assertNotEmpty($existing->active);
		$this->assertTrue($existing->exists());
	}

	public function testUpdate() {
		Galleries::create(array('name' => 'Flowers', 'active' => true))->save();
		$existing = Galleries::first();
		$this->assertEqual($existing->name, 'Flowers');
		$existing->name = 'Flowers & Poneys';
		$result = $existing->save();
		$this->assertTrue($result);

		$existing = Galleries::first();
		foreach (Galleries::key($existing) as $val) {
			$this->assertNotEmpty($val);
		}
		$this->assertNotEmpty($existing->active);
		$this->assertEqual('Flowers & Poneys', $existing->name);
	}

	public function testDelete() {
		Galleries::create(array('name' => 'Flowers', 'active' => true))->save();
		$existing = Galleries::first();
		$this->assertTrue($existing->exists());
		$this->assertTrue($existing->delete());
		$this->assertNull(Galleries::first(array('conditions' => Galleries::key($existing))));
		$this->assertIdentical(0, Galleries::count());
	}

	public function testCrudMulti() {
		$cities  = Galleries::create(array('name' => 'Cities', 'active' => true));
		$flowers = Galleries::create(array('name' => 'Flowers', 'active' => true));
		$poneys  = Galleries::create(array('name' => 'Poneys', 'active' => true));

		foreach (array('cities', 'flowers', 'poneys') as $key) {
			$this->assertFalse(${$key}->exists());
			$this->assertTrue(${$key}->save());
			$this->assertTrue(${$key}->exists());
		}
		$this->assertEqual(3, Galleries::count());

		$all = Galleries::all();
		$this->assertEqual(3, $all->count());

		$match = 'Cities';
		$filter = function($entity) use (&$match) { return $entity->name === $match; };

		foreach (array('Cities', 'Flowers', 'Poneys') as $match) {
			$this->assertTrue($all->first($filter)->exists());
		}
		$this->assertEqual(array(true, true, true), array_values($all->delete()));
		$this->assertEqual(0, Galleries::count());
	}

	public function testUpdateWithNewProperties() {
		$db = $this->_db;
		$this->skipIf($db::enabled('schema'));

		$new = Galleries::create(array('name' => 'Flowers', 'active' => true));

		$expected = array('name' => 'Flowers', 'active' => true);
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$new->foo = 'bar';
		$expected = array('name' => 'Flowers', 'active' => true, 'foo' => 'bar');
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$this->assertTrue($new->save());

		$updated = Galleries::find('first', array(
			'conditions' => Galleries::key($new)
		));
		$expected = 'bar';
		$result = $updated->foo;
		$this->assertEqual($expected, $result);
	}
}

?>