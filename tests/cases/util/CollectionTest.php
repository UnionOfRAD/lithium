<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\util;

use stdClass;
use lithium\util\Collection;
use lithium\tests\mocks\util\MockCollectionMarker;
use lithium\tests\mocks\util\MockCollectionObject;
use lithium\tests\mocks\util\MockCollectionStringCast;

class CollectionTest extends \lithium\test\Unit {

	public function setUp() {
		Collection::formats('\lithium\net\http\Media');
	}

	public function testArrayLike() {
		$collection = new Collection();
		$collection[] = 'foo';
		$this->assertEqual($collection[0], 'foo');
		$this->assertEqual(count($collection), 1);

		$collection = new Collection(array('data' => array('foo')));
		$this->assertEqual($collection[0], 'foo');
		$this->assertEqual(count($collection), 1);
	}

	public function testObjectMethodDispatch() {
		$collection = new Collection();

		for ($i = 0; $i < 10; $i++) {
			$collection[] = new MockCollectionMarker();
		}
		$result = $collection->mark();
		$this->assertEqual($result, array_fill(0, 10, true));

		$result = $collection->mapArray();
		$this->assertEqual($result, array_fill(0, 10, array('foo')));

		$result = $collection->invoke('mapArray', array(), array('merge' => true));
		$this->assertEqual($result, array_fill(0, 10, 'foo'));

		$collection = new Collection(array(
			'data' => array_fill(0, 10, new MockCollectionObject())
		));
		$result = $collection->testFoo();
		$this->assertEqual($result, array_fill(0, 10, 'testFoo'));

		$result = $collection->invoke('testFoo', array(), array('collect' => true));
		$this->assertTrue($result instanceof Collection);
		$this->assertEqual($result->to('array'), array_fill(0, 10, 'testFoo'));
	}

	public function testObjectCasting() {
		$collection = new Collection(array(
			'data' => array_fill(0, 10, new MockCollectionObject())
		));
		$result = $collection->to('array');
		$expected = array_fill(0, 10, array(1 => 2, 2 => 3));
		$this->assertEqual($expected, $result);

		$collection = new Collection(array(
			'data' => array_fill(0, 10, new MockCollectionMarker())
		));
		$result = $collection->to('array');
		$expected = array_fill(0, 10, array('marker' => false, 'data' => 'foo'));
		$this->assertEqual($expected, $result);

		$collection = new Collection(array(
			'data' => array_fill(0, 10, new MockCollectionStringCast())
		));
		$result = $collection->to('array');
		$expected = array_fill(0, 10, json_encode(array(1 => 2, 2 => 3)));
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that the `find()` method properly filters items out of the resulting collection.
	 *
	 * @return void
	 */
	public function testCollectionFindFilter() {
		$collection = new Collection(array('data' => array_merge(
			array_fill(0, 10, 1),
			array_fill(0, 10, 2)
		)));
		$this->assertEqual(20, count($collection->to('array')));

		$filter = function($item) { return $item == 1; };
		$result = $collection->find($filter);
		$this->assertTrue($result instanceof Collection);
		$this->assertEqual(array_fill(0, 10, 1), $result->to('array'));

		$result = $collection->find($filter, array('collect' => false));
		$this->assertEqual(array_fill(0, 10, 1), $result);
	}

	/**
	 * Tests that the `first()` method properly returns the first non-empty value.
	 *
	 * @return void
	 */
	public function testCollectionFirstFilter() {
		$collection = new Collection(array('data' => array(0, 1, 2)));
		$result = $collection->first(function($value) { return $value; });
		$this->assertEqual(1, $result);

		$collection = new Collection(array('data' => array('Hello', '', 'Goodbye')));
		$result = $collection->first(function($value) { return $value; });
		$this->assertEqual('Hello', $result);

		$collection = new Collection(array('data' => array('', 'Hello', 'Goodbye')));
		$result = $collection->first(function($value) { return $value; });
		$this->assertEqual('Hello', $result);

		$collection = new Collection(array('data' => array('', 'Hello', 'Goodbye')));
		$result = $collection->first();
		$this->assertEqual('', $result);
	}

	/**
	 * Tests that the `each()` filter applies the callback to each item in the current collection,
	 * returning an instance of itself.
	 *
	 * @return void
	 */
	public function testCollectionEachFilter() {
		$collection = new Collection(array('data' => array(1, 2, 3, 4, 5)));
		$filter = function($item) { return ++$item; };
		$result = $collection->each($filter);

		$this->assertIdentical($collection, $result);
		$this->assertEqual(array(2, 3, 4, 5, 6), $collection->to('array'));
	}

	public function testCollectionMapFilter() {
		$collection = new Collection(array('data' => array(1, 2, 3, 4, 5)));
		$filter = function($item) { return ++$item; };
		$result = $collection->map($filter);

		$this->assertNotEqual($collection, $result);
		$this->assertEqual(array(1, 2, 3, 4, 5), $collection->to('array'));
		$this->assertEqual(array(2, 3, 4, 5, 6), $result->to('array'));

		$result = $collection->map($filter, array('collect' => false));
		$this->assertEqual(array(2, 3, 4, 5, 6), $result);
	}

	/**
	 * Tests the `ArrayAccess` interface implementation for manipulating values by direct offsets.
	 *
	 * @return void
	 */
	public function testArrayAccessOffsetMethods() {
		$collection = new Collection(array('data' => array('foo', 'bar', 'baz' => 'dib')));
		$this->assertTrue($collection->offsetExists(0));
		$this->assertTrue($collection->offsetExists(1));
		$this->assertTrue($collection->offsetExists('0'));
		$this->assertTrue($collection->offsetExists('baz'));

		$this->assertFalse($collection->offsetExists('2'));
		$this->assertFalse($collection->offsetExists('bar'));
		$this->assertFalse($collection->offsetExists(2));

		$this->assertEqual('foo', $collection->offsetSet('bar', 'foo'));
		$this->assertTrue($collection->offsetExists('bar'));

		$this->assertNull($collection->offsetUnset('bar'));
		$this->assertFalse($collection->offsetExists('bar'));
	}

	/**
	 * Tests the `ArrayAccess` interface implementation for traversing values.
	 *
	 * @return void
	 */
	public function testArrayAccessTraversalMethods() {
		$collection = new Collection(array('data' => array('foo', 'bar', 'baz' => 'dib')));
		$this->assertEqual('foo', $collection->current());
		$this->assertEqual('bar', $collection->next());
		$this->assertEqual('foo', $collection->prev());
		$this->assertEqual('bar', $collection->next());
		$this->assertEqual('dib', $collection->next());
		$this->assertEqual('baz', $collection->key());
		$this->assertTrue($collection->valid());
		$this->assertFalse($collection->next());
		$this->assertFalse($collection->valid());
		$this->assertEqual('foo', $collection->rewind());
		$this->assertTrue($collection->valid());
		$this->assertEqual('dib', $collection->prev());
		$this->assertTrue($collection->valid());
		$this->assertEqual('bar', $collection->prev());
		$this->assertTrue($collection->valid());
		$this->assertEqual('dib', $collection->end());
		$this->assertTrue($collection->valid());
	}

	/**
	 * Tests objects and scalar values being appended to the collection.
	 *
	 * @return void
	 */
	public function testValueAppend() {
		$collection = new Collection();
		$this->assertFalse($collection->valid());
		$this->assertEqual(0, count($collection));

		$collection->append(1);
		$this->assertEqual(1, count($collection));
		$collection->append(new stdClass());
		$this->assertEqual(2, count($collection));

		$this->assertEqual(1, $collection->current());
		$this->assertEqual(new stdClass(), $collection->next());
	}

	/**
	 * Tests getting the index of the internal array.
	 *
	 * @return void
	 */
	public function testInternalKeys() {
		$collection = new Collection(array('data' => array('foo', 'bar', 'baz' => 'dib')));
		$this->assertEqual(array(0, 1, 'baz'), $collection->keys());
	}

	/**
	 * Tests that various types of handlers can be registered with `Collection::formats()`, and
	 * that collection instances are converted correctly.
	 *
	 * @return void
	 */
	public function testCollectionFormatConversion() {
		Collection::formats('lithium\net\http\Media');
		$data = array('hello', 'goodbye', 'foo' => array('bar', 'baz' => 'dib'));
		$collection = new Collection(compact('data'));

		$expected = json_encode($data);
		$result = $collection->to('json');
		$this->assertEqual($expected, $result);

		$this->assertNull($collection->to('badness'));

		Collection::formats(false);
		$this->assertNull($collection->to('json'));

		Collection::formats('json', function($collection, $options) {
			return json_encode($collection->to('array'));
		});
		$result = $collection->to('json');
		$this->assertEqual($expected, $result);

		$result = $collection->to(function($collection) {
			$value = array_map(
				function($i) { return is_array($i) ? join(',', $i) : $i; }, $collection->to('array')
			);
			return join(',', $value);
		});
		$expected = 'hello,goodbye,bar,dib';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that the Collection::sort method works appropriately.
	 *
	 * @return void
	 */
	public function testCollectionSort() {
		// Typical numeric sort using the default "sort" PHP function
		$collection = new Collection(array('data' => array(5,3,4,1,2)));
		$collection->sort();
		$expected = array(1,2,3,4,5);
		$this->assertEqual($expected, $collection->to('array'));

		// String sort using "sort"
		$collection = new Collection(array('data' => array('alan','dave','betsy','carl')));
		$collection->sort();
		$expected = array('alan','betsy','carl','dave');
		$this->assertEqual($expected, $collection->to('array'));

		// String sort using strcasecmp
		$collection = new Collection(array('data' => array('Alan','Dave','betsy','carl')));
		$collection->sort('strcasecmp');
		$expected = array('Alan','betsy','carl','Dave');
		$this->assertEqual($expected, $collection->to('array'));

		// Numeric sort using custom function
		$collection = new Collection(array('data' => array(5,3,4,1,2)));
		$collection->sort(function ($a,$b) {
			if ($a == $b) {
				return 0;
			}
			return ($b > $a ? 1 : -1);
		});
		$expected = array(5,4,3,2,1);
		$this->assertEqual($expected, $collection->to('array'));

		// Test fail
		$collection = new Collection(array('data' => array(5,3,4,1,2)));
		$result = $collection->sort('blahgah');
		$this->assertEqual($collection->to('array'), $result->to('array'));
	}

	public function testUnsetInForeach() {
		$data = array(
			'Hello',
			'Delete me',
			'Delete me',
			'Delete me',
			'Delete me',
			'Delete me',
			'Hello again!',
			'Delete me'
		);
		$collection = new Collection(array('data' => $data));
		
		$this->assertIdentical($data, $collection->to('array'));
		
		foreach ($collection as $i => $word) {
			if ($word == 'Delete me') {
				unset($collection[$i]);
			}
		}
	
		$expected = array(0 => 'Hello', 6 => 'Hello again!');
		$results = $collection->to('array');
		$this->assertIdentical($expected, $results);
	}
}

?>