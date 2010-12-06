<?php

namespace lithium\tests\integration\data;

use lithium\data\Connections;
use lithium\data\Entity;

class MockCompany extends \lithium\data\Model {

	protected $_meta = array(
		'source' => 'companies',
		'connection' => 'test'
	);
}


class FieldsTest extends \lithium\test\Unit {

	public function setUp() {
		MockCompany::config();
	}

	public function tearDown() {
		MockCompany::remove();
	}

	public function skip() {
		$isAvailable = (
			Connections::get('test', array('config' => true)) &&
			Connections::get('test')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available");
	}

	public function testSingleField() {
		$new = MockCompany::create(array('name' => 'Acme, Inc.'));
		$key = MockCompany::meta('key');
		$new->save();
		$id = is_object($new->{$key}) ? (string) $new->{$key} : $new->{$key};

		$entity = MockCompany::first($id);

		$this->assertTrue($entity instanceof Entity);
		$this->skipIf(!$entity instanceof Entity, 'Queried object is not an entity.');

		$expected = array($key => $id, 'name' => 'Acme, Inc.');
		$result = $entity->data();
		$this->assertEqual($expected, $result);

		$entity = MockCompany::first(array('fields' => array($key)));

		$this->assertTrue($entity instanceof Entity);
		$this->skipIf(!$entity instanceof Entity, 'Queried object is not an entity.');

		$expected = array($key => $id);
		$result = $entity->data();
		$this->assertEqual($expected, $result);

		$entity = MockCompany::find('first',array(
			'conditions' => array($key => $id),
			'fields' => array($key)
		));
		$this->assertTrue($entity instanceof Entity);
		$this->skipIf(!$entity instanceof Entity, 'Queried object is not an entity.');

		$result = $entity->save();
		$this->assertTrue($result);
		$new->delete();
	}
}

?>