<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\source\mongo_db;

use MongoId;
use MongoDate;
use lithium\data\Connections;
use lithium\data\source\MongoDb;
use lithium\data\entity\Document;
use lithium\data\collection\DocumentSet;
use lithium\data\source\mongo_db\Schema;
use lithium\data\source\mongo_db\Exporter;
use lithium\tests\mocks\data\source\MockResult;
use lithium\tests\mocks\data\source\MockMongoPost;

class ExporterTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\source\MockMongoPost';

	protected $_schema = [
		'_id' => ['type' => 'id'],
		'guid' => ['type' => 'id'],
		'title' => ['type' => 'string'],
		'tags' => ['type' => 'string', 'array' => true],
		'comments' => ['type' => 'MongoId'],
		'accounts' => ['type' => 'object', 'array' => true],
		'accounts._id' => ['type' => 'id'],
		'accounts.name' => ['type' => 'string'],
		'accounts.created' => ['type' => 'date'],
		'authors' => ['type' => 'MongoId', 'array' => true],
		'created' => ['type' => 'MongoDate'],
		'modified' => ['type' => 'datetime'],
		'voters' => ['type' => 'id', 'array' => true],
		'rank_count' => ['type' => 'integer', 'default' => 0],
		'rank' => ['type' => 'float', 'default' => 0.0],
		'notifications.foo' => ['type' => 'boolean'],
		'notifications.bar' => ['type' => 'boolean'],
		'notifications.baz' => ['type' => 'boolean']
	];

	protected $_handlers = [];

	public function skip() {
		$this->skipIf(!MongoDb::enabled(), 'MongoDb is not enabled');
	}

	public function setUp() {
		$this->_handlers = [
			'id' => function($v) {
				return is_string($v) && preg_match('/^[0-9a-f]{24}$/', $v) ? new MongoId($v) : $v;
			},
			'date' => function($v) {
				$v = is_numeric($v) ? (integer) $v : strtotime($v);
				return !$v ? new MongoDate() : new MongoDate($v);
			},
			'regex'   => function($v) { return new MongoRegex($v); },
			'integer' => function($v) { return (integer) $v; },
			'float'   => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; },
			'code'    => function($v) { return new MongoCode($v); },
			'binary'  => function($v) { return new MongoBinData($v); }
		];
		$model = $this->_model;
		Connections::add('mockconn', ['object' => new MongoDb(['autoConnect' => false])]);
		$model::config(['meta' => ['connection' => 'mockconn']]);

		$model::schema(false);
		$model::schema($this->_schema);
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockMongoPost::reset();
	}
	public function testInvalid() {
		$this->assertNull(Exporter::get(null, null));
	}

	public function testCreateWithFixedData() {
		$time = time();
		$doc = new Document(['exists' => false, 'data' => [
			'_id' => new MongoId(),
			'created' => new MongoDate($time),
			'numbers' => new DocumentSet(['data' => [7, 8, 9]]),
			'objects' => new DocumentSet(['data' => [
				new Document(['data' => ['foo' => 'bar']]),
				new Document(['data' => ['baz' => 'dib']])
			]]),
			'deeply' => new Document(['data' => ['nested' => 'object']])
		]]);
		$this->assertEqual('object', $doc->deeply->nested);
		$this->assertTrue($doc->_id instanceof MongoId);

		$result = Exporter::get('create', $doc->export());
		$this->assertTrue($result['create']['_id'] instanceof MongoId);
		$this->assertTrue($result['create']['created'] instanceof MongoDate);
		$this->assertIdentical($time, $result['create']['created']->sec);

		$this->assertIdentical([7, 8, 9], $result['create']['numbers']);
		$expected = [['foo' => 'bar'], ['baz' => 'dib']];
		$this->assertIdentical($expected, $result['create']['objects']);
		$this->assertIdentical(['nested' => 'object'], $result['create']['deeply']);
	}

	public function testCreateWithChangedData() {
		$doc = new Document(['exists' => false, 'data' => [
			'numbers' => new DocumentSet(['data' => [7, 8, 9]]),
			'objects' => new DocumentSet(['data' => [
				new Document(['data' => ['foo' => 'bar']]),
				new Document(['data' => ['baz' => 'dib']])
			]]),
			'deeply' => new Document(['data' => ['nested' => 'object']])
		]]);
		$doc->numbers[] = 10;
		$doc->deeply->nested2 = 'object2';
		$doc->objects[1]->dib = 'gir';

		$expected = [
			'numbers' => [7, 8, 9, 10],
			'objects' => [['foo' => 'bar'], ['baz' => 'dib', 'dib' => 'gir']],
			'deeply' => ['nested' => 'object', 'nested2' => 'object2']
		];
		$result = Exporter::get('create', $doc->export());
		$this->assertEqual(['create'], array_keys($result));
		$this->assertEqual($expected, $result['create']);
	}

	public function testUpdateWithNoChanges() {
		$doc = new Document(['exists' => true, 'data' => [
			'numbers' => new DocumentSet(['exists' => true, 'data' => [7, 8, 9]]),
			'objects' => new DocumentSet(['exists' => true, 'data' => [
				new Document(['exists' => true, 'data' => ['foo' => 'bar']]),
				new Document(['exists' => true, 'data' => ['baz' => 'dib']])
			]]),
			'deeply' => new Document(['exists' => true, 'data' => ['nested' => 'object']])
		]]);
		$this->assertEmpty(Exporter::get('update', $doc->export()));
	}

	public function testUpdateFromResourceLoading() {
		$result = new MockResult([
			'data' => [
				['_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar'],
				['_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo'],
				['_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib']
			]
		]);
		$doc = new DocumentSet(['model' => $this->_model, 'result' => $result]);
		$this->assertEmpty(Exporter::get('update', $doc->export()));
		$this->assertEqual('dib', $doc['6c8f86167675abfabdbf0302']->title);

		$doc['6c8f86167675abfabdbf0302']->title = 'bob';
		$this->assertEqual('6c8f86167675abfabdbf0302', $doc['6c8f86167675abfabdbf0302']->_id);
		$this->assertEqual('bob', $doc['6c8f86167675abfabdbf0302']->title);

		$doc['4c8f86167675abfabdbf0300']->title = 'bill';
		$this->assertEqual('4c8f86167675abfabdbf0300', $doc['4c8f86167675abfabdbf0300']->_id);
		$this->assertEqual('bill', $doc['4c8f86167675abfabdbf0300']->title);

		$expected = Exporter::get('update', $doc->export());
		$this->assertNotEmpty(Exporter::get('update', $doc->export()));
		$this->assertCount(2, $expected['update']);
	}

	public function testUpdateWithSubObjects() {
		$model = $this->_model;
		$exists = true;
		$model::config(['meta' => ['key' => '_id']]);
		$schema = new Schema(['fields' => [
			'forceArray' => ['type' => 'string', 'array' => true],
			'array' => ['type' => 'string', 'array' => true],
			'dictionary' => ['type' => 'string', 'array' => true],
			'numbers' => ['type' => 'integer', 'array' => true],
			'objects' => ['type' => 'object', 'array' => true],
			'deeply' => ['type' => 'object', 'array' => true],
			'foo' => ['type' => 'string']
		]]);
		$config = compact('model', 'schema', 'exists');

		$doc = new Document($config + ['data' => [
			'numbers' => new DocumentSet($config + [
				'data' => [7, 8, 9], 'pathKey' => 'numbers'
			]),
			'objects' => new DocumentSet($config + ['pathKey' => 'objects', 'data' => [
				new Document($config + ['data' => ['foo' => 'bar']]),
				new Document($config + ['data' => ['foo' => 'baz']])
			]]),
			'deeply' => new Document($config + ['pathKey' => 'deeply', 'data' => [
				'nested' => 'object'
			]]),
			'foo' => 'bar'
		]]);
		$doc->dictionary[] = 'A Word';
		$doc->forceArray = 'Word';
		$doc->array = ['one'];
		$doc->field = 'value';
		$doc->objects[1]->foo = 'dib';
		$doc->objects[] = ['foo' => 'diz'];
		$doc->deeply->nested = 'foo';
		$doc->deeply->nestedAgain = 'bar';
		$doc->array = ['one'];
		$doc->newObject = new Document([
			'exists' => false, 'data' => ['subField' => 'subValue']
		]);
		$doc->newObjects = [
			['test' => 'one', 'another' => 'two'],
			['three' => 'four']
		];
		$this->assertEqual('foo', $doc->deeply->nested);
		$this->assertEqual('subValue', $doc->newObject->subField);

		$doc->numbers = [8, 9];
		$doc->numbers[] = 10;
		$doc->numbers->append(11);

		$export = $doc->export();

		$result = Exporter::get('update', $doc->export());
		$expected = [
			'array' => ['one'],
			'dictionary' => ['A Word'],
			'forceArray' => ['Word'],
			'numbers' => [8, 9, 10, 11],
			'newObject' => ['subField' => 'subValue'],
			'newObjects' => [
				['test' => 'one', 'another' => 'two'],
				['three' => 'four']
			],
			'field' => 'value',
			'deeply.nested' => 'foo',
			'deeply.nestedAgain' => 'bar',
			'array' => ['one'],
			'objects.1.foo' => 'dib',
			'objects.2' => ['foo' => 'diz']
		];
		$this->assertEqual($expected, $result['update']);

		$doc->objects[] = ['foo' => 'dob'];

		$exist = $doc->objects->find(
			function ($data) { return (strcmp($data->foo, 'dob') === 0); },
			['collect' => false]
		);
		$this->assertTrue(!empty($exist));
	}

	public function testFieldRemoval() {
		$doc = new Document(['exists' => true, 'data' => [
			'numbers' => new DocumentSet(['data' => [7, 8, 9]]),
			'deeply' => new Document([
				'pathKey' => 'deeply', 'exists' => true, 'data' => ['nested' => 'object']
			]),
			'foo' => 'bar'
		]]);

		unset($doc->numbers);
		$result = Exporter::get('update', $doc->export());
		$this->assertEqual(['numbers' => true], $result['remove']);

		$doc->set(['flagged' => true, 'foo' => 'baz', 'bar' => 'dib']);
		unset($doc->foo, $doc->flagged, $doc->numbers, $doc->deeply->nested);
		$result = Exporter::get('update', $doc->export());
		$expected = ['foo' => true, 'deeply.nested' => true, 'numbers' => true];
		$this->assertEqual($expected, $result['remove']);
		$this->assertEqual(['bar' => 'dib'], $result['update']);
	}

	/**
	 * Tests that when an existing object is attached as a value of another existing object, the
	 * whole sub-object is re-written to the new value.
	 */
	public function testAppendExistingObjects() {
		$doc = new Document(['exists' => true, 'data' => [
			'deeply' => new Document([
				'pathKey' => 'deeply', 'exists' => true, 'data' => ['nested' => 'object']
			]),
			'foo' => 'bar'
		]]);
		$append = new Document(['exists' => true, 'data' => ['foo' => 'bar']]);

		$doc->deeply = $append;
		$result = Exporter::get('update', $doc->export());
		$expected = ['update' => ['deeply' => ['foo' => 'bar']]];
		$this->assertEqual($expected, $result);

		$expected = ['$set' => ['deeply' => ['foo' => 'bar']]];
		$this->assertEqual($expected, Exporter::toCommand($result));

		$doc->sync();
		$doc->append2 = new Document(['exists' => false, 'data' => ['foo' => 'bar']]);
		$expected = ['update' => ['append2' => ['foo' => 'bar']]];
		$this->assertEqual($expected, Exporter::get('update', $doc->export()));
		$doc->sync();

		$this->assertEmpty(Exporter::get('update', $doc->export()));
		$doc->append2->foo = 'baz';
		$doc->append2->bar = 'dib';
		$doc->deeply->nested = true;

		$expected = ['update' => [
			'append2.foo' => 'baz', 'append2.bar' => 'dib', 'deeply.nested' => true
		]];
		$this->assertEqual($expected, Exporter::get('update', $doc->export()));
	}

	public function testNestedObjectCasting() {
		$model = $this->_model;
		$data = ['notifications' => ['foo' => '', 'bar' => '1', 'baz' => 0, 'dib' => 42]];
		$result = $model::schema()->cast(null, null, $data, compact('model'));

		$this->assertIdentical(false, $result['notifications']->foo);
		$this->assertIdentical(true, $result['notifications']->bar);
		$this->assertIdentical(false, $result['notifications']->baz);
		$this->assertIdentical(42, $result['notifications']->dib);
	}

	/**
	 * Tests handling type values based on specified schema settings.
	 */
	public function testTypeCasting() {
		$time = time();
		$data = [
			'_id' => '4c8f86167675abfabd970300',
			'title' => 'Foo',
			'tags' => 'test',
			'comments' => [
				"4c8f86167675abfabdbe0300", "4c8f86167675abfabdbf0300", "4c8f86167675abfabdc00300"
			],
			'empty_array' => [],
			'authors' => '4c8f86167675abfabdb00300',
			'created' => $time,
			'modified' => date('Y-m-d H:i:s', $time),
			'rank_count' => '45',
			'rank' => '3.45688'
		];
		$model = $this->_model;
		$handlers = $this->_handlers;
		$options = compact('model', 'handlers');
		$schema = new Schema(['fields' => $this->_schema]);
		$result = $schema->cast(null, null, $data, $options);
		$this->assertEqual(array_keys($data), array_keys($result->data()));
		$this->assertInstanceOf('MongoId', $result->_id);
		$this->assertEqual('4c8f86167675abfabd970300', (string) $result->_id);

		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $result->comments);
		$this->assertCount(3, $result['comments']);

		$this->assertInstanceOf('MongoId', $result->comments[0]);
		$this->assertInstanceOf('MongoId', $result->comments[1]);
		$this->assertInstanceOf('MongoId', $result->comments[2]);
		$this->assertEqual('4c8f86167675abfabdbe0300', (string) $result->comments[0]);
		$this->assertEqual('4c8f86167675abfabdbf0300', (string) $result->comments[1]);
		$this->assertEqual('4c8f86167675abfabdc00300', (string) $result->comments[2]);

		$this->assertEqual($data['comments'], $result->comments->data());
		$this->assertEqual(['test'], $result->tags->data());
		$this->assertEqual(['4c8f86167675abfabdb00300'], $result->authors->data());
		$this->assertInstanceOf('MongoId', $result->authors[0]);

		$this->assertInstanceOf('MongoDate', $result->modified);
		$this->assertInstanceOf('MongoDate', $result->created);
		$this->assertGreaterThan($result->created->sec, 0);

		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $result->empty_array);

		$this->assertEqual($time, $result->modified->sec);
		$this->assertEqual($time, $result->created->sec);

		$this->assertIdentical(45, $result->rank_count);
		$this->assertIdentical(3.45688, $result->rank);
	}

	/**
	 * Tests handling type values of subdocument arrays based on specified schema settings.
	 */
	public function testTypeCastingSubObjectArrays() {
		$time = time();
		$data = [
			'_id' => '4c8f86167675abfabd970300',
			'accounts' => [
				[
					'_id' => "4fb6e2dd3e91581fe6e75736",
					'name' => 'Foo',
					'created' => $time
				],
				[
					'_id' => "4fb6e2df3e91581fe6e75737",
					'name' => 'Bar',
					'created' => $time
				]
			]
		];
		$model = $this->_model;
		$handlers = $this->_handlers;
		$options = compact('model', 'handlers');
		$schema = new Schema(['fields' => $this->_schema]);
		$result = $schema->cast(null, null, $data, $options);

		$this->assertEqual(array_keys($data), array_keys($result->data()));
		$this->assertInstanceOf('MongoId', $result->_id);
		$this->assertEqual('4c8f86167675abfabd970300', (string) $result->_id);

		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $result->accounts);
		$this->assertCount(2, $result->accounts);

		$id1 = '4fb6e2dd3e91581fe6e75736';
		$id2 = '4fb6e2df3e91581fe6e75737';
		$this->assertInstanceOf('MongoId', $result->accounts[$id1]['_id']);
		$this->assertEqual($id1, (string) $result->accounts[$id1]['_id']);
		$this->assertInstanceOf('MongoId', $result->accounts[$id2]['_id']);
		$this->assertEqual($id2, (string) $result->accounts[$id2]['_id']);

		$this->assertInstanceOf('MongoDate', $result->accounts[$id1]['created']);
		$this->assertGreaterThan($result->accounts[$id1]['created']->sec, 0);
		$this->assertInstanceOf('MongoDate', $result->accounts[$id2]['created']);
		$this->assertGreaterThan($result->accounts[$id2]['created']->sec, 0);
	}

	public function testWithArraySchema() {
		$model = $this->_model;
		$model::schema([
			'_id' => ['type' => 'id'],
			'list' => ['type' => 'string', 'array' => true],
			'obj.foo' => ['type' => 'string'],
			'obj.bar' => ['type' => 'string']
		]);
		$doc = new Document(compact('model'));
		$doc->list[] = ['foo' => '!!', 'bar' => '??'];

		$data = ['list' => [['foo' => '!!', 'bar' => '??']]];
		$this->assertEqual($data, $doc->data());

		$result = Exporter::get('create', $doc->export());
		$this->assertEqual($data, $result['create']);

		$result = Exporter::get('update', $doc->export());
		$this->assertEqual($data, $result['update']);

		$doc = new Document(compact('model'));
		$doc->list = [];
		$doc->list[] = ['foo' => '!!', 'bar' => '??'];

		$data = ['list' => [['foo' => '!!', 'bar' => '??']]];
		$this->assertEqual($data, $doc->data());

		$result = Exporter::get('create', $doc->export());
		$this->assertEqual($result['create'], $data);

		$result = Exporter::get('update', $doc->export());
		$this->assertEqual($result['update'], $data);
	}

	public function testArrayConversion() {
		$time = time();
		$doc = new Document(['data' => [
			'_id' => new MongoId(),
			'date' => new MongoDate($time)
		]]);
		$result = $doc->data();
		$this->assertPattern('/^[a-f0-9]{24}$/', $result['_id']);
		$this->assertEqual($time, $result['date']);
	}

	/**
	 * Allow basic type field to be replaced by a `Document` / `DocumentSet` type.
	 */
	public function testArrayFieldChange() {
		$doc = new Document();
		$doc->someOtherField = 'someValue';
		$doc->list = 'test';
		$doc->sync();
		$doc->list = new DocumentSet();
		$doc->list['id'] = ['foo' => '!!', 'bar' => '??'];
		$data = ['list' => ['id' => ['foo' => '!!', 'bar' => '??']]];

		$result = Exporter::get('update', $doc->export());
		$this->assertEqual($data, $result['update']);

		$doc = new Document();
		$doc->someOtherField = 'someValue';
		$doc->list = new Document(['data' => ['foo' => '!!']]);
		$doc->sync();
		$doc->list = new DocumentSet();
		$doc->list['id'] = ['foo' => '!!', 'bar' => '??'];

		$result = Exporter::get('update', $doc->export());
		$this->assertEqual($data, $result['update']);
	}

	/**
	 * Test that sub-objects are properly casted on creating a new `Document`.
	 */
	public function testSubObjectCastingOnSave() {
		$model = $this->_model;
		$model::schema([
			'_id' => ['type' => 'id'],
			'sub.foo' => ['type' => 'boolean'],
			'bar' => ['type' => 'boolean']
		]);
		$data = ['sub' => ['foo' => 0], 'bar' => 1];
		$doc = new Document(compact('data', 'model'));

		$this->assertIdentical(true, $doc->bar);
		$this->assertIdentical(false, $doc->sub->foo);

		$data = ['sub.foo' => '1', 'bar' => '0'];
		$doc = new Document(compact('data', 'model', 'schema'));

		$this->assertIdentical(false, $doc->bar);
		$this->assertIdentical(true, $doc->sub->foo);
	}

	/**
	 * Tests that a nested key on a previously saved document gets updated properly.
	 */
	public function testExistingNestedKeyOverwrite() {
		$doc = new Document(['model' => $this->_model]);
		$doc->{'this.that'} = 'value1';
		$this->assertEqual(['this' => ['that' => 'value1']], $doc->data());

		$result = Exporter::get('create', $doc->export());
		$this->assertEqual(['create' => ['this' => ['that' => 'value1']]], $result);

		$doc->sync();
		$doc->{'this.that'} = 'value2';
		$this->assertEqual(['this' => ['that' => 'value2']], $doc->data());

		$result = Exporter::get('update', $doc->export());
		$this->assertEqual(['update' => ['this.that' => 'value2']], $result);
	}

	public function testUpdatingArraysAndExporting() {
		$new = new Document(['data' => ['name' => 'Acme, Inc.', 'active' => true]]);

		$expected = ['name' => 'Acme, Inc.', 'active' => true];
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$new->foo = new DocumentSet(['data' => ['bar']]);
		$expected = ['name' => 'Acme, Inc.', 'active' => true, 'foo' => ['bar']];
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
	 * Tests that arrays of nested objects can be appended to and will be updated using the proper
	 * MongoDB operators.
	 */
	public function testAppendingNestedObjectArray() {
		$model = $this->_model;
		$model::schema(false);
		$model::schema([
			'accounts' => ['type' => 'object', 'array' => true],
			'accounts.name' => ['type' => 'string']
		]);
		$doc = new Document(compact('model'));
		$this->assertEqual([], $doc->accounts->data());
		$doc->sync();

		$data = ['name' => 'New account'];
		$doc->accounts[] = new Document(compact('data'));

		$result = Exporter::get('update', $doc->export());
		$expected = ['update' => ['accounts.0' => $data]];
		$this->assertEqual($expected, $result);

		$result = Exporter::toCommand($result);
		$expected = ['$set' => ['accounts.0' => $data]];
		$this->assertEqual($expected, $result);
	}

	/**
	 * @todo Implement me.
	 */
	public function testCreateWithWhitelist() {
	}

	/**
	 * Tests the casting of MongoIds in nested arrays.
	 */
	public function testNestedArrayMongoIdCasting() {

		$articleOneId = new MongoId();
		$bookOneId = new MongoId();
		$bookTwoId = new MongoId();
		$data = [
			'_id' => '4c8f86167675abfabd970300',
			'title' => 'Foo',
			'similar_text' => [
				'articles' => [
					$articleOneId
				],
				'books' => [
					$bookOneId,
					$bookTwoId
				],
				'magazines' => [
					"4fdfb4327a959c4f76000006",
					"4e95f6e098ef47722d000001"
				]
			]
		];

		$model = $this->_model;
		$handlers = $this->_handlers;
		$options = compact('model', 'handlers');

		$schema = new Schema(['fields' => [
			'_id' => ['type' => 'MongoId'],
			'title' => ['type' => 'text'],
			'similar_text' => ['type' => 'array'],
			'similar_text.articles' => ['type' => 'MongoId', 'array' => true],
			'similar_text.books' => ['type' => 'MongoId', 'array' => true],
			'similar_text.magazines' => ['type' => 'MongoId', 'array' => true]
		]]);
		$result = $schema->cast(null, null, $data, $options);
		$this->assertInstanceOf('MongoId', $result['similar_text']['articles'][0]);
		$this->assertInstanceOf('MongoId', $result['similar_text']['books'][0]);
		$this->assertInstanceOf('MongoId', $result['similar_text']['books'][1]);
		$this->assertInstanceOf('MongoId', $result['similar_text']['magazines'][0]);
		$this->assertInstanceOf('MongoId', $result['similar_text']['magazines'][1]);
	}

	/**
	 * Tests that updating arrays of `MongoId`s correctly preserves their type.
	 */
	public function testUpdatingMongoIdArray() {
		$schema = new Schema(['fields' => [
			'list' => ['type' => 'id', 'array' => true]
		]]);

		$doc = new Document(['exists' => true, 'data' => [
			'list' => [new MongoId(), new MongoId(), new MongoId()]
		]]);
		$this->assertEqual([], Exporter::get('update', $doc->export()));

		$doc->list[] = new MongoId();
		$doc->list[] = new MongoId();
		$result = Exporter::get('update', $doc->export());

		$this->assertCount(1, $result);
		$this->assertCount(1, $result['update']);
		$this->assertCount(5, $result['update']['list']);

		for ($i = 0; $i < 5; $i++) {
			$this->assertInstanceOf('MongoId', $result['update']['list'][$i]);
		}

		$doc = new Document(['exists' => true, 'data' => [
			'list' => [new MongoId(), new MongoId(), new MongoId()]
		]]);
		$doc->list = [new MongoId(), new MongoId(), new MongoId()];
		$result = Exporter::get('update', $doc->export());

		$this->assertCount(1, $result);
		$this->assertCount(1, $result['update']);
		$this->assertCount(3, $result['update']['list']);

		for ($i = 0; $i < 3; $i++) {
			$this->assertInstanceOf('MongoId', $result['update']['list'][$i]);
		}
	}

	public function testToDataOnDocumentSet() {
		$data = [
			[
				'_id' => '4c8f86167675abfabd970300',
				'accounts' => [
					[
						'_id' => "4fb6e2dd3e91581fe6e75736",
						'name' => 'Foo1'
					],
					[
						'_id' => "4fb6e2df3e91581fe6e75737",
						'name' => 'Bar1'
					]
				]
			],
			[
				'_id' => '4c8f86167675abfabd970301',
				'accounts' => [
					[
						'_id' => "4fb6e2dd3e91581fe6e75738",
						'name' => 'Foo2'
					],
					[
						'_id' => "4fb6e2df3e91581fe6e75739",
						'name' => 'Bar2'
					]
				]
			]
		];

		$model = $this->_model;
		$handlers = $this->_handlers;
		$options = compact('model', 'handlers');
		$schema = new Schema(['fields' => $this->_schema]);
		$set = $schema->cast(null, null, $data, $options);

		$result = $set->data();
		$accounts = $result['4c8f86167675abfabd970300']['accounts'];
		$this->assertEqual('Foo1', $accounts[0]['name']);
		$this->assertEqual('Bar1', $accounts[1]['name']);
		$accounts = $result['4c8f86167675abfabd970301']['accounts'];
		$this->assertEqual('Foo2', $accounts[0]['name']);
		$this->assertEqual('Bar2', $accounts[1]['name']);

		$result = $set->to('array', ['indexed' => false]);
		$accounts = $result[0]['accounts'];
		$this->assertEqual('Foo1', $accounts[0]['name']);
		$this->assertEqual('Bar1', $accounts[1]['name']);
		$accounts = $result[1]['accounts'];
		$this->assertEqual('Foo2', $accounts[0]['name']);
		$this->assertEqual('Bar2', $accounts[1]['name']);

		$result = $set->to('array', ['indexed' => true]);
		$accounts = $result['4c8f86167675abfabd970300']['accounts'];
		$this->assertEqual('Foo1', $accounts['4fb6e2dd3e91581fe6e75736']['name']);
		$this->assertEqual('Bar1', $accounts['4fb6e2df3e91581fe6e75737']['name']);
		$accounts = $result['4c8f86167675abfabd970301']['accounts'];
		$this->assertEqual('Foo2', $accounts['4fb6e2dd3e91581fe6e75738']['name']);
		$this->assertEqual('Bar2', $accounts['4fb6e2df3e91581fe6e75739']['name']);

		$result = $set->to('array');
		$accounts = $result['4c8f86167675abfabd970300']['accounts'];
		$this->assertEqual('Foo1', $accounts['4fb6e2dd3e91581fe6e75736']['name']);
		$this->assertEqual('Bar1', $accounts['4fb6e2df3e91581fe6e75737']['name']);
		$accounts = $result['4c8f86167675abfabd970301']['accounts'];
		$this->assertEqual('Foo2', $accounts['4fb6e2dd3e91581fe6e75738']['name']);
		$this->assertEqual('Bar2', $accounts['4fb6e2df3e91581fe6e75739']['name']);
	}

	public function testToDataOnDocument() {
		$data = [
			'_id' => '4c8f86167675abfabd970300',
			'accounts' => [
				[
					'_id' => "4fb6e2dd3e91581fe6e75736",
					'name' => 'Foo1'
				],
				[
					'_id' => "4fb6e2df3e91581fe6e75737",
					'name' => 'Bar1'
				]
			]
		];

		$model = $this->_model;
		$handlers = $this->_handlers;
		$options = compact('model', 'handlers');
		$schema = new Schema(['fields' => $this->_schema]);
		$set = $schema->cast(null, null, $data, $options);

		$result = $set->data();
		$accounts = $result['accounts'];
		$this->assertEqual('Foo1', $accounts[0]['name']);
		$this->assertEqual('Bar1', $accounts[1]['name']);

		$result = $set->to('array', ['indexed' => false]);
		$accounts = $result['accounts'];
		$this->assertEqual('Foo1', $accounts[0]['name']);
		$this->assertEqual('Bar1', $accounts[1]['name']);

		$result = $set->to('array', ['indexed' => true]);
		$accounts = $result['accounts'];
		$this->assertEqual('Foo1', $accounts['4fb6e2dd3e91581fe6e75736']['name']);
		$this->assertEqual('Bar1', $accounts['4fb6e2df3e91581fe6e75737']['name']);
	}

	public function testIndexesOnExportingDocumentSet() {
		$schema = new Schema(['fields' => [
			'_id' => ['type' => 'id'],
			'accounts' => ['type' => 'object', 'array' => true],
			'accounts._id' => ['type' => 'id'],
			'accounts.name' => ['type' => 'string']
		]]);

		$data = [
			[
				'_id' => '4c8f86167675abfabd970300',
				'accounts' => [
					[
						'_id' => "4fb6e2dd3e91581fe6e75736",
						'name' => 'Foo1'
					],
					[
						'_id' => "4fb6e2df3e91581fe6e75737",
						'name' => 'Bar1'
					]
				]
			],
			[
				'_id' => '4c8f86167675abfabd970301',
				'accounts' => [
					[
						'_id' => "4fb6e2dd3e91581fe6e75738",
						'name' => 'Foo2'
					],
					[
						'_id' => "4fb6e2df3e91581fe6e75739",
						'name' => 'Bar2'
					]
				]
			]
		];

		$model = $this->_model;

		$array = new DocumentSet(compact('model', 'schema', 'data'));
		$obj = $array['4c8f86167675abfabd970300']->accounts;
		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $obj);
		$obj = $array['4c8f86167675abfabd970301']->accounts;
		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $obj);

		$result = Exporter::get('create', $array->export());
		$this->assertTrue(isset($result['create'][0]));
		$this->assertTrue(isset($result['create'][1]));
		$this->assertFalse(isset($result['create']['4c8f86167675abfabd970300']));
		$this->assertFalse(isset($result['create']['4c8f86167675abfabd970301']));
		$this->assertTrue(isset($result['create'][0]['accounts'][0]));
		$this->assertTrue(isset($result['create'][0]['accounts'][1]));
		$this->assertTrue(isset($result['create'][1]['accounts'][0]));
		$this->assertTrue(isset($result['create'][1]['accounts'][1]));

		$result = Exporter::get('update', $array->export());
		$this->assertTrue(isset($result['update'][0]));
		$this->assertTrue(isset($result['update'][1]));
		$this->assertFalse(isset($result['update']['4c8f86167675abfabd970300']));
		$this->assertFalse(isset($result['update']['4c8f86167675abfabd970301']));
		$this->assertTrue(isset($result['update'][0]['accounts'][0]));
		$this->assertTrue(isset($result['update'][0]['accounts'][1]));
		$this->assertTrue(isset($result['update'][1]['accounts'][0]));
		$this->assertTrue(isset($result['update'][1]['accounts'][1]));
	}

	public function testIndexesOnExportingDocument() {
		$schema = new Schema(['fields' => [
			'_id' => ['type' => 'id'],
			'accounts' => ['type' => 'object', 'array' => true],
			'accounts._id' => ['type' => 'id'],
			'accounts.name' => ['type' => 'string']
		]]);

		$data = [
			'_id' => '4c8f86167675abfabd970300',
			'accounts' => [
				[
					'_id' => "4fb6e2dd3e91581fe6e75736",
					'name' => 'Foo1'
				],
				[
					'_id' => "4fb6e2df3e91581fe6e75737",
					'name' => 'Bar1'
				]
			]
		];

		$model = $this->_model;

		$document = new Document(compact('model', 'schema', 'data'));
		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $document->accounts);

		$export = $document->export();
		$result = Exporter::get('create', $document->export());
		$this->assertTrue(isset($result['create']['accounts'][0]));
		$this->assertTrue(isset($result['create']['accounts'][1]));

		$export['data'] = [];
		$result = Exporter::get('update', $export);
		$this->assertTrue(isset($result['update']['accounts'][0]));
		$this->assertTrue(isset($result['update']['accounts'][1]));
	}

	public function testEmptyArrayAsDocument() {
		$schema = new Schema(['fields' => [
			'_id' => ['type' => 'id'],
			'accounts' => ['type' => 'object', 'array' => true],
			'accounts.name' => ['type' => 'string']
		]]);

		$data = [
			'_id' => '4c8f86167675abfabd970300',
			'accounts' => [[]]
		];

		$model = $this->_model;

		$document = new Document(compact('model', 'schema', 'data'));
		$this->assertInstanceOf('lithium\data\entity\Document', $document->accounts[0]);
	}
}

?>