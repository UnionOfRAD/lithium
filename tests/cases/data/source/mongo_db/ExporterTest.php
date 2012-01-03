<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\mongo_db;

use MongoId;
use MongoDate;
use lithium\data\source\MongoDb;
use lithium\data\entity\Document;
use lithium\data\collection\DocumentArray;
use lithium\data\source\mongo_db\Exporter;

class ExporterTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\source\MockMongoPost';

	protected $_schema = array(
		'_id' => array('type' => 'id'),
		'guid' => array('type' => 'id'),
		'title' => array('type' => 'string'),
		'tags' => array('type' => 'string', 'array' => true),
		'comments' => array('type' => 'MongoId'),
		'authors' => array('type' => 'MongoId', 'array' => true),
		'created' => array('type' => 'MongoDate'),
		'modified' => array('type' => 'datetime'),
		'voters' => array('type' => 'id', 'array' => true),
		'rank_count' => array('type' => 'integer', 'default' => 0),
		'rank' => array('type' => 'float', 'default' => 0.0),
		'notifications.foo' => array('type' => 'boolean'),
		'notifications.bar' => array('type' => 'boolean'),
		'notifications.baz' => array('type' => 'boolean')
	);

	protected $_handlers = array();

	public function skip() {
		$this->skipIf(!MongoDb::enabled(), 'MongoDb is not enabled');
	}

	public function setUp() {
		$this->_handlers = array(
			'id' => function($v) {
				return is_string($v) && preg_match('/^[0-9a-f]{24}$/', $v) ? new MongoId($v) : $v;
			},
			'date' => function($v) {
				$v = is_numeric($v) ? intval($v) : strtotime($v);
				return (time() == $v) ? new MongoDate() : new MongoDate($v);
			},
			'regex'   => function($v) { return new MongoRegex($v); },
			'integer' => function($v) { return (integer) $v; },
			'float'   => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; },
			'code'    => function($v) { return new MongoCode($v); },
			'binary'  => function($v) { return new MongoBinData($v); }
		);
		$model = $this->_model;
		$model::resetConnection(true);
	}

	public function testInvalid() {
		$this->assertNull(Exporter::get(null, null));
	}

	public function testCreateWithFixedData() {
		$doc = new Document(array('exists' => false, 'data' => array(
			'_id' => new MongoId(),
			'created' => new MongoDate(),
			'numbers' => new DocumentArray(array('data' => array(7, 8, 9))),
			'objects' => new DocumentArray(array('data' => array(
				new Document(array('data' => array('foo' => 'bar'))),
				new Document(array('data' => array('baz' => 'dib')))
			))),
			'deeply' => new Document(array('data' => array('nested' => 'object')))
		)));
		$this->assertEqual('object', $doc->deeply->nested);
		$this->assertTrue($doc->_id instanceof MongoId);

		$result = Exporter::get('create', $doc->export());
		$data = $doc->export();
		$this->assertTrue($result['create']['_id'] instanceof MongoId);
		$this->assertTrue($result['create']['created'] instanceof MongoDate);
		$this->assertIdentical(time(), $result['create']['created']->sec);

		$this->assertIdentical(array(7, 8, 9), $result['create']['numbers']);
		$expected = array(array('foo' => 'bar'), array('baz' => 'dib'));
		$this->assertIdentical($expected, $result['create']['objects']);
		$this->assertIdentical(array('nested' => 'object'), $result['create']['deeply']);
	}

	public function testCreateWithChangedData() {
		$doc = new Document(array('exists' => false, 'data' => array(
			'numbers' => new DocumentArray(array('data' => array(7, 8, 9))),
			'objects' => new DocumentArray(array('data' => array(
				new Document(array('data' => array('foo' => 'bar'))),
				new Document(array('data' => array('baz' => 'dib')))
			))),
			'deeply' => new Document(array('data' => array('nested' => 'object')))
		)));
		$doc->numbers[] = 10;
		$doc->deeply->nested2 = 'object2';
		$doc->objects[1]->dib = 'gir';

		$expected = array(
			'numbers' => array(7, 8, 9, 10),
			'objects' => array(array('foo' => 'bar'), array('baz' => 'dib', 'dib' => 'gir')),
			'deeply' => array('nested' => 'object', 'nested2' => 'object2')
		);
		$result = Exporter::get('create', $doc->export());
		$this->assertEqual(array('create'), array_keys($result));
		$this->assertEqual($expected, $result['create']);
	}

	public function testUpdateWithNoChanges() {
		$doc = new Document(array('exists' => true, 'data' => array(
			'numbers' => new DocumentArray(array('exists' => true, 'data' => array(7, 8, 9))),
			'objects' => new DocumentArray(array('exists' => true, 'data' => array(
				new Document(array('exists' => true, 'data' => array('foo' => 'bar'))),
				new Document(array('exists' => true, 'data' => array('baz' => 'dib')))
			))),
			'deeply' => new Document(array('exists' => true, 'data' => array('nested' => 'object')))
		)));
		$this->assertFalse(Exporter::get('update', $doc->export()));
	}

	public function testUpdateWithSubObjects() {
		$model = $this->_model;
		$exists = true;
		$model::config(array('key' => '_id'));

		$doc = new Document(compact('model', 'exists') + array('data' => array(
			'numbers' => new DocumentArray(compact('model', 'exists') + array(
				'data' => array(7, 8, 9), 'pathKey' => 'numbers'
			)),
			'objects' => new DocumentArray(compact('model', 'exists') + array(
				'data' => array(
					new Document(
						compact('model', 'exists') + array('data' => array('foo' => 'bar'))
					),
					new Document(
						compact('model', 'exists') + array('data' => array('foo' => 'baz'))
					)
				), 'pathKey' => 'numbers'
			)),
			'deeply' => new Document(compact('model', 'exists') + array(
				'pathKey' => 'deeply', 'data' => array('nested' => 'object')
			)),
			'foo' => 'bar'
		)));

		$doc->field = 'value';
		$doc->objects[1]->foo = 'dib';
		$doc->deeply->nested = 'foo';
		$doc->newObject = new Document(array(
			'exists' => false, 'data' => array('subField' => 'subValue')
		));
		$this->assertEqual('foo', $doc->deeply->nested);
		$this->assertEqual('subValue', $doc->newObject->subField);

		$doc->numbers = array(8, 9);

		$result = Exporter::get('update', $doc->export());
		$expected = array(
			'numbers' => array(8, 9),
			'newObject' => array('subField' => 'subValue'),
			'field' => 'value',
			'deeply.nested' => 'foo',
			'objects.1.foo' => 'dib'
		);
		$this->assertEqual($expected, $result['update']);
	}

	public function testFieldRemoval() {
		$doc = new Document(array('exists' => true, 'data' => array(
			'numbers' => new DocumentArray(array('data' => array(7, 8, 9))),
			'deeply' => new Document(array(
				'pathKey' => 'deeply', 'exists' => true, 'data' => array('nested' => 'object')
			)),
			'foo' => 'bar'
		)));

		unset($doc->numbers);
		$result = Exporter::get('update', $doc->export());
		$expected = array(
			'numbers' => true
		);
		$this->assertEqual($expected, $result['remove']);

		$doc->set(array('flagged' => true, 'foo' => 'baz', 'bar' => 'dib'));
		unset($doc->foo, $doc->flagged, $doc->numbers, $doc->deeply->nested);
		$result = Exporter::get('update', $doc->export());
		$expected = array(
			'foo' => true, 'deeply.nested' => true, 'numbers' => true
		);
		$this->assertEqual($expected, $result['remove']);
		$this->assertEqual(array('bar' => 'dib'), $result['update']);
	}

	/**
	 * Tests that when an existing object is attached as a value of another existing object, the
	 * whole sub-object is re-written to the new value.
	 */
	public function testAppendExistingObjects() {
		$doc = new Document(array('exists' => true, 'data' => array(
			'deeply' => new Document(array(
				'pathKey' => 'deeply', 'exists' => true, 'data' => array('nested' => 'object')
			)),
			'foo' => 'bar'
		)));
		$append = new Document(array('exists' => true, 'data' => array('foo' => 'bar')));

		$doc->deeply = $append;
		$result = Exporter::get('update', $doc->export());
		$expected = array('update' => array('deeply' => array('foo' => 'bar')));
		$this->assertEqual($expected, $result);

		$expected = array('$set' => array('deeply' => array('foo' => 'bar')));
		$this->assertEqual($expected, Exporter::toCommand($result));

		$doc->sync();
		$doc->append2 = new Document(array('exists' => false, 'data' => array('foo' => 'bar')));
		$expected = array('update' => array('append2' => array('foo' => 'bar')));
		$this->assertEqual($expected, Exporter::get('update', $doc->export()));
		$doc->sync();

		$this->assertFalse(Exporter::get('update', $doc->export()));
		$doc->append2->foo = 'baz';
		$doc->append2->bar = 'dib';
		$doc->deeply->nested = true;

		$expected = array('update' => array(
			'append2.foo' => 'baz', 'append2.bar' => 'dib', 'deeply.nested' => true
		));
		$this->assertEqual($expected, Exporter::get('update', $doc->export()));
	}

	public function testNestedObjectCasting() {
		$model = $this->_model;
		$data = array('notifications' => array('foo' => '', 'bar' => '1', 'baz' => 0, 'dib' => 42));

		$model::schema($this->_schema);
		$result = Exporter::cast($data, $this->_schema, $model::connection(), compact('model'));
		$this->assertIdentical(false, $result['notifications']->foo);
		$this->assertIdentical(true, $result['notifications']->bar);
		$this->assertIdentical(false, $result['notifications']->baz);
		$this->assertIdentical(42, $result['notifications']->dib);
	}

	/**
	 * Tests handling type values based on specified schema settings.
	 *
	 * @return void
	 */
	public function testTypeCasting() {
		$data = array(
			'_id' => '4c8f86167675abfabd970300',
			'title' => 'Foo',
			'tags' => 'test',
			'comments' => array(
				"4c8f86167675abfabdbe0300", "4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
			),
			'authors' => '4c8f86167675abfabdb00300',
			'created' => time(),
			'modified' => date('Y-m-d H:i:s'),
			'rank_count' => '45',
			'rank' => '3.45688'
		);
		$time = time();
		$model = $this->_model;
		$handlers = $this->_handlers;
		$options = compact('model', 'handlers');
		$result = Exporter::cast($data, $this->_schema, $model::connection(), $options);

		$this->assertEqual(array_keys($data), array_keys($result));
		$this->assertTrue($result['_id'] instanceof MongoId);
		$this->assertEqual('4c8f86167675abfabd970300', (string) $result['_id']);

		$this->assertTrue($result['comments'] instanceof DocumentArray);
		$this->assertEqual(3, count($result['comments']));

		$this->assertTrue($result['comments'][0] instanceof MongoId);
		$this->assertTrue($result['comments'][1] instanceof MongoId);
		$this->assertTrue($result['comments'][2] instanceof MongoId);
		$this->assertEqual('4c8f86167675abfabdbe0300', (string) $result['comments'][0]);
		$this->assertEqual('4c8f86167675abfabdbf0300', (string) $result['comments'][1]);
		$this->assertEqual('4c8f86167675abfabdc00300', (string) $result['comments'][2]);

		$this->assertEqual($data['comments'], $result['comments']->data());
		$this->assertEqual(array('test'), $result['tags']->data());
		$this->assertEqual(array('4c8f86167675abfabdb00300'), $result['authors']->data());
		$this->assertTrue($result['authors'][0] instanceof MongoId);

		$this->assertTrue($result['modified'] instanceof MongoDate);
		$this->assertTrue($result['created'] instanceof MongoDate);
		$this->assertTrue($result['created']->sec > 0);

		$this->assertEqual($time, $result['modified']->sec);
		$this->assertEqual($time, $result['created']->sec);

		$this->assertIdentical(45, $result['rank_count']);
		$this->assertIdentical(3.45688, $result['rank']);
	}

	public function testWithArraySchema() {
		$model = $this->_model;
		$model::schema(array(
			'_id' => array('type' => 'id'),
			'list' => array('type' => 'string', 'array' => true),
			'obj.foo' => array('type' => 'string'),
			'obj.bar' => array('type' => 'string')
		));
		$doc = new Document(compact('model'));
		$doc->list[] = array('foo' => '!!', 'bar' => '??');

		$data = array('list' => array(array('foo' => '!!', 'bar' => '??')));
		$this->assertEqual($data, $doc->data());

		$result = Exporter::get('create', $doc->export());
		$this->assertEqual($data, $result['create']);

		$result = Exporter::get('update', $doc->export());
		$this->assertEqual($data, $result['update']);

		$doc = new Document(compact('model'));
		$doc->list = array();
		$doc->list[] = array('foo' => '!!', 'bar' => '??');

		$data = array('list' => array(array('foo' => '!!', 'bar' => '??')));
		$this->assertEqual($data, $doc->data());

		$result = Exporter::get('create', $doc->export());
		$this->assertEqual($result['create'], $data);

		$result = Exporter::get('update', $doc->export());
		$this->assertEqual($result['update'], $data);
	}

	/**
	 * Test that sub-objects are properly casted on creating a new `Document`.
	 */
	public function testSubObjectCastingOnSave() {
		$model = $this->_model;
		$model::schema(array(
			'sub.foo' => array('type' => 'boolean'),
			'bar' => array('type' => 'boolean')
		));
		$data = array('sub' => array('foo' => 0), 'bar' => 1);
		$doc = new Document(compact('data', 'model'));

		$this->assertIdentical(true, $doc->bar);
		$this->assertIdentical(false, $doc->sub->foo);

		$data = array('sub.foo' => '1', 'bar' => '0');
		$doc = new Document(compact('data', 'model', 'schema'));

		$this->assertIdentical(false, $doc->bar);
		$this->assertIdentical(true, $doc->sub->foo);
	}

	/**
	 * Tests that a nested key on a previously saved document gets updated properly.
	 */
	public function testExistingNestedKeyOverwrite() {
		$doc = new Document(array('model' => $this->_model));
		$doc->{'this.that'} = 'value1';
		$this->assertEqual(array('this' => array('that' => 'value1')), $doc->data());

		$result = Exporter::get('create', $doc->export());
		$this->assertEqual(array('create' => array('this' => array('that' => 'value1'))), $result);

		$doc->sync();
		$doc->{'this.that'} = 'value2';
		$this->assertEqual(array('this' => array('that' => 'value2')), $doc->data());

		$result = Exporter::get('update', $doc->export());
		$this->assertEqual(array('update' => array('this.that' => 'value2')), $result);
	}

	public function testUpdatingArraysAndExporting() {
		$new = new Document(array('data' => array('name' => 'Acme, Inc.', 'active' => true)));

		$expected = array('name' => 'Acme, Inc.', 'active' => true);
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$new->foo = new DocumentArray(array('data' => array('bar')));
		$expected = array('name' => 'Acme, Inc.', 'active' => true, 'foo' => array('bar'));
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$expected = 'bar';
		$result = $new->foo[0];
		$this->assertEqual($expected, $result);

		$new->foo[1] = 'baz';

		$expected = 'baz';
		$result = $new->data();
		$this->assertEqual($expected, $result['foo'][1]);
	}

	/**
	 * @todo Implement me.
	 */
	public function testCreateWithWhitelist() {
	}
}

?>