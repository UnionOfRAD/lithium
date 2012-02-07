<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\collection;

use lithium\data\source\mongo_db\Schema;
use lithium\data\collection\DocumentArray;

class DocumentArrayTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\model\MockDocumentPost';

	public function testInitialCasting() {
		$model = $this->_model;
		$schema = new Schema(array('fields' => array(
			'_id' => array('type' => 'id'),
			'foo' => array('type' => 'object'),
			'foo.bar' => array('type' => 'int')
		)));

		$array = new DocumentArray(compact('model', 'schema') + array(
			'pathKey' => 'foo.bar',
			'data' => array('5', '6', '7')
		));

		foreach ($array as $value) {
			$this->assertTrue(is_int($value));
		}
	}

	public function testAddValueAndExport() {
		$array = new DocumentArray(array(
			'model' => $this->_model,
			'pathKey' => 'foo',
			'data' => array('bar')
		));
		$array[] = 'baz';

		$expected = array('bar', 'baz');
		$result = $array->data();
		$this->assertEqual($expected, $result);
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
		$doc = new DocumentArray(compact('data'));
		$this->assertIdentical($data, $doc->data());

		foreach ($doc as $i => $word) {
			if ($word == 'Delete me') {
				unset($doc->{$i});
			}
		}

		$expected = array(0 => 'Hello', 6 => 'Hello again!');
		$this->assertIdentical($expected, $doc->data());

		$doc = new DocumentArray(compact('data'));

		foreach ($doc as $i => $word) {
			if ($word == 'Delete me') {
				unset($doc[$i]);
			}
		}
		$expected = array(0 => 'Hello', 6 => 'Hello again!');
		$this->assertIdentical($expected, $doc->data());
	}

	public function testArrayOfObjects() {
		$schema = new Schema();
		$first  = (object) array('name' => 'First');
		$second = (object) array('name' => 'Second');
		$third  = (object) array('name' => 'Third');
		$doc = new DocumentArray(compact('schema') + array(
			'data' => array($first, $second, $third)
		));

		$this->assertTrue(is_object($doc[0]));
		$this->assertTrue(is_object($doc[1]));
		$this->assertTrue(is_object($doc[2]));
		$this->assertEqual(3, count($doc));
	}
}

?>