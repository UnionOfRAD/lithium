<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\entity;

use lithium\data\Connections;
use lithium\data\DocumentSchema;
use lithium\data\entity\Document;
use lithium\data\collection\DocumentSet;
use lithium\tests\mocks\data\MockDocumentSource;
use lithium\tests\mocks\data\model\MockDocumentPost;

class DocumentTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\model\MockDocumentPost';

	public function setUp() {
		$connection = new MockDocumentSource();
		Connections::add('mockconn', ['object' => $connection]);
		MockDocumentPost::config(['meta' => ['connection' => 'mockconn']]);
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockDocumentPost::reset();
	}

	public function testFindAllAndIterate() {
		$set = MockDocumentPost::all();

		$expected = ['_id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'];
		$result = $set->current()->data();
		$this->assertEqual($expected, $result);

		$expected = ['_id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'];
		$result = $set->next()->data();
		$this->assertEqual($expected, $result);

		$expected = ['_id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three'];
		$set->next();
		$result = $set->current()->data();
		$this->assertEqual($expected, $result);

		$result = $set->next();
		$this->assertEmpty($result);

		$expected = ['_id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'];
		$result = $set->rewind()->data();
		$this->assertEqual($expected, $result);
	}

	public function testFindOne() {
		$document = MockDocumentPost::find('first');

		$expected = ['_id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'];
		$result = $document->data();
		$this->assertEqual($expected, $result);
	}

	public function testGetFields() {
		$document = MockDocumentPost::find('first');

		$expected = 2;
		$result = $document->_id;
		$this->assertEqual($expected, $result);

		$expected = 'Two';
		$result = $document->name;
		$this->assertEqual($expected, $result);

		$expected = 'Lorem ipsum two';
		$result = $document->content;
		$this->assertEqual($expected, $result);
	}

	public function testSetField() {
		$doc = new Document();
		$doc->_id = 4;
		$doc->name = 'Four';
		$doc->content = 'Lorem ipsum four';

		$expected = [
			'_id' => 4,
			'name' => 'Four',
			'content' => 'Lorem ipsum four'
		];
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	}

	public function testSyncModified() {
		$doc = new Document();
		$doc->_id = 4;
		$doc->name = 'Four';
		$doc->content = 'Lorem ipsum four';

		$expected = [
			'_id' => true,
			'name' => true,
			'content' => true
		];

		$this->assertEqual($expected, $doc->modified());
		$doc->sync();

		$this->assertEqual(array_fill_keys(array_keys($expected), false), $doc->modified());

		$doc->_id = 5;
		$doc->content = null;
		$doc->new = null;
		$expected = [
			'_id' => true,
			'name' => false,
			'content' => true,
			'new' => true
		];

		$this->assertEqual($expected, $doc->modified());

		$doc = new Document(['model' => $this->_model]);
		$doc->id = 4;
		$doc->name = 'Four';
		$doc->content = 'Lorem ipsum four';
		$doc->array = [1, 2, 3, 4];
		$doc->subdoc = [
			'setting' => 'something',
			'foo' => 'bar',
			'sub' => ['name' => 'A sub sub doc']
		];
		$doc->subdocs = [
			['id' => 1],
			['id' => 2],
			['id' => 3],
			['id' => 4]
		];

		$fields = ['id', 'name', 'content', 'array', 'subdoc', 'subdocs'];
		$expected = array_fill_keys($fields, true);

		$this->assertEqual($expected, $doc->modified());
		$doc->sync();

		$this->assertEqual(array_fill_keys($fields, false), $doc->modified());

		$doc->id = 5;
		$doc->content = null;
		$doc->new = null;
		$doc->subdoc->foo = 'baz';
		$doc->array[] = 5;
		$doc->subdocs[] = ['id' => 5];
		$expected['name'] = false;
		$expected['new'] = true;
		$fields[] = 'new';

		$this->assertEqual($expected, $doc->modified());
		$doc->sync();

		$expected = array_fill_keys($fields, false);

		$this->assertEqual($expected, $doc->modified());
		$doc->sync();

		$doc->subdocs[1]->updated = true;
		$expected['subdocs'] = true;

		$this->assertEqual($expected, $doc->modified());
		$doc->sync();

		$doc->array[1] = ['foo' => 'bar'];
		$expected['array'] = true;

		$this->assertEqual($expected, $doc->modified());
		$doc->sync();
	}

	public function testSetAndCoerceArray() {
		$schema = new DocumentSchema(['fields' => [
			'forceArray' => ['type' => 'string', 'array' => true],
			'array' => ['type' => 'string', 'array' => true],
			'dictionary' => ['type' => 'string', 'array' => true],
			'numbers' => ['type' => 'integer', 'array' => true],
			'objects' => ['type' => 'object', 'array' => true],
			'deeply' => ['type' => 'object', 'array' => true],
			'foo' => ['type' => 'string']
		]]);
		$exists = true;

		$doc = new Document(compact('schema', 'exists'));
		$doc->array = [1, 2, 3];
		$doc->forceArray = 'foo';
		$result = $doc->export();

		$obj = $result['update']['forceArray'];
		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $obj);
		$obj = $result['update']['array'];
		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $obj);
		$this->assertIdentical(['foo'], $result['update']['forceArray']->data());

		$doc->forceArray = false;
		$result = $doc->export();
		$this->assertIdentical([false], $result['update']['forceArray']->data());

		$doc->forceArray = [];
		$result = $doc->export();
		$this->assertIdentical([], $result['update']['forceArray']->data());
	}

	public function testNestedKeyGetSet() {
		$doc = new Document(['model' => $this->_model, 'data' => [
			'name' => 'Bob', 'location' => 'New York, NY', 'profile' => [
				'occupation' => 'Developer', 'likes' => 'PHP', 'dislikes' => 'Java'
			]
		]]);

		$expected = ['occupation' => 'Developer', 'likes' => 'PHP', 'dislikes' => 'Java'];
		$this->assertEqual($expected, $doc->profile->data());
		$this->assertEqual('Java', $doc->profile->dislikes);
		$this->assertEqual('Java', $doc->{'profile.dislikes'});
		$this->assertNull($doc->{'profile.'});
		$this->assertNull($doc->{'profile.foo'});
		$this->assertNull($doc->{'profile.foo.bar'});

		$doc->{'profile.dislikes'} = 'Crystal Reports';
		$this->assertEqual('Crystal Reports', $doc->profile->dislikes);

		$doc->{'profile.foo.bar'} = 'baz';
		$this->assertInstanceOf('lithium\data\entity\Document', $doc->profile->foo);
		$this->assertEqual(['bar' => 'baz'], $doc->profile->foo->data());

		$post = new Document(['model' => $this->_model, 'data' => [
			'title' => 'Blog Post',
			'body' => 'Some post content.',
			'meta' => ['tags' => ['foo', 'bar', 'baz']]
		]]);
		$this->assertEqual(['foo', 'bar', 'baz'], $post->meta->tags->data());

		$post->{'meta.tags'}[] = 'dib';
		$this->assertEqual(['foo', 'bar', 'baz', 'dib'], $post->meta->tags->data());
	}

	public function testNoItems() {
		$doc = new Document(['model' => $this->_model, 'data' => []]);
		$result = $doc->_id;
		$this->assertEmpty($result);
	}

	public function testWithData() {
		$doc = new DocumentSet(['model' => $this->_model, 'data' => [
			['_id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'],
			['_id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'],
			['_id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three']
		]]);

		$expected = ['_id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'];
		$result = $doc->current()->data();
		$this->assertEqual($expected, $result);

		$expected = ['_id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'];
		$result = $doc->next()->data();
		$this->assertEqual($expected, $result);
	}

	public function testExplicitSet() {
		$doc = new Document();
		$doc->set(['_id' => 4]);
		$doc->set(['name' => 'Four']);
		$doc->set(['content' => 'Lorem ipsum four']);

		$expected = ['_id' => 4, 'name' => 'Four', 'content' => 'Lorem ipsum four'];
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	}

	public function testSetMultiple() {
		$doc = new DocumentSet(['model' => $this->_model]);
		$doc->set([
			['_id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'],
			['_id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'],
			['_id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three']
		]);
		$expected = ['_id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'];
		return;
		$result = $doc->current()->data();
		$this->assertEqual($expected, $result);

		$expected = ['_id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'];
		$result = $doc->next()->data();
		$this->assertEqual($expected, $result);
	}

	public function testSetMultipleNested() {
		$doc = new Document(['model' => $this->_model]);
		$doc->_id = 123;
		$doc->type = 'father';

		$doc->set(['children' => [
			['_id' => 124, 'type' => 'child', 'children' => null],
			['_id' => 125, 'type' => 'child', 'children' => null]
		]]);

		$this->assertEqual('father', $doc->type);
		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $doc->children);

		$expected = ['_id' => 124, 'type' => 'child', 'children' => null];
		$result = $doc->children[124]->data();
		$this->assertEqual($expected, $result);

		$expected = ['_id' => 125, 'type' => 'child', 'children' => null];
		$result = $doc->children[125]->data();
		// @todo Make $result = $doc->children->{1}->data(); work as well (and ...->{'1'}->...)
		$this->assertEqual($expected, $result);
	}

	public function testSetNested() {
		$doc = new Document(['model' => $this->_model]);
		$doc->_id = 123;
		$doc->name = 'father';
		$doc->set(['child' => ['_id' => 124, 'name' => 'child']]);

		$this->assertEqual('father', $doc->name);

		$this->assertInternalType('object', $doc->child, 'children is not an object');
		$this->assertInstanceOf('lithium\data\entity\Document', $doc->child);
		$this->skipIf(!$doc->child instanceof Document, 'Child is not of the type Document');

		$expected = 124;
		$result = $doc->child->_id;
		$this->assertEqual($expected, $result);

		$expected = 'child';
		$result = $doc->child->name;
		$this->assertEqual($expected, $result);
	}

	public function testNestedSingle() {
		$doc = new Document(['model' => $this->_model]);

		$doc->arr1 = ['something' => 'else'];
		$doc->arr2 = ['some' => 'noses', 'have' => 'it'];

		$this->assertInstanceOf('lithium\data\entity\Document', $doc->arr1);
		$this->assertInstanceOf('lithium\data\entity\Document', $doc->arr2);
	}

	public function testRewindNoData() {
		$doc = new DocumentSet();
		$result = $doc->rewind();
		$this->assertFalse($result);
	}

	public function testRewindData() {
		$doc = new DocumentSet(['model' => $this->_model, 'data' => [
			['_id' => 1, 'name' => 'One'],
			['_id' => 2, 'name' => 'Two'],
			['_id' => 3, 'name' => 'Three']
		]]);

		$expected = ['_id' => 1, 'name' => 'One'];
		$result = $doc->rewind()->data();
		$this->assertEqual($expected, $result);
	}

	public function testUpdateWithSingleKey() {
		$doc = new Document(['model' => $this->_model]);
		$result = MockDocumentPost::meta('key');
		$this->assertEqual('_id', $result);

		$doc->_id = 3;
		$this->assertFalse($doc->exists());

		$doc->sync(12);
		$this->assertTrue($doc->exists());
		$this->assertEqual(12, $doc->_id);
	}

	public function testUpdateWithMultipleKeys() {
		$model = 'lithium\tests\mocks\data\model\MockDocumentMultipleKey';
		$model::config(['meta' => ['key' => ['_id', 'rev'], 'foo' => true]]);
		$doc = new Document(compact('model'));

		$result = $model::meta('key');
		$this->assertEqual(['_id', 'rev'], $result);

		$doc->_id = 3;
		$this->assertFalse($doc->exists());

		$doc->sync([12, '1-2']);
		$this->assertTrue($doc->exists());
		$this->assertEqual(12, $doc->_id);
		$this->assertEqual('1-2', $doc->rev);
	}

	public function testArrayValueNestedDocument() {
		$doc = new Document([
			'model' => 'lithium\tests\mocks\data\model\MockDocumentPost',
			'data' => [
				'_id' => 12, 'arr' => ['_id' => 33, 'name' => 'stone'], 'name' => 'bird'
			]
		]);

		$this->assertEqual(12, $doc->_id);
		$this->assertEqual('bird', $doc->name);

		$this->assertInternalType('object', $doc->arr, 'arr is not an object');
		$this->assertInstanceOf('lithium\data\entity\Document', $doc->arr);
		$this->skipIf(!$doc->arr instanceof Document, 'arr is not of the type Document');

		$this->assertEqual(33, $doc->arr->_id);
		$this->assertEqual('stone', $doc->arr->name);
	}

	public function testArrayValueGet() {
		$doc = new Document([
			'model' => $this->_model,
			'data' => ['_id' => 12, 'name' => 'Joe', 'sons' => ['Moe', 'Greg']]
		]);

		$this->assertEqual(12, $doc->_id);
		$this->assertEqual('Joe', $doc->name);

		$this->assertInstanceOf('lithium\data\collection\DocumentSet', $doc->sons);
		$this->assertEqual(['Moe', 'Greg'], $doc->sons->data());
	}

	public function testArrayValueSet() {
		$doc = new Document(['model' => $this->_model]);

		$doc->_id = 12;
		$doc->name = 'Joe';
		$doc->sons = ['Moe', 'Greg', 12, 0.3];
		$doc->set(['daughters' => ['Susan', 'Tinkerbell']]);

		$expected = [
			'_id' => 12,
			'name' => 'Joe',
			'sons' => ['Moe', 'Greg', 12, 0.3],
			'daughters' => ['Susan', 'Tinkerbell']
		];
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	}

	public function testInvalidCall() {
		$doc = new Document();

		$this->assertException("No model bound to call `medicin`.", function() use ($doc) {
			$doc->medicin();
		});
	}

	public function testCall() {
		$doc = new Document(['model' => 'lithium\tests\mocks\data\model\MockDocumentPost']);

		$expected = 'lithium';
		$result = $doc->medicin();
		$this->assertEqual($expected, $result);

		$result = $doc->ret();
		$this->assertNull($result);

		$expected = 'nose';
		$result = $doc->ret('nose');
		$this->assertEqual($expected, $result);

		$expected = 'job';
		$result = $doc->ret('nose','job');
		$this->assertEqual($expected, $result);
	}

	public function testEmptyValues() {
		$doc = new Document([
			'model' => 'lithium\tests\mocks\data\model\MockDocumentPost',
			'data' => [
				'title' => 'Post',
				'content' => 'Lorem Ipsum',
				'parsed' => null,
				'permanent' => false
			]
		]);

		$expected = [
			'title' => 'Post',
			'content' => 'Lorem Ipsum',
			'parsed' => null,
			'permanent' => false
		];
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	}

	public function testBooleanValues() {
		$doc = new Document(['model' => $this->_model]);

		$doc->tall = false;
		$doc->fat = true;
		$doc->set(['hair' => true, 'fast' => false]);

		$expected = ['fast', 'fat', 'hair', 'tall'];
		$result = array_keys($doc->data());
		sort($result);
		$this->assertEqual($expected, $result);
	}

	public function testIsset() {
		$doc = new Document(['data' => [
			'title' => 'Post',
			'content' => 'Lorem Ipsum',
			'extra' => [
				'foo' => 'bar'
			]
		]]);

		$this->assertTrue(isset($doc->title));
		$this->assertTrue(isset($doc->content));
		$this->assertFalse(isset($doc->body));

		$key = 'extra.foo';
		$this->assertTrue(isset($doc->{$key}));

		$key = 'extra.baz';
		$this->assertFalse(isset($doc->{$key}));
	}

	public function testData() {
		$doc = new Document([
			'data' => [
				'title' => 'Post',
				'content' => 'Lorem Ipsum',
				'parsed' => null,
				'permanent' => false
			]
		]);

		$expected = [
			'title' => 'Post',
			'content' => 'Lorem Ipsum',
			'parsed' => null,
			'permanent' => false
		];
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		$expected = 'Post';
		$result = $doc->data('title');
		$this->assertEqual($expected, $result);

		$result = $doc->data('permanent');
		$this->assertFalse($result);

		$doc = new Document();
		$this->assertNull($doc->data('field'));
	}

	public function testUnset() {
		$doc = new Document([
			'data' => [
				'title' => 'Post',
				'content' => 'Lorem Ipsum',
				'parsed' => null,
				'permanent' => false
			]
		]);

		$expected = [
			'title' => 'Post',
			'content' => 'Lorem Ipsum',
			'parsed' => null,
			'permanent' => false
		];
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		unset($expected['title']);
		unset($doc->title);
		$this->assertEqual($expected, $doc->data());

		unset($expected['parsed']);
		unset($doc->parsed);
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		unset($expected['permanent']);
		unset($doc->permanent);
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		unset($doc->none);
	}

	public function testUnsetNested() {
		$data = [
			'a' => 1,
			'b' => [
				'ba' => 21,
				'bb' => 22
			],
			'c' => [
				'ca' => 31,
				'cb' => [
					'cba' => 321,
					'cbb' => 322
				]
			],
			'd' => [
				'da' => 41
			]
		];
		$model = $this->_model;

		$doc = new Document(compact('model', 'data'));
		$expected = $data;
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		unset($doc->c->cb->cba);
		unset($expected['c']['cb']['cba']);
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		unset($doc->b->bb);
		unset($expected['b']['bb']);
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		unset($doc->a);
		unset($expected['a']);
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		unset($doc->d);
		unset($expected['d']);
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		$exportedRoot = $doc->export();
		$this->assertEqual(['a' => true, 'd' => true], $exportedRoot['remove']);

		$exportedB = $doc->b->export();
		$this->assertEqual(['bb' => true], $exportedB['remove']);

		$exportedCCB = $doc->c->cb->export();
		$this->assertEqual(['cba' => true], $exportedCCB['remove']);
	}

	public function testErrors() {
		$doc = new Document(['data' => [
			'title' => 'Post',
			'content' => 'Lorem Ipsum',
			'parsed' => null,
			'permanent' => false
		]]);

		$errors = ['title' => 'Too short', 'parsed' => 'Empty'];
		$doc->errors($errors);

		$expected = $errors;
		$result = $doc->errors();
		$this->assertEqual($expected, $result);

		$expected = 'Too short';
		$result = $doc->errors('title');
		$this->assertEqual($expected, $result);

		/* Errors are appended so, both errors are expected to be in an array */
		$doc->errors('title', 'Too generic');
		$expected = ['Too short', 'Too generic'];
		$result = $doc->errors('title');
		$this->assertEqual($expected, $result);
	}

	public function testDocumentNesting() {
		$model = $this->_model;
		$data = ['top' => 'level', 'second' => ['level' => 'of data']];
		$doc = new Document(compact('model', 'data'));

		$this->assertTrue(isset($doc->top));
		$this->assertTrue(isset($doc->second->level));
		$this->assertInstanceOf('lithium\data\entity\Document', $doc->second);

		$this->assertEqual('level', $doc->top);
		$this->assertEqual('of data', $doc->second->level);
	}

	public function testPropertyIteration() {
		$doc = new Document(['data' => ['foo' => 'bar', 'baz' => 'dib']]);
		$keys = [null, 'foo', 'baz'];
		$values = [null, 'bar', 'dib'];

		foreach ($doc as $key => $value) {
			$this->assertEqual(next($keys), $key);
			$this->assertEqual(next($values), $value);
		}
		reset($keys);
		reset($values);
		foreach ($doc as $key => $value) {
			$this->assertEqual(next($keys), $key);
			$this->assertEqual(next($values), $value);
		}
	}

	public function testExport() {
		$data = ['foo' => 'bar', 'baz' => 'dib'];
		$doc = new Document(compact('data') + ['exists' => false]);

		$expected = [
			'data' => ['foo' => 'bar', 'baz' => 'dib'],
			'update' => ['foo' => 'bar', 'baz' => 'dib'],
			'remove' => [],
			'increment' => [],
			'key' => '',
			'exists' => false
		];
		$this->assertEqual($expected, $doc->export());
	}

	/**
	 * Tests that documents nested within existing documents also exist, and vice versa.
	 */
	public function testNestedObjectExistence() {
		$model = $this->_model;
		$data = [
			'foo' => ['bar' => 'bar', 'baz' => 'dib'],
			'deeply' => ['nested' => ['object' => ['should' => 'exist']]]
		];
		$doc = new Document(compact('model', 'data') + ['exists' => false]);

		$this->assertFalse($doc->exists());
		$this->assertFalse($doc->foo->exists());

		$doc = new Document(compact('model', 'data') + ['exists' => true]);
		$this->assertTrue($doc->exists());
		$this->assertTrue($doc->foo->exists());
		$this->assertTrue($doc->deeply->nested->object->exists());

		$doc = new Document(compact('model', 'data') + ['exists' => true]);
		$subDoc = new Document(['data' => ['bar' => 'stuff']]);

		$this->assertTrue($doc->foo->exists());
		$this->assertFalse($subDoc->exists());

		$doc->foo = $subDoc;
		$this->assertTrue($doc->exists());
		$this->assertFalse($doc->foo->exists());

		$doc->sync();
		$this->assertTrue($doc->foo->exists());
	}

	/**
	 * Tests that a modified `Document` exports the proper fields in a newly-appended nested
	 * `Document`.
	 */
	public function testModifiedExport() {
		$model = $this->_model;
		$data = ['foo' => 'bar', 'baz' => 'dib'];
		$doc = new Document(compact('model', 'data') + ['exists' => false]);

		$doc->nested = ['more' => 'data'];
		$newData = $doc->export();

		$expected = ['foo' => 'bar', 'baz' => 'dib', 'nested.more' => 'data'];
		$this->assertFalse($newData['exists']);
		$this->assertEqual(['foo' => 'bar', 'baz' => 'dib'], $newData['data']);
		$this->assertCount(3, $newData['update']);
		$this->assertInstanceOf('lithium\data\entity\Document', $newData['update']['nested']);

		$result = $newData['update']['nested']->export();
		$this->assertFalse($result['exists']);
		$this->assertEqual(['more' => 'data'], $result['data']);
		$this->assertEqual(['more' => 'data'], $result['update']);
		$this->assertEqual('nested', $result['key']);

		$doc = new Document(compact('model') + ['exists' => true, 'data' => [
			'foo' => 'bar', 'baz' => 'dib'
		]]);

		$result = $doc->export();
		$this->assertEqual($result['data'], $result['update']);

		$doc->nested = ['more' => 'data'];
		$this->assertEqual('data', $doc->nested->more);

		$modified = $doc->export();
		$this->assertTrue($modified['exists']);
		$this->assertEqual(['foo' => 'bar', 'baz' => 'dib'], $modified['data']);
		$this->assertEqual(['foo', 'baz', 'nested'], array_keys($modified['update']));
		$this->assertNull($modified['key']);

		$nested = $modified['update']['nested']->export();
		$this->assertFalse($nested['exists']);
		$this->assertEqual(['more' => 'data'], $nested['data']);
		$this->assertEqual('nested', $nested['key']);

		$doc->sync();
		$result = $doc->export();
		$this->assertEqual($result['data'], $result['update']);

		$doc->more = 'cowbell';
		$doc->nested->evenMore = 'cowbell';
		$modified = $doc->export();

		$expected = ['more' => 'cowbell'] + $modified['data'];
		$this->assertEqual($expected, $modified['update']);
		$this->assertEqual(['foo', 'baz', 'nested'], array_keys($modified['data']));
		$this->assertEqual('bar', $modified['data']['foo']);
		$this->assertEqual('dib', $modified['data']['baz']);
		$this->assertTrue($modified['exists']);

		$nested = $modified['data']['nested']->export();
		$this->assertTrue($nested['exists']);
		$this->assertEqual(['more' => 'data'], $nested['data']);
		$this->assertEqual(['evenMore' => 'cowbell'] + $nested['data'], $nested['update']);
		$this->assertEqual('nested', $nested['key']);

		$doc->sync();
		$doc->nested->evenMore = 'foo!';
		$modified = $doc->export();
		$this->assertEqual($modified['data'], $modified['update']);

		$nested = $modified['data']['nested']->export();
		$this->assertEqual(['evenMore' => 'foo!'] + $nested['data'], $nested['update']);
	}

	public function testArrayInterface() {
		$doc = new Document();
		$doc->field = 'value';
		$this->assertEqual('value', $doc['field']);

		$doc['field'] = 'newvalue';
		$this->assertEqual('newvalue', $doc->field);

		unset($doc['field']);
		$this->assertNull($doc->field);
	}

	/**
	 * Tests that unassigned fields with default schema values are auto-populated at access time.
	 */
	public function testSchemaValueInitialization() {
		$doc = new Document(['schema' => new DocumentSchema(['fields' => [
			'foo' => ['type' => 'string', 'default' => 'bar']
		]])]);
		$this->assertEmpty($doc->data());

		$this->assertEqual('bar', $doc->foo);
		$this->assertEqual(['foo' => 'bar'], $doc->data());
	}

	public function testInitializationWithNestedFields() {
		$doc = new Document(['model' => $this->_model, 'data' => [
			'simple' => 'value',
			'nested.foo' => 'first',
			'nested.bar' => 'second',
			'really.nested.key' => 'value'
		]]);
		$this->assertEqual('value', $doc->simple);
		$this->assertEqual('first', $doc->nested->foo);
		$this->assertEqual('second', $doc->nested->bar);
		$this->assertEqual('value', $doc->really->nested->key);
		$result = array_keys($doc->data());
		sort($result);
		$this->assertEqual(['nested', 'really', 'simple'], $result);
	}

	public function testWithArraySchemaReusedName() {
		$model = $this->_model;
		$schema = new DocumentSchema(['fields' => [
			'_id' => ['type' => 'id'],
			'bar' => ['array' => true],
			'foo' => ['type' => 'object', 'array' => true],
			'foo.foo' => ['type' => 'integer'],
			'foo.bar' => ['type' => 'integer']
		]]);
		$doc = new Document(compact('model', 'schema'));
		$doc->foo[] = ['foo' => 1, 'bar' => 100];

		$expected = ['foo' => [['foo' => 1, 'bar' => 100]]];
		$this->assertEqual($expected, $doc->data());
	}

	public function testIdGetDoesNotSet() {
		$document = MockDocumentPost::create();
		$message = 'The `_id` key should not be set.';
		$this->assertFalse(array_key_exists('_id', $document->data()), $message);

		$document->_id === "";
		$this->assertFalse(array_key_exists('_id', $document->data()), $message);
	}

	/**
	 * Ensures that the data returned from the `data()` method matches the
	 * internal state of the object.
	 */
	public function testEnsureArrayExportFidelity() {
		$data = [
			'department_3' => 0,
			4 => 0,
			5 => 0,
			6 => 0,
			'6x' => 0,
			7 => 0,
			8 => 0,
			10 => 0,
			12 => 0
		];
		$doc = new Document(compact('data'));
		$this->assertIdentical($data, $doc->data());
	}

	public function testHandlers() {
		$model = $this->_model;
		$schema = new DocumentSchema([
			'fields' => [
				'_id' => ['type' => 'id'],
				'date' => ['type' => 'date']
			],
			'types' => [
				'date' => 'date'
			],
			'handlers' => [
				'date' => function($v) { return (object) $v; },
			]
		]);
		$handlers = [
			'stdClass' => function($value) { return date('d/m/Y H:i', strtotime($value->scalar)); }
		];
		$array = new Document(compact('model', 'schema', 'handlers') + [
			'data' => [
				'_id' => '2',
				'date' => '2013-06-06 13:00:00'
			]
		]);

		$expected = ['_id' => '2', 'date' => '06/06/2013 13:00'];
		$this->assertIdentical($expected, $array->to('array', ['indexed' => false]));
	}
}

?>