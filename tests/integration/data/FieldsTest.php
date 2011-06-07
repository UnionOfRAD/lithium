<?php

namespace lithium\tests\integration\data;

use lithium\data\Connections;
use lithium\data\Entity;
use lithium\tests\mocks\data\Company;

class FieldsTest extends \lithium\test\Unit {

	public function setUp() {
		Company::config();
	}

	public function tearDown() {
		Company::remove();
	}

	public function skip() {
		$isAvailable = (
			Connections::get('test', array('config' => true)) &&
			Connections::get('test')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available");
	}

	public function testSingleField() {
		$new = Company::create(array('name' => 'Acme, Inc.'));
		$key = Company::meta('key');
		$new->save();
		$id = is_object($new->{$key}) ? (string) $new->{$key} : $new->{$key};

		$entity = Company::first($id);

		$this->assertTrue($entity instanceof Entity);
		$this->skipIf(!$entity instanceof Entity, 'Queried object is not an entity.');

		$expected = array($key => $id, 'name' => 'Acme, Inc.');
		$result = $entity->data();
		$this->assertEqual($expected, $result);

		$entity = Company::first(array('fields' => array($key)));

		$this->assertTrue($entity instanceof Entity);
		$this->skipIf(!$entity instanceof Entity, 'Queried object is not an entity.');

		$expected = array($key => $id);
		$result = $entity->data();
		$this->assertEqual($expected, $result);

		$entity = Company::find('first',array(
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