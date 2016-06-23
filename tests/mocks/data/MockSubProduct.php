<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockSubProduct extends \lithium\tests\mocks\data\MockProduct {

	protected $_meta = ['source' => 'mock_products', 'connection' => false];

	protected $_custom = [
		'prop2' => 'value2'
	];

	protected $_schema = [
		'refurb' => ['type' => 'boolean']
	];

	public $validates = [
		'refurb' => [
			[
				'boolean',
				'message' => 'Must have a boolean value.'
			]
		]
	];
}

?>