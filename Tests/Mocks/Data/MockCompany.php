<?php

namespace Lithium\Tests\Mocks\Data;

class MockCompany extends \Lithium\Data\Model {
	public $hasMany = array(
		'Employee' => array(
			'keys' => array(
				'id' => 'company_id'
			),
			'to' => 'Lithium\Tests\Mocks\Data\MockEmployees'
		)
	);
	protected $_meta = array(
		'source' => 'companies',
		'connection' => 'test'
	);
}

?>