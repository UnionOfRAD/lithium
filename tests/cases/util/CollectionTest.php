<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\util;

use stdClass;
use lithium\data\Entity;
use lithium\util\Collection;
use lithium\tests\mocks\util\MockCollectionMarker;
use lithium\tests\mocks\util\MockCollectionObject;
use lithium\tests\mocks\util\MockCollectionStringCast;

class CollectionTest extends \lithium\test\Unit {

	public function setUp() {
		Collection::formats('lithium\net\http\Media');
	}

	public function tearDown() {
		Collection::formats(false);
	}

	public function testArrayLike() {
		$collection = new Collection();
		$collection[] = 'foo';
		$this->assertEqual($collection[0], 'foo');
		$this->assertEqual(count($collection), 1);

		$collection = new Collection(['data' => ['foo']]);
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
		$this->assertEqual($result, array_fill(0, 10, ['foo']));

		$result = $collection->invoke('mapArray', [], ['merge' => true]);
		$this->assertEqual($result, array_fill(0, 10, 'foo'));

		$collection = new Collection([
			'data' => array_fill(0, 10, new MockCollectionObject())
		]);
		$result = $collection->testFoo();
		$this->assertEqual($result, array_fill(0, 10, 'testFoo'));

		$result = $collection->invoke('testFoo', [], ['collect' => true]);
		$this->assertInstanceOf('lithium\util\Collection', $result);
		$this->assertEqual($result->to('array'), array_fill(0, 10, 'testFoo'));
	}

	public function testObjectCasting() {
		$collection = new Collection([
			'data' => array_fill(0, 10, new MockCollectionObject())
		]);
		$result = $collection->to('array');
		$expected = array_fill(0, 10, [1 => 2, 2 => 3]);
		$this->assertEqual($expected, $result);

		$collection = new Collection([
			'data' => array_fill(0, 10, new MockCollectionMarker())
		]);
		$result = $collection->to('array');
		$expected = array_fill(0, 10, ['marker' => false, 'data' => 'foo']);
		$this->assertEqual($expected, $result);

		$collection = new Collection([
			'data' => array_fill(0, 10, new MockCollectionStringCast())
		]);
		$result = $collection->to('array');
		$expected = array_fill(0, 10, json_encode([1 => 2, 2 => 3]));
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that the `find()` method properly filters items out of the resulting collection.
	 */
	public function testCollectionFindFilter() {
		$collection = new Collection(['data' => array_merge(
			array_fill(0, 10, 1),
			array_fill(0, 10, 2)
		)]);
		$this->assertCount(20, $collection->to('array'));

		$filter = function($item) { return $item === 1; };
		$result = $collection->find($filter);
		$this->assertInstanceOf('lithium\util\Collection', $result);
		$this->assertEqual(array_fill(0, 10, 1), $result->to('array'));

		$result = $collection->find($filter, ['collect' => false]);
		$this->assertEqual(array_fill(0, 10, 1), $result);
	}

	/**
	 * Tests that the `first()` method properly returns the first non-empty value.
	 */
	public function testCollectionFirstFilter() {
		$collection = new Collection(['data' => [0, 1, 2]]);
		$result = $collection->first(function($value) { return $value; });
		$this->assertEqual(1, $result);

		$collection = new Collection(['data' => ['Hello', '', 'Goodbye']]);
		$result = $collection->first(function($value) { return $value; });
		$this->assertEqual('Hello', $result);

		$collection = new Collection(['data' => ['', 'Hello', 'Goodbye']]);
		$result = $collection->first(function($value) { return $value; });
		$this->assertEqual('Hello', $result);

		$collection = new Collection(['data' => ['', 'Hello', 'Goodbye']]);
		$result = $collection->first();
		$this->assertEqual('', $result);
	}

	/**
	 * Tests that the `each()` filter applies the callback to each item in the current collection,
	 * returning an instance of itself.
	 */
	public function testCollectionEachFilter() {
		$collection = new Collection(['data' => [1, 2, 3, 4, 5]]);
		$filter = function($item) { return ++$item; };
		$result = $collection->each($filter);

		$this->assertIdentical($collection, $result);
		$this->assertEqual([2, 3, 4, 5, 6], $collection->to('array'));
	}

	public function testCollectionMapFilter() {
		$collection = new Collection(['data' => [1, 2, 3, 4, 5]]);
		$filter = function($item) { return ++$item; };
		$result = $collection->map($filter);

		$this->assertNotEqual($collection, $result);
		$this->assertEqual([1, 2, 3, 4, 5], $collection->to('array'));
		$this->assertEqual([2, 3, 4, 5, 6], $result->to('array'));

		$result = $collection->map($filter, ['collect' => false]);
		$this->assertEqual([2, 3, 4, 5, 6], $result);
	}

	public function testCollectionReduceFilter() {
		$collection = new Collection(['data' => [1, 2, 3]]);
		$filter = function($memo, $item) { return $memo + $item; };
		$result = $collection->reduce($filter, 0);

		$this->assertEqual(6, $collection->reduce($filter, 0));
		$this->assertEqual(7, $collection->reduce($filter, 1));
	}

	/**
	 * Tests the `ArrayAccess` interface implementation for manipulating values by direct offsets.
	 */
	public function testArrayAccessOffsetMethods() {
		$collection = new Collection(['data' => ['foo', 'bar', 'baz' => 'dib']]);
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

		$data = ['Hello', 2, 3, null, 6, false, true, 0];
		$collection = new Collection(['data' => $data]);

		$cpt = 0;
		foreach ($collection as $i => $word) {
			$this->assertTrue(isset($collection[$cpt]));
			$cpt++;
		}
		$this->assertIdentical(8, $cpt);
	}

	public function testTraversal() {
		$collection = new Collection(['data' => ['foo', 'bar', 'baz' => 'dib']]);
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

		$collection = new Collection(['data' => [0, 1, 2, 3, 4]]);
		$this->assertIdentical(0, $collection->first());
		$this->assertIdentical(0, $collection->rewind());
		$this->assertIdentical(1, $collection->next());
		$this->assertIdentical(2, $collection->next());
		$this->assertIdentical(3, $collection->next());
		$this->assertIdentical(2, $collection->prev());
		$this->assertIdentical(2, $collection->current());
		$this->assertIdentical(3, $collection->next());
		$this->assertIdentical(4, $collection->next());
		$this->assertIdentical(3, $collection->prev());
		$this->assertIdentical(4, $collection->next());
		$this->assertTrue($collection->valid());
		$this->assertFalse($collection->next());
		$this->assertFalse($collection->valid());
		$this->assertFalse($collection->current());
		$this->assertIdentical(4, $collection->prev());
		$this->assertTrue($collection->valid());
	}

	public function testTraverseEmptyHomogeneousReturnValues() {
		$collection = new Collection(['data' => []]);

		$this->assertFalse($collection->next());
		$this->assertFalse($collection->prev());
		$this->assertFalse($collection->current());
		$this->assertFalse($collection->end());
		$this->assertFalse($collection->rewind());
		$this->assertFalse($collection->current());
	}

	public function testNext() {
		$collection = new Collection(['data' => [1, 2]]);
		$this->assertIdentical(2, $collection->next());
		$this->assertIdentical(false, $collection->next());
	}

	public function testNextOverFalsey() {
		$collection = new Collection(['data' => [1, '', 3]]);
		$this->assertIdentical('', $collection->next());
		$this->assertIdentical(3, $collection->next());
	}

	public function testPrev() {
		$collection = new Collection(['data' => [1, 2]]);

		$collection->end();
		$this->assertIdentical(1, $collection->prev());
	}

	public function testPrevOverFalsey() {
		$collection = new Collection(['data' => [1, '', 3]]);

		$collection->end();
		$this->assertIdentical('', $collection->prev());
		$this->assertIdentical(1, $collection->prev());
	}

	public function testPrevWraps() {
		$collection = new Collection(['data' => [1, 2]]);

		$collection->end();
		$this->assertIdentical(1, $collection->prev());
		$this->assertIdentical(2, $collection->prev());
	}

	/**
	 * Tests objects and scalar values being appended to the collection.
	 */
	public function testValueAppend() {
		$collection = new Collection();
		$this->assertFalse($collection->valid());
		$this->assertCount(0, $collection);

		$collection->append(1);
		$this->assertCount(1, $collection);
		$collection->append(new stdClass());
		$this->assertCount(2, $collection);

		$this->assertEqual(1, $collection->current());
		$this->assertEqual(new stdClass(), $collection->next());
	}

	/**
	 * Tests getting the index of the internal array.
	 */
	public function testInternalKeys() {
		$collection = new Collection(['data' => ['foo', 'bar', 'baz' => 'dib']]);
		$this->assertEqual([0, 1, 'baz'], $collection->keys());
	}

	/**
	 * Tests that various types of handlers can be registered with `Collection::formats()`, and
	 * that collection instances are converted correctly.
	 */
	public function testCollectionFormatConversion() {
		Collection::formats('lithium\net\http\Media');
		$data = ['hello', 'goodbye', 'foo' => ['bar', 'baz' => 'dib']];
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

	public function testCollectionHandlers() {
		$obj = new stdClass();
		$obj->a = "b";
		$handlers = ['stdClass' => function($v) { return (array) $v; }];
		$data = ['test' => new Collection(['data' => compact('obj')])] + compact('obj');

		$collection = new Collection(compact('data'));
		$expected = [
			'test' => ['obj' => ['a' => 'b']],
			'obj' => ['a' => 'b']
		];
		$this->assertIdentical($expected, $collection->to('array', compact('handlers')));

		$handlers = ['stdClass' => function($v) { return $v; }];
		$expected = ['test' => compact('obj')] + compact('obj');
		$this->assertIdentical($expected, $collection->to('array', compact('handlers')));
	}

	/**
	 * Tests that the Collection::sort method works appropriately.
	 */
	public function testCollectionSort() {

		$collection = new Collection(['data' => [5,3,4,1,2]]);
		$collection->sort();
		$expected = [1,2,3,4,5];
		$this->assertEqual($expected, $collection->to('array'));

		$collection = new Collection(['data' => ['alan', 'dave', 'betsy', 'carl']]);
		$expected = ['alan','betsy','carl','dave'];
		$this->assertEqual($expected, $collection->sort()->to('array'));

		$collection = new Collection(['data' => ['Alan', 'Dave', 'betsy', 'carl']]);
		$expected = ['Alan', 'betsy', 'carl', 'Dave'];
		$this->assertEqual($expected, $collection->sort('strcasecmp')->to('array'));

		$collection = new Collection(['data' => [5,3,4,1,2]]);
		$collection->sort(function ($a,$b) {
			if ($a === $b) {
				return 0;
			}
			return ($b > $a ? 1 : -1);
		});
		$expected = [5,4,3,2,1];
		$this->assertEqual($expected, $collection->to('array'));

		$collection = new Collection(['data' => [5,3,4,1,2]]);
		$result = $collection->sort('blahgah');
		$this->assertEqual($collection->to('array'), $result->to('array'));
	}

	public function testUnsetInForeach() {
		$data = ['Delete me'];
		$collection = new Collection(['data' => $data]);

		$this->assertIdentical($data, $collection->to('array'));

		$cpt = 0;
		foreach ($collection as $i => $word) {
			if ($word === 'Delete me') {
				unset($collection[$i]);
			}
			$cpt++;
		}
		$this->assertEqual(1, $cpt);
		$this->assertIdentical([], $collection->to('array'));

		$data = [
			'Hello',
			'Delete me',
			'Delete me',
			'Delete me',
			'Delete me',
			'Delete me',
			'Hello again!',
			'Delete me'
		];
		$collection = new Collection(['data' => $data]);

		$this->assertIdentical($data, $collection->to('array'));

		foreach ($collection as $i => $word) {
			if ($word === 'Delete me') {
				unset($collection[$i]);
			}
		}
		$expected = [0 => 'Hello', 6 => 'Hello again!'];
		$results = $collection->to('array');
		$this->assertIdentical($expected, $results);

		$data = [
			'Delete me',
			'Hello',
			'Delete me',
			'Delete me',
			'Delete me',
			'Delete me',
			'Hello again!',
			'Delete me'
		];
		$collection = new Collection(['data' => $data]);

		$this->assertIdentical($data, $collection->to('array'));

		foreach ($collection as $i => $word) {
			if ($word === 'Delete me') {
				unset($collection[$i]);
			}
		}

		$expected = [1 => 'Hello', 6 => 'Hello again!'];
		$results = $collection->to('array');
		$this->assertIdentical($expected, $results);
	}

	public function testCount() {
		$collection = new Collection(['data' => [5, 3, 4, 1, 2]]);
		$this->assertIdentical(5, count($collection));

		$collection = new Collection(['data' => []]);
		$this->assertIdentical(0, count($collection));

		$collection = new Collection(['data' => [5 ,null, 4, true, false, 'bob']]);
		$this->assertIdentical(6, count($collection));

		unset($collection[1]);
		unset($collection[2]);

		$this->assertIdentical(4, count($collection));

		$first  = (object) ['name' => 'First'];
		$second = (object) ['name' => 'Second'];
		$third  = (object) ['name' => 'Third'];

		$doc = new Collection([
			'data' => [$first, $second, $third]
		]);

		$this->assertInternalType('object', $doc[0]);
		$this->assertInternalType('object', $doc[1]);
		$this->assertInternalType('object', $doc[2]);
		$this->assertCount(3, $doc);
	}

	public function testValid() {
		$collection = new Collection();
		$this->assertFalse($collection->valid());

		$collection = new Collection(['data' => [1, 5]]);
		$this->assertTrue($collection->valid());
	}

	public function testRespondsToParent() {
		$collection = new Collection();
		$this->assertTrue($collection->respondsTo('invokeMethod'));
		$this->assertFalse($collection->respondsTo('fooBarBaz'));
	}

	public function testRespondsToMagic() {
		$collection = new Collection([
			'data' => [
				new Entity([
					'model' => 'lithium\tests\mocks\data\MockPost',
					'data' => ['stats' => ['foo' => 'bar']],
				])
			]
		]);
		$this->assertTrue($collection->respondsTo('instances'));
		$this->assertTrue($collection->respondsTo('foobar'));
		$this->assertFalse($collection->respondsTo('foobarbaz'));
	}
}

?>