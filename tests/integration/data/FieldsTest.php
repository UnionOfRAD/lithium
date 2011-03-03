<?php

namespace lithium\tests\integration\data;

use lithium\data\Connections;
use lithium\data\Entity;
use lithium\tests\mocks\data\MockEmployees;
use lithium\tests\mocks\data\MockCompany;

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

		$expected = array(
			$key => $id, 'name' => 'Acme, Inc.', 'active' => null,
			'created' => null, 'modified' => null
		);
		$result = $entity->data();
		$this->assertEqual($expected, $result);

		$entity = MockCompany::first(array(
			'conditions' => array($key => $id),
			'fields' => array($key)
		));

		$this->assertTrue($entity instanceof Entity);
		$this->skipIf(!$entity instanceof Entity, 'Queried object is not an entity.');

		$expected = array($key => $id);
		$result = $entity->data();
		$this->assertEqual($expected, $result);

		$entity = Company::find('first',array(
			'conditions' => array($key => $id),
			'fields' => array($key, 'name')
		));
		$this->assertTrue($entity instanceof Entity);
		$this->skipIf(!$entity instanceof Entity, 'Queried object is not an entity.');

		$entity->name = 'Acme, Incorporated';
		$result = $entity->save();
		$this->assertTrue($result);

		$entity = MockCompany::find('first',array(
			'conditions' => array($key => $id),
			'fields' => array($key, 'name')
		));
		$this->assertEqual($entity->name, 'Acme, Incorporated');
		$new->delete();
	}

	function testFieldsWithJoins() {
		$new = MockCompany::create(array('name' => 'Acme, Inc.'));
		$cKey = MockCompany::meta('key');
		$result = $new->save();
		$cId = (string) $new->{$cKey};

		$this->skipIf(!$result, 'Could not save MockCompany');

		$new = MockEmployees::create(array(
			'company_id' => $cId,
			'name' => 'John Doe'
		));
		$eKey = MockEmployees::meta('key');
		$result = $new->save();
		$this->skipIf(!$result, 'Could not save MockEmployee');
		$eId = (string) $new->{$eKey};
		
		$entity = MockCompany::first(array(
			'with' => 'Employee',
			'conditions' => array(
				'MockCompany.id' => $cId
			),
			'fields' => array(
				'MockCompany' => array('id', 'name'),
				'Employee' => array('id', 'name')
			)
		));
		$expected = array(
			'id' => $cId,
			'name' => 'Acme, Inc.',
			'employees' =>
			array (
				0 => array (
					'id' => $eId,
					'name' => 'John Doe',
				),
			)
		);
		$this->assertEqual($expected, $entity->data());
	}
}

?>