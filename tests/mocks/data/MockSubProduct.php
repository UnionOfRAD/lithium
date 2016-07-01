<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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