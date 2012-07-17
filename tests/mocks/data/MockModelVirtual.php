<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

use lithium\tests\mocks\data\source\database\adapter\MockAdapter;

class MockModelVirtual extends \lithium\data\Model {

	protected $_meta = array(
		'connection' => false
	);
	
	public static $_properties = array(
		'fielda' => 'FieldA',
		'fieldb' => array('FieldB', 'get' => 'getFieldZ', 'set' => false),
		'field_c' => array('get' => 'myTest')
	);
	
	public function getFieldA($entity) {
		return $entity->bar;
	}
	
	public function setFieldA($entity, $value) {
		$entity->bar = $value;
	}
	
	public function issetFieldA($entity) {
		return isset($entity->bar);
	}
	
	public function issetFieldB($entity) {
		return $entity->bar;
	}
	
	public function getFieldZ($entity) {
		return $entity->bar;
	}
	
	public function myTest($entity) {
		return $entity->bar;
	}
	
	public function issetfield_c($entity) {
		return isset($entity->bar);
	}
	
	// must be missing public function setfield_c
}

?>