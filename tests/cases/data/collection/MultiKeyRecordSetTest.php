<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\collection;

use lithium\data\collection\MultiKeyRecordSet;
use lithium\tests\mocks\data\collection\MockMultiKeyRecordSet;
use lithium\tests\mocks\data\model\database\MockResult;
use lithium\tests\mocks\data\MockPostObject;
use lithium\util\Collection;

/**
 * RecordSet tests
 */
class MultiKeyRecordSetTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\MockModel';
	protected $_model2 = 'lithium\tests\mocks\data\MockModelCompositePk';

	/**
	 * RecordSet object to test
	 *
	 * @var object
	 */
	protected $_recordSet = null;

	/**
	 * Object based RecordSet object to test
	 *
	 * @var object
	 */
	protected $_objectRecordSet = null;

	/**
	 * Array of records for testing
	 *
	 * @var array
	 */
	protected $_records = [
		['id' => 1, 'data' => 'data1'],
		['id' => 2, 'data' => 'data2'],
		['id' => 3, 'data' => 'data3'],
		['id' => 4, 'data' => 'data4']
	];

	/**
	 * Array of object records for testing
	 *
	 * @var array
	 */
	protected $_objectRecords = [];

	public function setUp() {
		$result = new MockResult(['records' => $this->_records]);

		$model = $this->_model;

		$this->_recordSet = new MockMultiKeyRecordSet(compact('result', 'model') + [
			'exists' => true
		]);

		$result = new MockResult(['records' => $this->_records]);

		foreach ($this->_records as $i => $record) {
			$this->_objectRecords[$i] = new MockPostObject($record);
		}
		$this->_objectRecordSet = new MockMultiKeyRecordSet(compact('result', 'model') + [
			'exists' => true
		]);
	}

	public function tearDown() {
		Collection::formats(false);
	}

	public function testInit() {
		$recordSet = new MockMultiKeyRecordSet();
		$this->assertInstanceOf('lithium\data\collection\MultiKeyRecordSet', $recordSet);

		$recordSet = new MockMultiKeyRecordSet([
			'model'  => $this->_model,
			'result' => true,
			'exists' => true
		]);

		$this->assertEqual($this->_model, $recordSet->model());
		$this->assertTrue($recordSet->get('_result'));
	}

	public function testOffsetExists() {
		$this->assertFalse($this->_recordSet->offsetExists(0));
		$this->assertTrue($this->_recordSet->offsetExists(1));
		$this->assertTrue($this->_recordSet->offsetExists(2));
		$this->assertTrue($this->_recordSet->offsetExists(3));
		$this->assertTrue($this->_recordSet->offsetExists(4));

		$this->assertArrayHasKey(3, $this->_recordSet);

		$this->assertFalse($this->_objectRecordSet->offsetExists(0));
		$this->assertTrue($this->_objectRecordSet->offsetExists(1));
		$this->assertTrue($this->_objectRecordSet->offsetExists(2));
		$this->assertTrue($this->_objectRecordSet->offsetExists(3));
		$this->assertTrue($this->_objectRecordSet->offsetExists(4));
		$this->assertArrayHasKey(3, $this->_objectRecordSet);

		$data = [
			[
				'client_id' => 1,
				'invoice_id' => 4,
				'title' => 'Payment1'
			],
			[
				'client_id' => 2,
				'invoice_id' => 5,
				'title' => 'Payment2'
			],
			[
				'client_id' => 3,
				'invoice_id' => 6,
				'title' => 'Payment3'
			]
		];

		$payments = new MockMultiKeyRecordSet(['data' => $data, 'model' => $this->_model2]);
		$this->assertArrayHasKey(['client_id' => 1,'invoice_id' => 4], $payments);
		$this->assertArrayHasKey(['invoice_id' => 4, 'client_id' => 1], $payments);
		$this->assertArrayNotHasKey(0, $payments);
		$this->assertArrayNotHasKey(true, $payments);
		$this->assertArrayNotHasKey(false, $payments);
		$this->assertArrayNotHasKey(null, $payments);
		$this->assertArrayNotHasKey('string', $payments);

		$records = new MockMultiKeyRecordSet();
		$records[0] = ['title' => 'Record0'];
		$records[1] = ['title' => 'Record1'];
		$this->assertArrayHasKey(true, $records);
		$this->assertArrayHasKey(null, $records);
		$this->assertArrayHasKey(false, $records);
		$this->assertArrayHasKey([], $records);
		$this->assertArrayHasKey(0, $records);
		$this->assertArrayHasKey('0', $records);
		$this->assertArrayHasKey(1, $records);
		$this->assertArrayHasKey('1', $records);
		$this->assertArrayNotHasKey(2, $records);
	}

	public function testOffsetGet() {
		$expected = ['id' => 1, 'data' => 'data1'];
		$this->assertEqual($expected, $this->_recordSet[1]->to('array'));

		$expected = ['id' => 2, 'data' => 'data2'];
		$this->assertEqual($expected, $this->_recordSet[2]->to('array'));

		$expected = ['id' => 3, 'data' => 'data3'];
		$this->assertEqual($expected, $this->_recordSet[3]->to('array'));

		$expected = ['id' => 4, 'data' => 'data4'];
		$this->assertEqual($expected, $this->_recordSet[4]->to('array'));

		$expected = ['id' => 3, 'data' => 'data3'];
		$this->assertEqual($this->_records[2], $this->_recordSet[3]->to('array'));

		$recordSet = new MockMultiKeyRecordSet();
		$this->assertEqual([], $recordSet->data());

		$this->assertNull($this->_recordSet[5]);
	}

	public function testWithNoIndexes() {
		$records = [
			['data' => 'data1'],
			['data' => 'data2'],
			['data' => 'data3'],
			['data' => 'data4']
		];

		$result = new MockResult(['records' => $records]);

		$model = $this->_model;

		$recordSet = new MockMultiKeyRecordSet(compact('result', 'model'));

		$this->assertEqual($records, $recordSet->data());
		$this->assertEqual($records[1]['data'], $recordSet[1]->data);
	}

	public function testOffsetGetObject() {
		$result = $this->_objectRecordSet[1];
		$this->assertEqual(1, $result->id);
		$this->assertEqual('data1', $result->data);

		$result = $this->_objectRecordSet[2];
		$this->assertEqual(2, $result->id);
		$this->assertEqual('data2', $result->data);

		$result = $this->_objectRecordSet[3];
		$this->assertEqual(3, $result->id);
		$this->assertEqual('data3', $result->data);

		$result = $this->_objectRecordSet[4];
		$this->assertEqual(4, $result->id);
		$this->assertEqual('data4', $result->data);

		$this->assertNull($this->_objectRecordSet[5]);
	}

	public function testOffsetGetBackwards() {
		$expected = ['id' => 4, 'data' => 'data4'];
		$this->assertEqual($expected, $this->_recordSet[4]->to('array'));

		$expected = ['id' => 3, 'data' => 'data3'];
		$this->assertEqual($expected, $this->_recordSet[3]->to('array'));

		$expected = ['id' => 2, 'data' => 'data2'];
		$this->assertEqual($expected, $this->_recordSet[2]->to('array'));

		$expected = ['id' => 1, 'data' => 'data1'];
		$this->assertEqual($expected, $this->_recordSet[1]->to('array'));

		$result = $this->_objectRecordSet[4];
		$this->assertEqual(4, $result->id);
		$this->assertEqual('data4', $result->data);

		$result = $this->_objectRecordSet[3];
		$this->assertEqual(3, $result->id);
		$this->assertEqual('data3', $result->data);

		$result = $this->_objectRecordSet[2];
		$this->assertEqual(2, $result->id);
		$this->assertEqual('data2', $result->data);

		$result = $this->_objectRecordSet[1];
		$this->assertEqual(1, $result->id);
		$this->assertEqual('data1', $result->data);
	}

	public function testOffsetSet() {
		$this->assertCount(0, $this->_recordSet->get('_data'));
		$this->_recordSet[5] = $expected = ['id' => 5, 'data' => 'data5'];
		$this->assertEqual($expected, $this->_recordSet[5]->to('array'));
		$this->assertCount(5, $this->_recordSet->get('_data'));

		$this->_recordSet[] = $expected = ['id' => 6, 'data' => 'data6'];
		$this->assertEqual($expected, $this->_recordSet[6]->to('array'));
		$this->assertCount(6, $this->_recordSet->get('_data'));

		$this->_objectRecordSet[5] = $expected = new MockPostObject([
			'id' => 5, 'data' => 'data5'
		]);
		$item = $this->_objectRecordSet[5];
		$this->assertEqual($expected->id, $item->id);
		$this->assertEqual($expected->data, $item->data);

		$this->_objectRecordSet[] = $expected = new MockPostObject([
			'id' => 6, 'data' => 'data6 new'
		]);
		$item = $this->_objectRecordSet[6];
		$this->assertEqual($expected->id, $item->id);
		$this->assertEqual($expected->data, $item->data);

		$this->_objectRecordSet[] = $expected = new MockPostObject([
			'id' => 6, 'data' => 'data6 new2'
		]);
		$item = $this->_objectRecordSet[6];
		$this->assertEqual($expected->id, $item->id);
		$this->assertEqual($expected->data, $item->data);
	}

	public function testOffsetSetWithLoadedData() {
		$this->_recordSet[1] = ['id' => 1, 'data' => 'new data1'];

		$expected = [
			1 => ['id' => 1, 'data' => 'new data1'],
			2 => ['id' => 2, 'data' => 'data2'],
			3 => ['id' => 3, 'data' => 'data3'],
			4 => ['id' => 4, 'data' => 'data4']
		];
		$this->assertEqual($expected, $this->_recordSet->to('array'));

		$this->_objectRecordSet[1] = new MockPostObject(['id' => 1, 'data' => 'new data1']);

		$result = $this->_objectRecordSet[1];
		$this->assertEqual(1, $result->id);
		$this->assertEqual('new data1', $result->data);

		$result = $this->_objectRecordSet[2];
		$this->assertEqual(2, $result->id);
		$this->assertEqual('data2', $result->data);

		$result = $this->_objectRecordSet[3];
		$this->assertEqual(3, $result->id);
		$this->assertEqual('data3', $result->data);

		$result = $this->_objectRecordSet[4];
		$this->assertEqual(4, $result->id);
		$this->assertEqual('data4', $result->data);
	}

	public function testOffsetUnset() {
		unset($this->_recordSet[1]);

		$expected = [
			2 => ['id' => 2, 'data' => 'data2'],
			3 => ['id' => 3, 'data' => 'data3'],
			4 => ['id' => 4, 'data' => 'data4']
		];
		$this->assertEqual($expected, $this->_recordSet->to('array'));

		unset($this->_objectRecordSet[1]);

		$this->assertNull($this->_objectRecordSet[1]);

		$result = $this->_objectRecordSet[2];
		$this->assertEqual(2, $result->id);
		$this->assertEqual('data2', $result->data);

		$result = $this->_objectRecordSet[3];
		$this->assertEqual(3, $result->id);
		$this->assertEqual('data3', $result->data);

		$result = $this->_objectRecordSet[4];
		$this->assertEqual(4, $result->id);
		$this->assertEqual('data4', $result->data);

		$data = [
			[
				'client_id' => 1,
				'invoice_id' => 4,
				'title' => 'Payment1'
			],
			[
				'client_id' => 2,
				'invoice_id' => 5,
				'title' => 'Payment2'
			],
			[
				'client_id' => 3,
				'invoice_id' => 6,
				'title' => 'Payment3'
			]
		];

		$payments = new MockMultiKeyRecordSet(['data' => $data, 'model' => $this->_model2]);

		$expected = [
			[
				'client_id' => 2,
				'invoice_id' => 5,
				'title' => 'Payment2'
			],
			[
				'client_id' => 3,
				'invoice_id' => 6,
				'title' => 'Payment3'
			]
		];

		unset($payments[['client_id' => 1,'invoice_id' => 4]]);
		$this->assertEqual($expected, array_values($payments->data()));

		$payments = new MockMultiKeyRecordSet(['data' => $data, 'model' => $this->_model2]);
		unset($payments[['invoice_id' => 4, 'client_id' => 1]]);
		$this->assertEqual($expected, array_values($payments->data()));

		unset($payments[true]);
		$this->assertEqual($expected, array_values($payments->data()));

		unset($payments[false]);
		$this->assertEqual($expected, array_values($payments->data()));

		unset($payments[null]);
		$this->assertEqual($expected, array_values($payments->data()));

		unset($payments['string']);
		$this->assertEqual($expected, array_values($payments->data()));
	}

	public function testRewind() {
		$this->_recordSet->rewind();

		$expected = ['id' => 1, 'data' => 'data1'];
		$this->assertEqual($expected, $this->_recordSet->current()->to('array'));

		$this->_objectRecordSet->rewind();

		$result = $this->_objectRecordSet->current();
		$this->assertEqual(1, $result->id);
		$this->assertEqual('data1', $result->data);
	}

	public function testCurrent() {
		$this->assertEqual($this->_records[0], $this->_recordSet->current()->to('array'));
		$this->assertEqual($this->_records[1], $this->_recordSet->next()->to('array'));
		$this->assertEqual($this->_records[1], $this->_recordSet->current()->to('array'));

		$this->assertEqual($this->_records[0], $this->_recordSet->rewind()->to('array'));
		$this->assertEqual($this->_records[1], $this->_recordSet->next()->to('array'));
		$this->assertEqual($this->_records[1], $this->_recordSet->current()->to('array'));

		$this->assertEqual($this->_records[0], $this->_recordSet->rewind()->to('array'));
		$this->assertEqual($this->_records[0], $this->_recordSet->current()->to('array'));
		$this->assertEqual($this->_records[1], $this->_recordSet->next()->to('array'));

		$this->assertEqual($this->_records[0], $this->_recordSet->rewind()->to('array'));
		$this->assertEqual($this->_records[1], $this->_recordSet->next()->to('array'));
		$this->assertEqual($this->_records[2], $this->_recordSet->next()->to('array'));

		$result = $this->_objectRecordSet->current();
		$this->assertEqual($this->_objectRecordSet[1]->id, $result->id);
		$this->assertEqual($this->_objectRecordSet[1]->data, $result->data);
		$this->_objectRecordSet->next();
		$result = $this->_objectRecordSet->current();
		$this->assertEqual($this->_objectRecordSet[2]->id, $result->id);
		$this->assertEqual($this->_objectRecordSet[2]->data, $result->data);

		$result = $this->_objectRecordSet->rewind();
		$this->assertEqual($this->_objectRecordSet[1]->id, $result->id);
		$this->assertEqual($this->_objectRecordSet[1]->data, $result->data);
		$result = $this->_objectRecordSet->next();
		$this->assertEqual($this->_objectRecordSet[2]->id, $result->id);
		$this->assertEqual($this->_objectRecordSet[2]->data, $result->data);

		$this->_objectRecordSet->rewind();
		$result = $this->_objectRecordSet->current();
		$this->assertEqual($this->_objectRecordSet[1]->id, $result->id);
		$this->assertEqual($this->_objectRecordSet[1]->data, $result->data);
		$result = $this->_objectRecordSet->next();
		$this->assertEqual($this->_objectRecordSet[2]->id, $result->id);
		$this->assertEqual($this->_objectRecordSet[2]->data, $result->data);

		$this->_objectRecordSet->rewind();
		$result = $this->_objectRecordSet->next();
		$this->assertEqual($this->_objectRecordSet[2]->id, $result->id);
		$this->assertEqual($this->_objectRecordSet[2]->data, $result->data);
	}

	public function testKey() {
		$this->_recordSet->current();
		$this->assertEqual(1, $this->_recordSet->key());

		$this->_recordSet->next();
		$this->assertEqual(2, $this->_recordSet->key());
	}

	public function testNextWithForEach() {
		$counter = 0;
		foreach ($this->_recordSet as $record) {
			$this->assertEqual($this->_records[$counter], $record->to('array'));
			$counter++;
		}
		$this->assertEqual(4, $counter);

		$counter = 0;
		foreach ($this->_objectRecordSet as $record) {
			$this->assertEqual($this->_objectRecords[$counter]->id, $record->id);
			$this->assertEqual($this->_objectRecords[$counter]->data, $record->data);
			$counter++;
		}
		$this->assertEqual(4, $counter);
	}

	public function testNextWithWhile() {
		$counter = 0;
		while ($this->_recordSet->key() !== null) {
			$record = $this->_recordSet->current();
			$this->assertEqual($this->_records[$counter], $record->to('array'));
			$counter++;
			$this->_recordSet->next();
		}
		$this->assertEqual(4, $counter);

		$counter = 0;
		while ($this->_objectRecordSet->key() !== null) {
			$record = $this->_objectRecordSet->current();
			$this->assertEqual($this->_objectRecords[$counter]->id, $record->id);
			$this->assertEqual($this->_objectRecords[$counter]->data, $record->data);
			$counter++;
			$this->_objectRecordSet->next();
		}
		$this->assertEqual(4, $counter);
	}

	public function testMeta() {
		$expected = ['model' => 'lithium\tests\mocks\data\MockModel'];
		$this->assertEqual($expected, $this->_recordSet->meta());

		$expected = ['model' => 'lithium\tests\mocks\data\MockModel'];
		$this->assertEqual($expected, $this->_objectRecordSet->meta());
	}

	public function testTo() {
		Collection::formats('lithium\net\http\Media');
		$this->assertFalse(isset($this->_recordSet[0]));
		$expected = [
			1 => ['id' => 1, 'data' => 'data1'],
			2 => ['id' => 2, 'data' => 'data2'],
			3 => ['id' => 3, 'data' => 'data3'],
			4 => ['id' => 4, 'data' => 'data4']
		];
		$this->assertEqual($expected, $this->_recordSet->to('array'));

		$expected = '{"1":{"id":1,"data":"data1"},"2":{"id":2,"data":"data2"},';
		$expected .= '"3":{"id":3,"data":"data3"},"4":{"id":4,"data":"data4"}}';
		$this->assertEqual($expected, $this->_recordSet->to('json'));
	}

	public function testToInternal() {
		Collection::formats('lithium\net\http\Media');

		$expected = [
			['id' => 1, 'data' => 'data1'],
			['id' => 2, 'data' => 'data2'],
			['id' => 3, 'data' => 'data3'],
			['id' => 4, 'data' => 'data4']
		];
		$this->assertEqual($expected, $this->_recordSet->to('array', ['indexed' => false]));

		$expected = '{"1":{"id":1,"data":"data1"},"2":{"id":2,"data":"data2"},';
		$expected .= '"3":{"id":3,"data":"data3"},"4":{"id":4,"data":"data4"}}';
		$this->assertEqual($expected, $this->_recordSet->to('json'));

		$expected = '[{"id":1,"data":"data1"},{"id":2,"data":"data2"},';
		$expected .= '{"id":3,"data":"data3"},{"id":4,"data":"data4"}]';
		$result = $this->_recordSet->to('json', ['indexed' => false]);
		$this->assertEqual($expected, $result);
	}

	public function testRecordSetFindFilter() {
		$expected = [
			['id' => 1, 'data' => 'data1'],
			['id' => 2, 'data' => 'data2'],
			['id' => 3, 'data' => 'data3'],
			['id' => 4, 'data' => 'data4']
		];

		$records = $this->_recordSet->find(function($item) {
			return true;
		});
		$this->assertEqual($expected, $records->to('array'));
	}

	public function testEach() {
		$filter = function($rec) {
			$rec->more_data = "More Data{$rec->id}";
			return $rec;
		};
		$expected = [
			1 => ['id' => 1, 'data' => 'data1', 'more_data' => 'More Data1'],
			2 => ['id' => 2, 'data' => 'data2', 'more_data' => 'More Data2'],
			3 => ['id' => 3, 'data' => 'data3', 'more_data' => 'More Data3'],
			4 => ['id' => 4, 'data' => 'data4', 'more_data' => 'More Data4']
		];
		$result = $this->_recordSet->each($filter)->to('array');
		$this->assertEqual($expected, $result);

		$result = $this->_objectRecordSet->each($filter);
		foreach ($result as $key => $record) {
			$this->assertEqual($expected[$key]['id'], $record->id);
			$this->assertEqual($expected[$key]['data'], $record->data);
			$this->assertEqual($expected[$key]['more_data'], $record->more_data);
		}
	}

	public function testMap() {
		$filter = function($rec) {
			return $rec->id . $rec->data;
		};
		$expected = ['1data1', '2data2', '3data3', '4data4'];

		$result = $this->_recordSet->map($filter, ['collect' => false]);
		$this->assertEqual($expected, $result);

		$result = $this->_recordSet->map($filter);

		$this->assertEqual($expected, $result->get('_data'));

		$result = $this->_objectRecordSet->map($filter, ['collect' => false]);
		$this->assertEqual($expected, $result);

		$result = $this->_objectRecordSet->map($filter);
		$this->assertEqual($expected, $result->get('_data'));
	}

	public function testRecordSet() {
		$expected = [
			'post1' => [
				'title' => 'My First Post',
				'content' => 'First Content...'
			],
			'post2' => [
				'title' => 'My Second Post',
				'content' => 'Also some foobar text'
			],
			'post3' => [
				'title' => 'My Third Post',
				'content' => 'I like to write some foobar foo too'
			]
		];
		$posts = new MockMultiKeyRecordSet(['data' => $expected]);
		$this->assertCount(3, $posts->get('_data'));

		$this->assertEqual($expected['post1'], $posts->first());
		$this->assertEqual($expected['post1'], $posts->current());
		$this->assertEqual($expected['post2'], $posts->next());
		$this->assertEqual($expected['post2'], $posts->current());
		$this->assertEqual($expected['post1'], $posts->prev());
		$this->assertEqual($expected['post2'], $posts->next());
		$this->assertEqual($expected['post3'], $posts->next());
		$this->assertEqual($expected['post3'], $posts->current());
		$this->assertEqual($expected['post2'], $posts->prev());
		$this->assertEqual($expected['post1'], $posts->rewind());
		$this->assertEqual($expected['post1'], $posts->current());
		$this->assertEqual($expected['post1'], $posts['post1']);

		$posts = new MockMultiKeyRecordSet();
		$posts->set($expected);
		$this->assertCount(3, $posts->get('_data'));

		$this->assertEqual($expected['post1'], $posts->first());
		$this->assertEqual($expected['post1'], $posts->current());
		$this->assertEqual($expected['post2'], $posts->next());
		$this->assertEqual($expected['post2'], $posts->current());
		$this->assertEqual($expected['post1'], $posts->prev());
		$this->assertEqual($expected['post2'], $posts->next());
		$this->assertEqual($expected['post3'], $posts->next());
		$this->assertEqual($expected['post3'], $posts->current());
		$this->assertEqual($expected['post2'], $posts->prev());
		$this->assertEqual($expected['post1'], $posts->rewind());
		$this->assertEqual($expected['post1'], $posts->current());
		$this->assertEqual($expected['post1'], $posts['post1']);
	}

	public function testRewindReinitialization() {
		$counter = 0;
		while ($record = $this->_recordSet->current()) {
			$this->assertEqual($this->_records[$counter], $record->to('array'));
			$counter++;
			$this->_recordSet->next();
		}
		$this->assertEqual(4, $counter);
		$this->_recordSet->rewind();
		$counter = 0;
		while ($this->_recordSet->key() !== null) {
			$record = $this->_recordSet->current();
			$this->assertEqual($this->_records[$counter], $record->to('array'));
			$this->_recordSet->next();
			$counter++;
		}
		$this->assertEqual(4, $counter);
	}

	public function testMockResultContent() {
		$result = new MockResult(['records' => []]);

		$result->rewind();
		$i = 0;
		foreach ($result as $r) {
			$i++;
		}
		$this->assertEqual(0, $i);

		$records = [
			['id' => 1, 'data' => 'data1'],
			['id' => 2, 'data' => 'data2'],
			['id' => 3, 'data' => 'data3'],
			['id' => 4, 'data' => 'data4']
		];
		$result = new MockResult(['records' => $records]);

		$i = 0;
		foreach ($result as $s) {
			$this->assertEqual($records[$i], $s);
			$i++;
		}
		$this->assertEqual(4, $i);

		$records = [
			[false],
			['id' => 1, 'data' => 'data1'],
			['id' => 2, 'data' => 'data2'],
			['id' => 3, 'data' => 'data3'],
			['id' => 4, 'data' => 'data4']
		];
		$result = new MockResult(['records' => $records]);

		$i = 0;
		foreach ($result as $s) {
			$this->assertEqual($records[$i], $s);
			$i++;
		}
		$this->assertEqual(5, $i);
	}

	public function testUnsetInForeach() {
		$records = [
			['id' => 1, 'data' => 'delete']
		];
		$result = new MockResult(['records' => $records]);

		$model = $this->_model;

		$recordSet = new MockMultiKeyRecordSet(compact('result', 'model') + [
			'exists' => true
		]);

		$cpt = 0;
		foreach ($recordSet as $i => $word) {
			$array = $word->to('array');
			if ($array['data'] === 'delete') {
				unset($recordSet[$i]);
			}
			$cpt++;
		}

		$this->assertEqual(1, $cpt);
		$this->assertIdentical([], $recordSet->to('array'));

		$records = [
			1 => ['id' => 1, 'data' => 'delete'],
			3 => ['id' => 2, 'data' => 'data2'],
			'hello' => ['id' => 3, 'data' => 'delete'],
			0 => ['id' => 4, 'data' => 'data4'],
			7 => ['id' => 5, 'data' => 'delete'],
			8 => ['id' => 6, 'data' => 'delete'],
			10 => ['id' => 7, 'data' => 'data7'],
			50 => ['id' => 8, 'data' => 'delete']
		];
		$result = new MockResult(['records' => $records]);

		$model = $this->_model;

		$recordSet = new MockMultiKeyRecordSet(compact('result', 'model') + [
			'exists' => true
		]);

		foreach ($recordSet as $i => $word) {
			$array = $word->to('array');
			if ($array['data'] === 'delete') {
				unset($recordSet[$i]);
			}
		}

		$this->assertCount(3, $recordSet);

		$expected = [
			2 => ['id' => 2, 'data' => 'data2'],
			4 => ['id' => 4, 'data' => 'data4'],
			7 => ['id' => 7, 'data' => 'data7']
		];

		$this->assertIdentical($expected, $recordSet->to('array'));
	}

	public function testValid() {
		$collection = new MultiKeyRecordSet();
		$this->assertFalse($collection->valid());

		$collection = new MultiKeyRecordSet(['data' => ['value' => 42]]);
		$this->assertTrue($collection->valid());

		$resource = new MockResult(['records' => []]);
		$collection = new MultiKeyRecordSet(['model' => $this->_model, 'result' => $resource]);
		$this->assertFalse($collection->valid());

		$resource = new MockResult([
			'records' => [['id' => 1, 'data' => 'data1']]
		]);
		$collection = new MultiKeyRecordSet(['model' => $this->_model, 'result' => $resource]);
		$this->assertTrue($collection->valid());
	}

	public function testRecordWithCombinedPk() {
		$data = [
			[
				'client_id' => 1,
				'invoice_id' => 4,
				'title' => 'Payment1'
			],
			[
				'client_id' => 2,
				'invoice_id' => 5,
				'title' => 'Payment2'
			],
			[
				'client_id' => 3,
				'invoice_id' => 6,
				'title' => 'Payment3'
			]
		];

		$payments = new MockMultiKeyRecordSet(['data' => $data, 'model' => $this->_model2]);
		$this->assertCount(3, $payments->get('_data'));

		$index = ['client_id' => 1, 'invoice_id' => 4];
		$this->assertEqual($data[0], $payments[$index]->data());

		$index = ['client_id' => 3, 'invoice_id' => 6];
		$this->assertEqual($data[2], $payments[$index]->data());

		$this->assertNull($payments[['client_id' => 3, 'invoice_id' => 3]]);
		$this->assertNull($payments[['client_id' => 2]]);
		$this->assertNull($payments[['invoice_id' => 6]]);

		$index = ['client_id' => 2, 'invoice_id' => 5];
		$this->assertEqual($data[1], $payments[$index]->data());
	}

	public function testKeyCastingManagment() {
		$payments = new MockMultiKeyRecordSet();
		$payments[true] = ['title' => 'Payment1'];
		$payments[null] = ['title' => 'Payment2'];
		$payments[false] = ['title' => 'Payment3'];
		$payments[[]] = ['title' => 'Payment4'];

		$expected = [
			0 => ['title' => 'Payment1'],
			1 => ['title' => 'Payment2'],
			2 => ['title' => 'Payment3'],
			3 => ['title' => 'Payment4']
		];

		$this->assertEqual($expected, $payments->data());

		$expected = ['title' => 'Payment1 updated'];
		$payments[0] = $expected;
		$this->assertEqual($expected, $payments[0]);

		$expected = ['title' => 'Payment1 updated 2'];
		$payments['0'] = $expected;
		$this->assertEqual($expected, $payments['0']);
		$this->assertEqual($expected, $payments[0]);
	}

	public function testRecordWithCombinedPkAndLazyLoading() {
		$records = [
			['client_id' => 1, 'invoice_id' => 4, 'title' => 'Payment1'],
			['client_id' => 2, 'invoice_id' => 5, 'title' => 'Payment2'],
			['client_id' => 2, 'invoice_id' => 6, 'title' => 'Payment3'],
			['client_id' => 4, 'invoice_id' => 7, 'title' => 'Payment3']
		];

		$result = new MockResult(['records' => $records]);

		$payments = new MockMultiKeyRecordSet([
			'result' => $result, 'model' => $this->_model2
		]);
		$this->assertCount(0, $payments->get('_data'));

		$result = $payments[['client_id' => 1, 'invoice_id' => 4]]->to('array');
		$this->assertEqual($records[0], $result);

		$result = $payments[['client_id' => 2, 'invoice_id' => 6]]->to('array');
		$this->assertEqual($records[2], $result);
		$this->assertCount(3, $payments->get('_data'));

		$result = $payments[['client_id' => 2, 'invoice_id' => 5]]->to('array');
		$this->assertEqual($records[1], $result);
		$this->assertCount(3, $payments->get('_data'));

		$this->assertNull($payments[['client_id' => 3, 'invoice_id' => 3]]);
		$this->assertNull($payments[['client_id' => 2]]);
		$this->assertNull($payments[['invoice_id' => 6]]);

		$this->assertCount(4, $payments->get('_data'));

		$this->assertEqual($records, $payments->to('array'));
		$expected = '[{"client_id":1,"invoice_id":4,"title":"Payment1"},';
		$expected .= '{"client_id":2,"invoice_id":5,"title":"Payment2"},';
		$expected .= '{"client_id":2,"invoice_id":6,"title":"Payment3"},';
		$expected .= '{"client_id":4,"invoice_id":7,"title":"Payment3"}]';

		Collection::formats('lithium\net\http\Media');
		$this->assertEqual($expected, $payments->to('json'));
	}

	public function testInternalWithCombinedPkKeys() {
		$data = [
			[
				'client_id' => 1,
				'invoice_id' => 4,
				'title' => 'Payment1'
			],
			[
				'client_id' => 2,
				'invoice_id' => 5,
				'title' => 'Payment2'
			],
			[
				'client_id' => 3,
				'invoice_id' => 6,
				'title' => 'Payment3'
			]
		];

		$payments = new MockMultiKeyRecordSet(['data' => $data, 'model' => $this->_model2]);

		$expected = [
			[
				'client_id' => 1,
				'invoice_id' => 4
			],
			[
				'client_id' => 2,
				'invoice_id' => 5
			],
			[
				'client_id' => 3,
				'invoice_id' => 6
			]
		];
		$this->assertEqual($expected, $payments->keys());
	}

	public function testInternalKeys() {
		$this->assertEqual([0 => 1, 1 => 2, 2 => 3, 3 => 4], $this->_recordSet->keys());
		$this->assertEqual([0 => 1, 1 => 2, 2 => 3, 3 => 4], $this->_objectRecordSet->keys());
	}
}

?>