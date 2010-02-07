<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use \lithium\tests\mocks\data\collection\MockRecordSet;
use \lithium\tests\mocks\data\source\database\adapter\MockAdapter;
use \lithium\tests\mocks\data\MockModel;
use \lithium\data\Connections;

/**
 * RecordSet tests
 */
class RecordSetTest extends \lithium\test\Unit {
	/**
	 * RecordSet object to test
	 *
	 * @var object
	 */
	protected $_recordSet = null;

	/**
	 * Array of records for testing
	 *
	 * @var array
	 */
	protected $_records = array(
		array('id' => 1, 'data' => 'data1'),
		array('id' => 2, 'data' => 'data2'),
		array('id' => 3, 'data' => 'data3'),
		array('id' => 4, 'data' => 'data4')
	);

	public function setUp() {
		$connection = new MockAdapter(array(
			'records' => $this->_records,
			'columns' => array('Test' => array('id', 'data')),
			'autoConnect' => false
		));

		$this->_recordSet = new MockRecordSet(array(
			'model'  => 'lithium\tests\mocks\data\MockModel',
			'handle' => &$connection,
			'result' => true,
			'exists' => true,
		));
	}

	public function testInit() {
		$recordSet = new MockRecordSet();

		$this->assertTrue(is_a($recordSet, '\lithium\data\collection\RecordSet'));

		$recordSet = new MockRecordSet(array(
			'model'  => 'lithium\tests\mocks\data\MockModel',
			'handle' => new MockAdapter(),
			'result' => true,
			'exists' => true,
		));

		$this->assertEqual('lithium\tests\mocks\data\MockModel', $recordSet->get('_model'));
		$this->assertTrue($recordSet->get('_result'));


	}


	public function testOffsetExists() {
		$this->assertFalse($this->_recordSet->offsetExists(0));
		$this->assertTrue($this->_recordSet->offsetExists(1));
		$this->assertTrue($this->_recordSet->offsetExists(2));
		$this->assertTrue($this->_recordSet->offsetExists(3));
		$this->assertTrue($this->_recordSet->offsetExists(4));

		$this->assertTrue(isset($this->_recordSet[3]));
	}

	public function testOffsetGet() {
		$expected = array('id' => 1, 'data' => 'data1');
		$this->assertEqual($expected, $this->_recordSet[1]->to('array'));

		$expected = array('id' => 2, 'data' => 'data2');
		$this->assertEqual($expected, $this->_recordSet[2]->to('array'));

		$expected = array('id' => 3, 'data' => 'data3');
		$this->assertEqual($expected, $this->_recordSet[3]->to('array'));

		$expected = array('id' => 4, 'data' => 'data4');
		$this->assertEqual($expected, $this->_recordSet[4]->to('array'));

		$expected = array('id' => 3, 'data' => 'data3');
		$this->assertEqual($this->_records[2], $this->_recordSet[3]->to('array'));

		$this->expectException();
		$this->_recordSet[5];
	}

	public function testOffsetGetBackwards() {
		$expected = array('id' => 4, 'data' => 'data4');
		$this->assertEqual($expected, $this->_recordSet[4]->to('array'));

		$expected = array('id' => 3, 'data' => 'data3');
		$this->assertEqual($expected, $this->_recordSet[3]->to('array'));

		$expected = array('id' => 2, 'data' => 'data2');
		$this->assertEqual($expected, $this->_recordSet[2]->to('array'));

		$expected = array('id' => 1, 'data' => 'data1');
		$this->assertEqual($expected, $this->_recordSet[1]->to('array'));
	}

	public function testOffsetSet() {
		$this->_recordSet[5] = $expected = array('id' => 5, 'data' => 'data5');
		$items = $this->_recordSet->get('_items');
		$this->assertEqual($expected, $items[0]->to('array'));

		$this->_recordSet[] = $expected = array('id' => 6, 'data' => 'data6');
		$items = $this->_recordSet->get('_items');
		$this->assertEqual($expected, $items[1]->to('array'));
	}

	public function testOffsetSetWithLoadedData() {
		$this->_recordSet[0];
		$this->_recordSet[1] = array('id' => 1, 'data' => 'new data1');

		$expected = array(
			1 => array('id' => 1, 'data' => 'new data1'),
			2 => array('id' => 2, 'data' => 'data2'),
			3 => array('id' => 3, 'data' => 'data3'),
			4 => array('id' => 4, 'data' => 'data4'),
		);
		$this->assertEqual($expected, $this->_recordSet->to('array'));
	}

	public function testOffsetUnset() {
		$this->_recordSet[0];
		unset($this->_recordSet[1]);

		$expected = array(
			2 => array('id' => 2, 'data' => 'data2'),
			3 => array('id' => 3, 'data' => 'data3'),
			4 => array('id' => 4, 'data' => 'data4')
		);

		$this->assertEqual($expected, $this->_recordSet->to('array'));
	}

	public function testRewind() {
		$this->_recordSet[0];
		$this->_recordSet->rewind();

		$expected = array('id' => 1, 'data' => 'data1');
		$this->assertEqual($expected, $this->_recordSet->current()->to('array'));
	}

	public function testCurrent() {
		$this->assertFalse(isset($this->_recordSet[0]));

		$this->_recordSet->set('_pointer', 1);
		$this->assertEqual($this->_records[1], $this->_recordSet->current()->to('array'));

		$this->_recordSet->set('_pointer', 2);
		$this->assertEqual($this->_records[2], $this->_recordSet->current()->to('array'));
	}

	public function testKey() {
		$this->assertFalse(isset($this->_recordSet[0]));

		$this->_recordSet->set('_pointer', 1);
		$this->assertEqual(2, $this->_recordSet->key());

		$this->_recordSet->set('_pointer', 2);
		$this->assertEqual(3, $this->_recordSet->key());
	}

	public function testNextWithForEach() {
		$counter = 0;
		foreach($this->_recordSet as $record) {
			$this->assertEqual($this->_records[$counter], $record->to('array'));
			$counter++;
		}
	}

	public function testNextWithWhile() {
		$counter = 0;
		while($record = $this->_recordSet->next()) {
			$this->assertEqual($this->_records[$counter], $record->to('array'));
			$counter++;
		}
	}

	public function testMeta() {
		$expected = array('model' => 'lithium\tests\mocks\data\MockModel');
		$this->assertEqual($expected, $this->_recordSet->meta());
	}

	public function testTo() {
		$this->assertFalse(isset($this->_recordSet[0]));
		$expected = array(
			1 => array('id' => 1, 'data' => 'data1'),
			2 => array('id' => 2, 'data' => 'data2'),
			3 => array('id' => 3, 'data' => 'data3'),
			4 => array('id' => 4, 'data' => 'data4')
		);
		$this->assertEqual($expected, $this->_recordSet->to('array'));

		$expected = '{"1":{"id":1,"data":"data1"},"2":{"id":2,"data":"data2"},'
			. '"3":{"id":3,"data":"data3"},"4":{"id":4,"data":"data4"}}';
		$this->assertEqual($expected, $this->_recordSet->to('json'));
	}

	public function testEach() {
		$filter = function($rec) {
			$rec->more_data = 'More Data' . $rec->id;
			return $rec;
		};
		$expected = array(
			1 => array('id' => 1, 'data' => 'data1', 'more_data' => 'More Data1'),
			2 => array('id' => 2, 'data' => 'data2', 'more_data' => 'More Data2'),
			3 => array('id' => 3, 'data' => 'data3', 'more_data' => 'More Data3'),
			4 => array('id' => 4, 'data' => 'data4', 'more_data' => 'More Data4')
		);
		$result = $this->_recordSet->each($filter)->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testMap() {
		$filter = function($rec) {
			return $rec->id . $rec->data;
		};
		$expected = array('1data1', '2data2', '3data3', '4data4');

		$result = $this->_recordSet->map($filter, array('collect' => false));
		$this->assertEqual($expected, $result);

		$result = $this->_recordSet->map($filter);

		$this->assertEqual($expected, $result->get('_items'));
	}
}

?>