<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\collection;

use lithium\data\Connections;
use lithium\data\DocumentSchema;
use lithium\data\entity\Document;
use lithium\data\collection\DocumentSet;
use lithium\tests\mocks\data\model\MockDocumentPost;
use lithium\tests\mocks\data\source\MockResult;
use lithium\tests\mocks\data\MockDocumentSource;
use lithium\util\Collection;

class DocumentSetTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\model\MockDocumentPost';

	public function setUp() {
		$connection = new MockDocumentSource();
		Connections::add('mockconn', array('object' => $connection));
		MockDocumentPost::config(array('meta' => array('connection' => 'mockconn')));
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockDocumentPost::reset();
	}

	public function testInitialCasting() {
		$model = $this->_model;
		$schema = new DocumentSchema(array(
			'fields' => array(
				'_id' => array('type' => 'id'),
				'foo' => array('type' => 'object'),
				'foo.bar' => array('type' => 'int')
			),
			'types' => array(
				'int' => 'integer'
			),
			'handlers' => array(
				'integer' => function($v) { return (integer) $v; },
			)
		));

		$array = new DocumentSet(compact('model', 'schema') + array(
			'pathKey' => 'foo.bar',
			'data' => array('5', '6', '7')
		));

		foreach ($array as $value) {
			$this->assertInternalType('int', $value);
		}
	}

	public function testInitialCastingOnSubObject() {
		$model = $this->_model;

		$schema = new DocumentSchema(array(
			'fields' => array(
				'_id' => array('type' => 'id'),
				'body' => array('type' => 'string'),
				'foo' => array('type' => 'object'),
				'foo.bar' => array('type' => 'int')
			),
			'types' => array(
				'int' => 'integer'
			),
			'handlers' => array(
				'integer' => function($v) { return (integer) $v; },
			)
		));

		$array = new DocumentSet(compact('model', 'schema') + array(
			'data' => array(
				array(
					'_id' => '4cb4ab6d7addf98506010002',
					'body' => 'body1',
					'foo' => (object) array('bar' => '1')
				),
				array(
					'_id' => '4cb4ab6d7addf98506010003',
					'body' => 'body2',
					'foo' => (object) array('bar' => '2')
				),
				array(
					'_id' => '4cb4ab6d7addf98506010004',
					'body' => 'body3',
					'foo' => (object) array('bar' => '3')
				)
			)
		));

		foreach ($array as $document) {
			$this->assertInternalType('string', $document->_id);
			$this->assertInternalType('string', $document->body);
			$this->assertInternalType('object', $document->foo);
			$this->assertInternalType('string', $document->foo->bar);
		}

		$array = new DocumentSet(compact('model', 'schema') + array(
			'data' => array(
				array(
					'_id' => '4cb4ab6d7addf98506010002',
					'body' => 'body1',
					'foo' => array('bar' => '1')
				),
				array(
					'_id' => '4cb4ab6d7addf98506010003',
					'body' => 'body2',
					'foo' => array('bar' => '2')
				),
				array(
					'_id' => '4cb4ab6d7addf98506010004',
					'body' => 'body3',
					'foo' => array('bar' => '3')
				)
			)
		));

		foreach ($array as $document) {
			$this->assertInternalType('string', $document->_id);
			$this->assertInternalType('string', $document->body);
			$this->assertInternalType('object', $document->foo);
			$this->assertInternalType('int', $document->foo->bar);
		}

	}

	public function testAddValueAndExport() {
		$array = new DocumentSet(array(
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
		$doc = new DocumentSet(compact('data'));
		$this->assertIdentical($data, $doc->data());

		foreach ($doc as $i => $word) {
			if ($word === 'Delete me') {
				unset($doc[$i]);
			}
		}

		$expected = array(0 => 'Hello', 6 => 'Hello again!');
		$this->assertIdentical($expected, $doc->data());

		$doc = new DocumentSet(compact('data'));

		foreach ($doc as $i => $word) {
			if ($word === 'Delete me') {
				unset($doc[$i]);
			}
		}
		$expected = array(0 => 'Hello', 6 => 'Hello again!');
		$this->assertIdentical($expected, $doc->data());
	}

	public function testArrayOfObjects() {
		$schema = new DocumentSchema();
		$first  = (object) array('name' => 'First');
		$second = (object) array('name' => 'Second');
		$third  = (object) array('name' => 'Third');
		$doc = new DocumentSet(compact('schema') + array(
			'data' => array($first, $second, $third)
		));

		$this->assertInternalType('object', $doc[0]);
		$this->assertInternalType('object', $doc[1]);
		$this->assertInternalType('object', $doc[2]);
		$this->assertCount(3, $doc);
	}

	public function testOffsetSet() {
		$data   = array('change me', 'foo', 'bar');
		$doc    = new DocumentSet(compact('data'));
		$doc[0] = 'new me';

		$expected = array(0 => 'new me', 1 => 'foo', 2 => 'bar');
		$this->assertIdentical($expected, $doc->data());
	}

	public function testPopulateResourceClose() {
		$result = new MockResult(array(
			'data' => array(
				array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar'),
				array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo'),
				array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib')
			)
		));
		$doc = new DocumentSet(array('model' => $this->_model, 'result' => $result));

		$result = $doc->rewind();
		$this->assertInstanceOf('lithium\data\entity\Document', $result);
		$this->assertInternalType('string', $result['_id']);

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
		$result = new MockResult(array(
			'data' => array(
				array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar'),
				array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo'),
				array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib')
			)
		));
		$doc = new DocumentSet(array('model' => $this->_model, 'result' => $result));

		$expected = array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib');
		$this->assertEqual($expected, $doc['6c8f86167675abfabdbf0302']->data());

		$expected = array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo');
		$this->assertEqual($expected, $doc['5c8f86167675abfabdbf0301']->data());

		$expected = array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar');
		$this->assertEqual($expected, $doc['4c8f86167675abfabdbf0300']->data());
	}

	public function testMappingToNewDocumentSet() {
		$result = new MockResult(array(
			'data' => array(
				array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar'),
				array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo'),
				array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib')
			)
		));
		$model = $this->_model;
		$doc = new DocumentSet(compact('model', 'result'));

		$mapped = $doc->map(function($data) { return $data; });
		$this->assertEqual($doc->data(), $mapped->data());
		$this->assertEqual($model, $doc->model());
		$this->assertEqual($model, $mapped->model());
	}

	public function testValid() {
		$collection = new DocumentSet();
		$this->assertFalse($collection->valid());

		$collection = new DocumentSet(array('data' => array('value' => 42)));
		$this->assertTrue($collection->valid());

		$resource = new MockResult(array('data' => array()));
		$collection = new DocumentSet(array('model' => $this->_model, 'result' => $resource));
		$this->assertFalse($collection->valid());

		$resource = new MockResult(array(
			'data' => array(array('id' => 1, 'data' => 'data1'))
		));
		$collection = new DocumentSet(array('model' => $this->_model, 'result' => $resource));
		$this->assertTrue($collection->valid());
	}

	public function testInternalKeys() {
		$result = new MockResult(array(
			'data' => array(
				array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar'),
				array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo'),
				array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib')
			)
		));
		$doc = new DocumentSet(array('model' => $this->_model, 'result' => $result));
		$this->assertEqual(array(
			0 => '4c8f86167675abfabdbf0300',
			1 => '5c8f86167675abfabdbf0301',
			2 => '6c8f86167675abfabdbf0302'
		), $doc->keys());
	}

	public function testTo() {
		Collection::formats('lithium\net\http\Media');
		$result = new MockResult(array(
			'data' => array(
				array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar'),
				array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo'),
				array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib')
			)
		));
		$doc = new DocumentSet(array('model' => $this->_model, 'result' => $result));
		$expected = array(
			'4c8f86167675abfabdbf0300' => array(
				'_id' => '4c8f86167675abfabdbf0300',
				'title' => 'bar'
			),
			'5c8f86167675abfabdbf0301' => array(
				'_id' => '5c8f86167675abfabdbf0301',
				'title' => 'foo'
			),
			'6c8f86167675abfabdbf0302' => array(
				'_id' => '6c8f86167675abfabdbf0302',
				'title' => 'dib'
			)
		);
		$this->assertEqual($expected, $doc->to('array'));

		$expected = array(
			array(
				'_id' => '4c8f86167675abfabdbf0300',
				'title' => 'bar'
			),
			array(
				'_id' => '5c8f86167675abfabdbf0301',
				'title' => 'foo'
			),
			array(
				'_id' => '6c8f86167675abfabdbf0302',
				'title' => 'dib'
			)
		);
		$this->assertEqual($expected, $doc->to('array', array('indexed' => false)));
	}

	public function testParent() {
		$model = $this->_model;
		$schema = new DocumentSchema(array('fields' => array(
			'_id' => array('type' => 'id'),
			'bar' => array('array' => true),
			'foo' => array('type' => 'object', 'array' => true),
			'foo.foo' => array('type' => 'integer'),
			'foo.bar' => array('type' => 'integer')
		)));
		$doc = new Document(compact('model', 'schema'));

		$expected = array(
			'foo' => 1,
			'bar' => 2
		);
		$doc->foo[] = $expected;
		$this->assertEqual($doc, $doc->foo->parent());
		$this->assertEqual($expected, $doc->foo[0]->data());

		$data = array(
			'_id' => '4fb6e2df3e91581fe6e75737',
			'foo' => array($expected)
		);

		$doc = new Document(compact('model', 'schema', 'data'));
		$this->assertEqual($doc, $doc->foo->parent());
		$this->assertEqual($expected, $doc->foo[0]->data());
	}

	public function testHandlers() {
		$model = $this->_model;
		$schema = new DocumentSchema(array(
			'fields' => array(
				'_id' => array('type' => 'id'),
				'date' => array('type' => 'date')
			),
			'types' => array(
				'date' => 'date'
			),
			'handlers' => array(
				'date' => function($v) { return (object) $v; },
			)
		));
		$handlers = array(
			'stdClass' => function($value) { return date('d/m/Y H:i', strtotime($value->scalar)); }
		);
		$array = new DocumentSet(compact('model', 'schema', 'handlers') + array(
			'data' => array(
				array(
					'_id' => '2',
					'date' => '2013-06-06 13:00:00'
				),
				array(
					'_id' => '3',
					'date' => '2013-06-06 12:00:00'
				),
				array(
					'_id' => '4',
					'date' => '2013-06-06 11:00:00'
				)
			)
		));

		$expected = array(
			array(
				'_id' => '2',
				'date' => '06/06/2013 13:00'
			),
			array(
				'_id' => '3',
				'date' => '06/06/2013 12:00'
			),
			array (
				'_id' => '4',
				'date' => '06/06/2013 11:00'
			)
		);
		$this->assertIdentical($expected, $array->to('array', array('indexed' => false)));
	}
}

?>