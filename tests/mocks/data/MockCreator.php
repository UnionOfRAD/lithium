<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockCreator extends \lithium\data\Model {

	protected $_meta = ['connection' => false];

	protected $_schema = [
		'name' => [
			'default' => 'Moe',
			'type' => 'string',
			'null' => false
		],
		'sign' => [
			'default' => 'bar',
			'type' => 'string',
			'null' => false
		],
		'age' => [
			'default' => 0,
			'type' => 'number',
			'null' => false
		]
	];
}

?>