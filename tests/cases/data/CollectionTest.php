<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use stdClass;
use lithium\tests\mocks\data\MockCollection;
use lithium\data\Entity;

/**
 * lithium\data\Connections Test.
 */
class CollectionTest extends \lithium\test\Unit {

	/**
	 * Used model.
	 *
	 * @var string
	 */
	protected $_model = 'lithium\tests\mocks\data\MockPost';

	/**
	 * Mock database class.
	 *
	 * @var string
	 */
	protected $_database = 'lithium\tests\mocks\data\MockSource';

	/**
	 * Tests `Collection::stats()`.
	 */
	public function testGetStats() {
		$collection = new MockCollection(array('stats' => array('foo' => 'bar')));
		$this->assertNull($collection->stats('bar'));
		$this->assertEqual('bar', $collection->stats('foo'));
		$this->assertEqual(array('foo' => 'bar'), $collection->stats());
	}

	/**
	 * Tests `Collection` accessors (getters/setters).
	 */
	public function testAccessorMethods() {
		$model = $this->_model;
		$model::config(array('meta' => array('connection' => false, 'key' => 'id')));
		$collection = new MockCollection(compact('model'));
		$this->assertEqual($model, $collection->model());
		$this->assertEqual(compact('model'), $collection->meta());
	}

	/**
	 * Tests `Collection::offsetExists()`.
	 */
	public function testOffsetExists() {
		$collection = new MockCollection();
		$this->assertEqual($collection->offsetExists(0), false);

		$collection = new MockCollection(array('data' => array('bar', 'baz', 'bob' => 'bill')));
		$this->assertEqual($collection->offsetExists(0), true);
		$this->assertEqual($collection->offsetExists(1), true);
	}

	/**
	 * Tests `Collection::rewind` and `Collection::current`.
	 */
	public function testNextRewindCurrent() {
		$collection = new MockCollection(array('data' => array(
			'title' => 'Lorem Ipsum',
			'value' => 42,
			'foo'   => 'bar'
		)));
		$this->assertEqual('Lorem Ipsum', $collection->current());
		$this->assertEqual(42, $collection->next());
		$this->assertEqual('bar', $collection->next());
		$this->assertEqual('Lorem Ipsum', $collection->rewind());
		$this->assertEqual(42, $collection->next());
	}

	/**
	 * Tests `Collection::each`.
	 */
	public function testEach() {
		$collection = new MockCollection(array('data' => array(
			'Lorem Ipsum',
			'value',
			'bar'
		)));
		$collection->each(function($value) {
			return $value . ' test';
		});
		$expected = array(
			'Lorem Ipsum test',
			'value test',
			'bar test'
		);
		$this->assertEqual($expected, $collection->to('array'));
	}

	/**
	 * Tests `Collection::map`.
	 */
	public function testMap() {
		$collection = new MockCollection(array('data' => array(
			'Lorem Ipsum',
			'value',
			'bar'
		)));
		$results = $collection->map(function($value) {
			return $value . ' test';
		});
		$expected = array(
			'Lorem Ipsum test',
			'value test',
			'bar test'
		);
		$this->assertEqual($results->to('array'), $expected);
		$this->assertNotEqual($results->to('array'), $collection->to('array'));
	}

	/**
	 * Tests `Collection::reduce`.
	 */
	public function testReduce() {
		$collection = new MockCollection();
		$collection->set(array(
			'title' => 'Lorem Ipsum',
			'key'   => 'value',
			'foo'   => 'bar'
		));
		$result = $collection->reduce(function($memo, $value) {
			return trim($memo . ' ' . $value);
		}, '');
		$expected = 'Lorem Ipsum value bar';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests `Collection::data`.
	 */
	public function testData() {
		$data = array(
			'Lorem Ipsum',
			'value',
			'bar'
		);
		$collection = new MockCollection(array('data' => $data));
		$this->assertEqual($data, $collection->data());
	}

	/**
	 * Tests the sort method in `lithium\data\Collection`.
	 */
	public function testSort() {
		$collection = new MockCollection(array('data' => array(
			array('id' => 1, 'name' => 'Annie'),
			array('id' => 2, 'name' => 'Zilean'),
			array('id' => 3, 'name' => 'Trynamere'),
			array('id' => 4, 'name' => 'Katarina'),
			array('id' => 5, 'name' => 'Nunu')
		)));

		$collection->sort('name');
		$idsSorted = $collection->map(function ($v) { return $v['id']; })->to('array');
		$this->assertEqual($idsSorted, array(1, 4, 5, 3, 2));
	}

	/**
	 * Tests that arrays can be used to filter objects in `find()` and `first()` methods.
	 */
	public function testArrayFiltering() {
		$collection = new MockCollection(array('data' => array(
			new Entity(array('data' => array('id' => 1, 'name' => 'Annie', 'active' => 1))),
			new Entity(array('data' => array('id' => 2, 'name' => 'Zilean', 'active' => 1))),
			new Entity(array('data' => array('id' => 3, 'name' => 'Trynamere', 'active' => 0))),
			new Entity(array('data' => array('id' => 4, 'name' => 'Katarina', 'active' => 1))),
			new Entity(array('data' => array('id' => 5, 'name' => 'Nunu', 'active' => 0)))
		)));
		$result = $collection->find(array('active' => 1))->data();
		$expected = array(
			0 => array('id' => 1, 'name' => 'Annie', 'active' => 1),
			1 => array('id' => 2, 'name' => 'Zilean', 'active' => 1),
			3 => array('id' => 4, 'name' => 'Katarina', 'active' => 1)
		);
		$this->assertEqual($expected, $result);

		$result = $collection->first(array('active' => 1))->data();
		$expected = array('id' => 1, 'name' => 'Annie', 'active' => 1);
		$this->assertEqual($expected, $result);

		$result = $collection->first(array('name' => 'Nunu'))->data();
		$expected = array('id' => 5, 'name' => 'Nunu', 'active' => 0);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests `Collection::closed` && `Collection::close`.
	 */
	public function testClosed() {
		$collection = new MockCollection();
		$this->assertTrue($collection->closed());

		$collection = new MockCollection(array('result' => 'foo'));
		$this->assertFalse($collection->closed());
		$collection->close();
		$this->assertTrue($collection->closed());
	}

	/**
	 * Tests `Collection::assignTo`.
	 */
	public function testAssignTo() {
		$parent = new stdClass();
		$config = array('valid' => false, 'model' => $this->_model);
		$collection = new MockCollection();
		$collection->assignTo($parent, $config);
		$this->assertEqual($this->_model, $collection->model());
		$this->assertEqual($parent, $collection->parent());
	}

	public function testHandlers() {
		$handlers = array(
			'stdClass' => function($value) { return substr($value->scalar, -1); }
		);
		$array = new MockCollection(compact('handlers') + array(
			'data' => array(
				array(
					'value' => (object) 'hello'
				),
				array(
					'value' => (object) 'world'
				)
			)
		));

		$expected = array(array('value' => 'o'), array('value' => 'd'));
		$this->assertIdentical($expected, $array->to('array', array('indexed' => false)));
	}
}

?>