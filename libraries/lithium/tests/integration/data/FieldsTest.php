<?php

namespace lithium\tests\integration\data;

use \lithium\data\Connections;

class MockCompany extends \lithium\data\Model {

	protected $_meta = array(
		'source' => 'companies',
		'connection' => 'test');
}


class FieldsTest extends \lithium\test\Unit {

	public function setUp() {
		MockCompany::config();
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
		$new->save();
		$id = $new->id;

		$entity = MockCompany::first($id);

		$isRecord = $entity instanceof \lithium\data\entity\Record;
		$this->assertTrue($isRecord);
		$this->skipIf(!$isRecord, 'Is not record');

		$expected = array('id' => $id, 'name' => 'Acme, Inc.');
		$result = $entity->data();
		$this->assertEqual($expected, $result);

		$entity = MockCompany::first(array('fields' => array('id')));

		$isRecord = $entity instanceof \lithium\data\entity\Record;
		$this->assertTrue($isRecord);
		$this->skipIf(!$isRecord, 'Is not record');

		$expected = array('id' => $id);
		$result = $entity->data();
		$this->assertEqual($expected, $result);

		$entity = MockCompany::find('first',array(
			'conditions' => array('id' => $id),
			'fields' => array('id')
		));
		$isRecord = $entity instanceof \lithium\data\entity\Record;
		$this->assertTrue($isRecord);
		$this->skipIf(!$isRecord, 'Is not record');

		$result = $entity->save();
		$this->assertTrue($result);

		$new->delete();
	}
}

?>