<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\collection;

use lithium\data\source\MongoDb;
use lithium\data\source\mongo_db\Schema;
use lithium\data\entity\Document;
use lithium\data\collection\DocumentArray;
use lithium\tests\mocks\data\model\MockDocumentPost;
use lithium\tests\mocks\data\source\mongo_db\MockResult;
use lithium\tests\mocks\data\source\MockMongoConnection;

class DocumentArrayTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\model\MockDocumentPost';

	public function setUp() {
		MockDocumentPost::config(array('connection' => 'mongo'));
		MockDocumentPost::$connection = new MongoDb(array('autoConnect' => false));
		MockDocumentPost::$connection->connection = new MockMongoConnection();
	}

	public function tearDown() {
		MockDocumentPost::$connection = null;
	}

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
				unset($doc[$i]);
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

	public function testOffsetSet() {
		$data   = array('change me', 'foo', 'bar');
		$doc    = new DocumentArray(compact('data'));
		$doc[0] = 'new me';
		$expected = array(0 => 'new me', 1 => 'foo', 2 => 'bar');
		$this->assertIdentical($expected, $doc->data());
	}

	public function testPopulateResourceClose() {
		$resource = new MockResult();

		$doc = new DocumentArray(array('model' => $this->_model, 'result' => $resource));
		$model = $this->_model;

		$result = $doc->rewind();
		$this->assertTrue($result instanceof Document);
		$this->assertTrue(is_object($result['_id']));

		$expected = array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar');
		$this->assertEqual($expected, $result->data());

		$expected = array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo');
		$this->assertEqual($expected, $doc->next()->data());

		$expected = array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib');
		$result = $doc->next()->data();
		$this->assertEqual($expected, $result);

		$this->assertFalse($doc->next());
	}

	public function testOffsetGetBackwards() {
		$resource = new MockResult();
		$doc = new DocumentArray(array('model' => $this->_model, 'result' => $resource));
		$model = $this->_model;

		$expected = array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib');
		$this->assertEqual($expected, $doc[2]->data());

		$expected = array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo');
		$this->assertEqual($expected, $doc[1]->data());

		$expected = array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar');
		$this->assertEqual($expected, $doc[0]->data());
	}

	public function testMappingToNewDocumentArray() {
		$result = new MockResult();
		$model = $this->_model;
		$doc = new DocumentArray(compact('model', 'result'));

		$mapped = $doc->map(function($data) { return $data; });
		$this->assertEqual($doc->data(), $mapped->data());
		$this->assertEqual($model, $doc->model());
		$this->assertEqual($model, $mapped->model());
	}
}

?>
