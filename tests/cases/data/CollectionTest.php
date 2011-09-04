<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use lithium\data\collection\DocumentSet;
use lithium\data\entity\Document;
use lithium\data\Connections;

class CollectionTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\MockPost';

	public function testGetStats() {
		$collection = new DocumentSet(array('stats' => array('foo' => 'bar')));
		$this->assertNull($collection->stats('bar'));
		$this->assertEqual('bar', $collection->stats('foo'));
		$this->assertEqual(array('foo' => 'bar'), $collection->stats());
	}

	public function testInvalidData() {
		$this->expectException('Error creating new Collection instance; data format invalid.');
		$collection = new DocumentSet(array('data' => 'foo'));
	}

	public function testAccessorMethods() {
		Connections::config(array('mock-source' => array(
			'type' => 'lithium\tests\mocks\data\MockSource'
		)));
		$model = $this->_model;
		$model::config(array('connection' => false, 'key' => 'id'));
		$collection = new DocumentSet(compact('model'));
		$this->assertEqual($model, $collection->model());
		$this->assertEqual(compact('model'), $collection->meta());
	}

	public function testOffsetExists() {
		$collection = new DocumentSet();
		$this->assertEqual($collection->offsetExists(0), false);
		$collection->set(array('foo' => 'bar', 'bas' => 'baz'));
		$this->assertEqual($collection->offsetExists(0), true);
		$this->assertEqual($collection->offsetExists(1), true);
	}

	public function testNextRewindCurrent() {
		$collection = new DocumentSet();
		$collection->set(array(
			'title' => 'Lorem Ipsum',
			'value' => 42,
			'foo'   => 'bar'
		));
		$this->assertEqual('Lorem Ipsum', $collection->current());
		$this->assertEqual(42, $collection->next());
		$this->assertEqual('bar', $collection->next());
		$this->assertEqual('Lorem Ipsum', $collection->rewind());
		$this->assertEqual(42, $collection->next());
	}

	public function testEach() {
		$collection = new DocumentSet();
		$collection->set(array(
			'title' => 'Lorem Ipsum',
			'key'   => 'value',
			'foo'   => 'bar'
		));
		$collection->each(function($value) {
			return $value . ' test';
		});
		$expected = array(
			'Lorem Ipsum test',
			'value test',
			'bar test'
		);
		$this->assertEqual($collection->to('array'), $expected);
	}

	public function testMap() {
		$collection = new DocumentSet();
		$collection->set(array(
			'title' => 'Lorem Ipsum',
			'key'   => 'value',
			'foo'   => 'bar'
		));
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

	public function testData() {
		$collection = new DocumentSet();
		$data = array(
			'Lorem Ipsum',
			'value',
			'bar'
		);
		$collection->set($data);
		$this->assertEqual($data, $collection->data());
	}
	
	// Tests the sort method in \lithium\data\Collection
	public function testSort() {
		$collection = new DocumentSet();
		$collection->set(array(
			array('id' => 1, 'name' => 'Annie'),
			array('id' => 2, 'name' => 'Zilean'),
			array('id' => 3, 'name' => 'Trynamere'),
			array('id' => 4, 'name' => 'Katarina'),
			array('id' => 5, 'name' => 'Nunu')
		));
		
		$collection->sort('name');
		
		$idsSorted = $collection->map(function ($v) { return $v['id']; })->to('array');
		$this->assertEqual($idsSorted, array(1,4,5,3,2));
	}
}

?>