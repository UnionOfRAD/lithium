<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
		$collection = new MockCollection(['stats' => ['foo' => 'bar']]);
		$this->assertNull($collection->stats('bar'));
		$this->assertEqual('bar', $collection->stats('foo'));
		$this->assertEqual(['foo' => 'bar'], $collection->stats());
	}

	/**
	 * Tests `Collection` accessors (getters/setters).
	 */
	public function testAccessorMethods() {
		$model = $this->_model;
		$model::config(['meta' => ['connection' => false, 'key' => 'id']]);
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

		$collection = new MockCollection(['data' => ['bar', 'baz', 'bob' => 'bill']]);
		$this->assertEqual($collection->offsetExists(0), true);
		$this->assertEqual($collection->offsetExists(1), true);
	}

	/**
	 * Tests `Collection::rewind` and `Collection::current`.
	 */
	public function testNextRewindCurrent() {
		$collection = new MockCollection(['data' => [
			'title' => 'Lorem Ipsum',
			'value' => 42,
			'foo'   => 'bar'
		]]);
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
		$collection = new MockCollection(['data' => [
			'Lorem Ipsum',
			'value',
			'bar'
		]]);
		$collection->each(function($value) {
			return $value . ' test';
		});
		$expected = [
			'Lorem Ipsum test',
			'value test',
			'bar test'
		];
		$this->assertEqual($expected, $collection->to('array'));
	}

	/**
	 * Tests `Collection::map`.
	 */
	public function testMap() {
		$collection = new MockCollection(['data' => [
			'Lorem Ipsum',
			'value',
			'bar'
		]]);
		$results = $collection->map(function($value) {
			return $value . ' test';
		});
		$expected = [
			'Lorem Ipsum test',
			'value test',
			'bar test'
		];
		$this->assertEqual($results->to('array'), $expected);
		$this->assertNotEqual($results->to('array'), $collection->to('array'));
	}

	/**
	 * Tests `Collection::reduce`.
	 */
	public function testReduce() {
		$collection = new MockCollection();
		$collection->set([
			'title' => 'Lorem Ipsum',
			'key'   => 'value',
			'foo'   => 'bar'
		]);
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
		$data = [
			'Lorem Ipsum',
			'value',
			'bar'
		];
		$collection = new MockCollection(['data' => $data]);
		$this->assertEqual($data, $collection->data());
	}

	/**
	 * Tests the sort method in `lithium\data\Collection`.
	 */
	public function testSort() {
		$collection = new MockCollection(['data' => [
			['id' => 1, 'name' => 'Annie'],
			['id' => 2, 'name' => 'Zilean'],
			['id' => 3, 'name' => 'Trynamere'],
			['id' => 4, 'name' => 'Katarina'],
			['id' => 5, 'name' => 'Nunu']
		]]);

		$collection->sort('name');
		$idsSorted = $collection->map(function ($v) { return $v['id']; })->to('array');
		$this->assertEqual($idsSorted, [1, 4, 5, 3, 2]);
	}

	/**
	 * Tests that arrays can be used to filter objects in `find()` and `first()` methods.
	 */
	public function testArrayFiltering() {
		$collection = new MockCollection(['data' => [
			new Entity(['data' => ['id' => 1, 'name' => 'Annie', 'active' => 1]]),
			new Entity(['data' => ['id' => 2, 'name' => 'Zilean', 'active' => 1]]),
			new Entity(['data' => ['id' => 3, 'name' => 'Trynamere', 'active' => 0]]),
			new Entity(['data' => ['id' => 4, 'name' => 'Katarina', 'active' => 1]]),
			new Entity(['data' => ['id' => 5, 'name' => 'Nunu', 'active' => 0]])
		]]);
		$result = $collection->find(['active' => 1])->data();
		$expected = [
			0 => ['id' => 1, 'name' => 'Annie', 'active' => 1],
			1 => ['id' => 2, 'name' => 'Zilean', 'active' => 1],
			3 => ['id' => 4, 'name' => 'Katarina', 'active' => 1]
		];
		$this->assertEqual($expected, $result);

		$result = $collection->first(['active' => 1])->data();
		$expected = ['id' => 1, 'name' => 'Annie', 'active' => 1];
		$this->assertEqual($expected, $result);

		$result = $collection->first(['name' => 'Nunu'])->data();
		$expected = ['id' => 5, 'name' => 'Nunu', 'active' => 0];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests `Collection::closed` && `Collection::close`.
	 */
	public function testClosed() {
		$collection = new MockCollection();
		$this->assertTrue($collection->closed());

		$collection = new MockCollection(['result' => 'foo']);
		$this->assertFalse($collection->closed());
		$collection->close();
		$this->assertTrue($collection->closed());
	}

	/**
	 * Tests `Collection::assignTo`.
	 */
	public function testAssignTo() {
		$parent = new stdClass();
		$config = ['valid' => false, 'model' => $this->_model];
		$collection = new MockCollection();
		$collection->assignTo($parent, $config);
		$this->assertEqual($this->_model, $collection->model());
		$this->assertEqual($parent, $collection->parent());
	}

	public function testHandlers() {
		$handlers = [
			'stdClass' => function($value) { return substr($value->scalar, -1); }
		];
		$array = new MockCollection(compact('handlers') + [
			'data' => [
				[
					'value' => (object) 'hello'
				],
				[
					'value' => (object) 'world'
				]
			]
		]);

		$expected = [['value' => 'o'], ['value' => 'd']];
		$this->assertIdentical($expected, $array->to('array', ['indexed' => false]));
	}
}

?>