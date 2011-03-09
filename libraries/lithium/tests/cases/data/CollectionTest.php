<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */

namespace lithium\tests\cases\data;

use lithium\data\collection\DocumentSet;

class CollectionTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\model\MockQueryPost';

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
		$model = $this->_model;
		$model::config(array('connection' => false));
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
}

?>