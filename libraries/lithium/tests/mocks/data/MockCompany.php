<?php

namespace lithium\tests\mocks\data;

class MockCompany extends \lithium\data\Model {
	public $hasMany = array(
		'Employee' => array(
			'keys' => array(
				'id' => 'company_id'
			),
			'to' => 'lithium\tests\mocks\data\MockEmployees'
		)
	);
	protected $_meta = array(
		'source' => 'companies',
		'connection' => 'test'
	);
}