<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use \stdClass;
use \lithium\data\model\Document;
use \lithium\tests\mocks\data\model\MockDocumentPost;
use \lithium\tests\mocks\data\model\MockDocumentSource;
use \lithium\tests\mocks\data\model\MockDocumentMultipleKey;

class DocumentTest extends \lithium\test\Unit {

	public function testFindAllAndIterate() {
		$document = MockDocumentPost::find('all');

		$expected = array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one');
		$result = $document->current();
		$this->assertEqual($expected, $result);

		$expected = array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two');
		$result = $document->next()->to('array');
		$this->assertEqual($expected, $result);

		$expected = array('id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three');
		$document->next();
		$result = $document->current()->to('array');
		$this->assertEqual($expected, $result);

		$result = $document->next();
		$this->assertTrue(empty($result));

		$expected = array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one');
		$result = $document->rewind()->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testFindOne() {
		$document = MockDocumentPost::find('first');

		$expected = array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two');
		$result = $document->data();
		$this->assertEqual($expected, $result);
	}

	public function testGetFields() {
		$document = MockDocumentPost::find('first');

		$expected = 2;
		$result = $document->id;
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
		$doc->id = 4;
		$doc->name = 'Four';
		$doc->content = 'Lorem ipsum four';

		$expected = array(
			'id' => 4,
			'name' => 'Four',
			'content' => 'Lorem ipsum four'
		);
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	}

	public function testNoItems() {
		$doc = new Document(array('items' => array()));
		$result = $doc->id;
		$this->assertFalse($result);
	}

	public function testWithData() {
		$doc = new Document(array('data' => array(
			array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'),
			array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'),
			array('id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three')
		)));

		$expected = array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one');
		$result = $doc->current();
		$this->assertEqual($expected, $result);

		$expected = array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two');
		$result = $doc->next()->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testExplicitSet() {
		$doc = new Document();
		$doc->set(array('id' => 4));
		$doc->set(array('name' => 'Four'));
		$doc->set(array('content' => 'Lorem ipsum four'));

		$expected = array('id' => 4, 'name' => 'Four', 'content' => 'Lorem ipsum four');
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	}

	public function testSetMultiple() {
		$doc = new Document();
		$doc->set(array(
			array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'),
			array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'),
			array('id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three')
		));
		$expected = array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one');
		$result = $doc->current();
		$this->assertEqual($expected, $result);

		$expected = array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two');
		$result = $doc->next()->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testSetMultipleNested() {
		$doc = new Document();
		$doc->id = 123;
		$doc->type = 'father';
		$doc->set(array('children' => array(
			array('id' => 124, 'type' => 'child', 'children' => null),
			array('id' => 125, 'type' => 'child', 'children' => null)
		)));

		$this->assertEqual('father', $doc->type);

		$this->assertTrue(is_object($doc->children), 'children is not an object');

		$this->assertTrue(
			is_a($doc->children,'\lithium\data\model\Document'),
			'Children is not of the type Document'
		);
		$this->skipIf(
			!is_a($doc->children,'\lithium\data\model\Document'),
			'Children is not of the type Document'
		);

		$expected = array('id' => 124, 'type' => 'child', 'children' => null);
		$result = $doc->children->current();
		$this->assertEqual($expected, $result);

		$expected = array('id' => 125, 'type' => 'child', 'children' => null);
		$result = $doc->children->next()->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testSetNested() {
		$doc = new Document();
		$doc->id = 123;
		$doc->name = 'father';
		$doc->set(array('child' => array('id' => 124, 'name' => 'child')));

		$this->assertEqual('father', $doc->name);

		$this->assertTrue(is_object($doc->child), 'children is not an object');
		$this->assertTrue(is_a($doc->child, '\lithium\data\model\Document'),
			'Child is not of the type Document'
		);
		$this->skipIf(
			!is_a($doc->child,'\lithium\data\model\Document'),
			'Child is not of the type Document'
		);

		$expected = 124;
		$result = $doc->child->id;
		$this->assertEqual($expected, $result);

		$expected = 'child';
		$result = $doc->child->name;
		$this->assertEqual($expected, $result);
	}

	public function testNestedSingle() {
		$doc = new Document();

		$doc->arr1 = array('something' => 'else');
		$doc->arr2 = array('some' => 'noses', 'have' => 'it');

		$this->assertTrue(is_a($doc->arr1, '\lithium\data\model\Document'));
		$this->assertTrue(is_a($doc->arr2, '\lithium\data\model\Document'));
	}

	public function testRewindNoData() {
		$doc = new Document();

		$expected = null;
		$result = $doc->rewind();
		$this->assertEqual($expected, $result);
	}

	public function testRewindData() {
		$doc = new Document(array('items' => array(
			array('id' => 1, 'name' => 'One'),
			array('id' => 2, 'name' => 'Two'),
			array('id' => 3, 'name' => 'Three')
		)));

		$expected = array('id' => 1, 'name' => 'One');
		$result = $doc->rewind()->to('array');
		$this->assertEqual($expected, $result);
	}

	public function testUpdateWithSingleKey() {
		$doc = new Document(array(
			'model' => 'lithium\tests\mocks\data\model\MockDocumentPost',
		));
		$expected = 'id';
		$result = MockDocumentPost::meta('key');
		$this->assertEqual($expected, $result);

		$doc->id = 3;
		$this->assertFalse($doc->exists());

		$doc->invokeMethod('_update', array(12));

		$this->assertTrue($doc->exists());

		$expected = 12;
		$result = $doc->id;
		$this->assertEqual($expected, $result);
	}

	public function testUpdateWithMultipleKeys() {
		$doc = new Document(array(
			'model' => 'lithium\tests\mocks\data\model\MockDocumentMultipleKey',
		));
		$expected = array('id', 'rev');
		$result = MockDocumentMultipleKey::meta('key');
		$this->assertEqual($expected, $result);

		$doc->id = 3;
		$this->assertFalse($doc->exists());

		$doc->invokeMethod('_update', array(array(12, '1-2')));

		$this->assertTrue($doc->exists());

		$expected = 12;
		$result = $doc->id;
		$this->assertEqual($expected, $result);

		$expected = '1-2';
		$result = $doc->rev;
		$this->assertEqual($expected, $result);

	}

	public function testArrayValueNestedDocument() {
		$doc = new Document(array(
			'model' => 'lithium\tests\mocks\data\model\MockDocumentPost',
			'items' => array(
				'id' => 12, 'arr' => array('id' => 33, 'name' => 'stone'), 'name' => 'bird'
			)
		));

		$expected = 12;
		$result = $doc->id;
		$this->assertEqual($expected, $result);

		$expected = 'bird';
		$result = $doc->name;
		$this->assertEqual($expected, $result);

		$this->assertTrue(is_object($doc->arr), 'arr is not an object');
		$this->assertTrue(
			is_a($doc->arr,'\lithium\data\model\Document'),
			'arr is not of the type Document'
		);
		$this->skipIf(
			!is_a($doc->arr,'\lithium\data\model\Document'),
			'arr is not of the type Document'
		);

		$expected = 33;
		$result = $doc->arr->id;
		$this->assertEqual($expected, $result);

		$expected = 'stone';
		$result = $doc->arr->name;
		$this->assertEqual($expected, $result);
	}

	public function testArrayValueGet() {
		$doc = new Document(array(
			'model' => 'lithium\tests\mocks\data\model\MockDocumentPost',
			'items' => array('id' => 12, 'name' => 'Joe', 'sons' => array('Moe', 'Greg'))
		));

		$expected = 12;
		$result = $doc->id;
		$this->assertEqual($expected, $result);

		$expected = 'Joe';
		$result = $doc->name;
		$this->assertEqual($expected, $result);

		$this->assertTrue(is_array($doc->sons), 'arr is not an array');

		$expected = array('Moe', 'Greg');
		$result = $doc->sons;
		$this->assertEqual($expected, $result);
	}

	public function testArrayValueSet() {
		$doc = new Document();

		$doc->id = 12;
		$doc->name = 'Joe';
		$doc->sons = array('Moe', 'Greg',12, 0.3);
		$doc->set(array('daughters' => array('Susan', 'Tinkerbell')));

		$expected = array(
			'id' => 12,
			'name' => 'Joe',
			'sons' => array('Moe', 'Greg', 12, 0.3),
			'daughters' => array('Susan', 'Tinkerbell')
		);
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	}

	public function testCall() {
		$doc = new Document();

		$result = $doc->medicin();
		$this->assertNull($result);

		$doc = new Document(array('model' => 'lithium\tests\mocks\data\model\MockDocumentPost'));

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

	public function testPopulateResourceClose() {
		$resource = new MockDocumentSource();
		$resource->read();
		$doc = new Document(array(
			'model' => 'lithium\tests\mocks\data\model\MockDocumentPost',
			'handle' => new MockDocumentSource(),
			'result' => $resource
		));

		$result = $doc->rewind();
		$this->assertTrue(is_a($result,'\lithium\data\model\Document'));

		$expected = array('id' => 2, 'name' => 'Moe');
		$result = $doc->next()->to('array');
		$this->assertEqual($expected, $result);

		$expected = array('id' => 3, 'name' => 'Roe');
		$result = $doc->next()->to('array');
		$this->assertEqual($expected, $result);

		$result = $doc->next();
		$this->assertNull($result);
	}

	public function testEmptyValues() {
		$doc = new Document(array(
			'model' => 'lithium\tests\mocks\data\model\MockDocumentPost',
			'data' => array(
				'title' => 'Post',
				'content' => 'Lorem Ipsum',
				'parsed' => null,
				'permanent' => false,
			)
		));

		$expected = array(
			'title' => 'Post',
			'content' => 'Lorem Ipsum',
			'parsed' => null,
			'permanent' => false,
		);
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that `Document`s with embedded objects are cast to arrays so as not to cause fatal
	 * errors when traversing via array interfaces.
	 *
	 * @return void
	 */
	public function testObjectIteration() {
		$doc = new Document(array('data' => array(
			(object) array('foo' => 'bar'),
			(object) array('bar' => 'foo')
		)));
		$result = $doc->first()->foo;
		$expected = 'bar';
		$this->assertEqual($expected, $result);

		$result = $doc->next()->bar;
		$expected = 'foo';
		$this->assertEqual($expected, $result);

		$doc = new Document(array('data' => (object) array(
			'first' => array('foo' => 'bar'),
			'second' => array('bar' => 'foo')
		)));
		$result = $doc->first->foo;
	}

	public function testBooleanValues() {
		$doc = new Document();

		$doc->tall = false;
		$doc->fat = true;
		$doc->set(array('hair' => true, 'fast' => false));

		$expected = array('hair', 'fast', 'tall', 'fat');
		$result = array_keys($doc->data());
		$this->assertEqual($expected, $result);
	}

	public function testComplexTypes() {
		$doc = new Document();
		$this->assertFalse($doc->invoke('isComplexType', array(null)));
		$this->assertFalse($doc->invoke('isComplexType', array('')));
		$this->assertFalse($doc->invoke('isComplexType', array(array())));
		$this->assertFalse($doc->invokeMethod('_isComplexType',array(new stdClass())));
	}

	public function testIsset() {
		$doc = new Document(array('data' => array(
				'title' => 'Post',
				'content' => 'Lorem Ipsum',
		)));

		$this->assertTrue(isset($doc->title));
		$this->assertTrue(isset($doc->content));
		$this->assertFalse(isset($doc->body));
	}

	public function testData() {
		$doc = new Document(array(
			'data' => array(
				'title' => 'Post',
				'content' => 'Lorem Ipsum',
				'parsed' => null,
				'permanent' => false
			)
		));

		$expected = array(
			'title' => 'Post',
			'content' => 'Lorem Ipsum',
			'parsed' => null,
			'permanent' => false
		);
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		$expected = 'Post';
		$result = $doc->data('title');
		$this->assertEqual($expected, $result);

		$expected = false;
		$result = $doc->data('permanent');
		$this->assertEqual($expected, $result);

		$doc = new Document();
		$this->assertNull($doc->data('field'));
	}

	public function testUnset() {
		$doc = new Document(array(
			'data' => array(
				'title' => 'Post',
				'content' => 'Lorem Ipsum',
				'parsed' => null,
				'permanent' => false
			)
		));

		$expected = array(
			'title' => 'Post',
			'content' => 'Lorem Ipsum',
			'parsed' => null,
			'permanent' => false
		);
		$result = $doc->data();
		$this->assertEqual($expected, $result);

		unset($expected['title']);
		unset($doc->title);
		$result = $doc->data();
		$this->assertEqual($expected, $result);

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

	public function testErrors() {
		$doc = new Document(array(
			'data' => array(
				'title' => 'Post',
				'content' => 'Lorem Ipsum',
				'parsed' => null,
				'permanent' => false
			)
		));

		$errors = array(
			'title' => 'Too short',
			'parsed' => 'Empty'
		);
		$doc->errors($errors);

		$expected = $errors;
		$result = $doc->errors();
		$this->assertEqual($expected, $result);

		$expected = 'Too short';
		$result = $doc->errors('title');
		$this->assertEqual($expected, $result);

		$doc->errors('title', 'Too generic');
		$expected = 'Too generic';
		$result = $doc->errors('title');
		$this->assertEqual($expected, $result);
	}
}

?>