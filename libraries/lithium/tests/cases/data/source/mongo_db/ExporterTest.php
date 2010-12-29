<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */

namespace lithium\tests\cases\data\source\mongo_db;

use MongoId;
use MongoDate;
use lithium\data\entity\Document;
use lithium\data\collection\DocumentArray;
use lithium\data\source\mongo_db\Exporter;

class ExporterTest extends \lithium\test\Unit {

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
		$doc = new Document(array('exists' => true, 'data' => array(
			'numbers' => new DocumentArray(array('data' => array(7, 8, 9))),
			'deeply' => new Document(array(
				'pathKey' => 'deeply', 'exists' => true, 'data' => array('nested' => 'object')
			)),
			'foo' => 'bar'
		)));
		$doc->field = 'value';
		$doc->deeply->nested = 'foo';
		$doc->newObject = new Document(array(
			'exists' => false, 'data' => array('subField' => 'subValue')
		));

		$this->assertEqual('foo', $doc->deeply->nested);
		$this->assertEqual('subValue', $doc->newObject->subField);

		$result = Exporter::get('update', $doc->export());
		$this->assertFalse(isset($result['update']['foo']));
		$this->assertEqual('value', $result['update']['field']);
		$this->assertEqual(array('subField' => 'subValue'), $result['update']['newObject']);
		$this->assertEqual('foo', $result['update']['deeply.nested']);
	}

	public function testFieldRemoval() {
		$doc = new Document(array('exists' => true, 'data' => array(
			'numbers' => new DocumentArray(array('data' => array(7, 8, 9))),
			'deeply' => new Document(array(
				'pathKey' => 'deeply', 'exists' => true, 'data' => array('nested' => 'object')
			)),
			'foo' => 'bar'
		)));
		$doc->set(array('flagged' => true, 'foo' => 'baz', 'bar' => 'dib'));
		unset($doc->foo, $doc->flagged, $doc->numbers, $doc->deeply->nested);

		$result = Exporter::get('update', $doc->export());
		$expected = array(
			'foo' => true, 'flagged' => true, 'numbers' => true, 'deeply.nested' => true
		);
		$this->assertEqual($expected, $result['remove']);
		$this->assertEqual(array('bar' => 'dib'), $result['update']);
	}

	/**
	 * @todo Implement me.
	 */
	public function testCreateWithWhitelist() {
	}
}

?>