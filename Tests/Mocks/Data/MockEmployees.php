<?php

namespace Lithium\Tests\Mocks\Data;

class MockEmployees extends \Lithium\Data\Model {

	protected $_meta = array(
		'source' => 'employees',
		'connection' => 'test'
	);
}

?>