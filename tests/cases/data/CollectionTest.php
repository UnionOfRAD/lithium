<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use stdClass;
use lithium\data\collection\DocumentSet;
use lithium\data\Connections;

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
	 * Used for storing connections in CollectionTest::setUp,
	 * restored in Collection::tearDown.
	 *
	 * @var array
	 */
	protected $_backup = array();

	/**
	 * Setup method run before every test method.
	 */
	public function setUp() {
		if (empty($this->_backup)) {
			foreach (Connections::get() as $conn) {
				$this->_backup[$conn] = Connections::get($conn, array('config' => true));
			}
		}
		Connections::reset();
	}

	/**
	 * Teardown method run after every test method.
	 */
	public function tearDown() {
		Connections::reset();
		foreach ($this->_backup as $name => $config) {
			Connections::add($name, $config);
		}
	}

	/**
	 * Tests `Collection::stats`.
	 */
	public function testGetStats() {
		$collection = new DocumentSet(array('stats' => array('foo' => 'bar')));
		$this->assertNull($collection->stats('bar'));
		$this->assertEqual('bar', $collection->stats('foo'));
		$this->assertEqual(array('foo' => 'bar'), $collection->stats());
	}

	/**
	 * Tests Collection accessors (getters/setters).
	 */
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

	/**
	 * Tests `Collection::offsetExists`.
	 */
	public function testOffsetExists() {
		$collection = new DocumentSet();
		$this->assertEqual($collection->offsetExists(0), false);
		$collection->set(array('foo' => 'bar', 'bas' => 'baz'));
		$this->assertEqual($collection->offsetExists(0), true);
		$this->assertEqual($collection->offsetExists(1), true);
	}

	/**
	 * Tests `Collection::rewind` and `Collection::current`.
	 */
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

	/**
	 * Tests `Collection::each`.
	 */
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
		$this->assertEqual($expected, $collection->to('array'));
	}

	/**
	 * Tests `Collection::map`.
	 */
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

	/**
	 * Tests `Collection::reduce`.
	 */
	public function testReduce() {
		$collection = new DocumentSet();
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
		$collection = new DocumentSet();
		$data = array(
			'Lorem Ipsum',
			'value',
			'bar'
		);
		$collection->set($data);
		$this->assertEqual($data, $collection->data());
	}

	/**
	 * Tests `Collection::sort`.
	 */
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

	/**
	 * Tests `Collection::closed` && `Collection::close`.
	 */
	public function testClosed() {
		$collection = new DocumentSet();
		$this->assertTrue($collection->closed());

		$collection = new DocumentSet(array('result' => 'foo'));
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
		$collection = new DocumentSet;
		$collection->assignTo($parent, $config);
		$this->assertEqual($this->_model, $collection->model());
		$this->assertEqual($parent, $collection->parent());
	}
}

?>