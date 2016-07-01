<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data;

class MockProduct extends \lithium\data\Model {

	protected $_meta = ['source' => 'mock_products', 'connection' => false];

	protected $_inherits = ['_custom'];

	protected $_custom = [
		'prop1' => 'value1'
	];

	protected $_schema = [
		'id' => ['type' => 'id'],
		'name' => ['type' => 'string', 'null' => false],
		'price' => ['type' => 'string', 'null' => false],
	];

	public $hasOne = ['MockCreator'];

	public $validates = [
		'name' => [
			[
				'notEmpty',
				'message' => 'Name cannot be empty.'
			]
		],
		'price' => [
			[
				'notEmpty',
				'message' => 'Price cannot be empty.'
			],
			[
				'numeric',
				'message' => 'Price must have a numeric value.'
			]
		]
	];

	public static function finders() {
		$self = static::_object();
		return $self->_finders;
	}

	public static function initializers() {
		$self = static::_object();
		return $self->_initializers;
	}

	public static function attribute($name) {
		$self = static::_object();
		return isset($self->$name) ? $self->$name : null;
	}
}

?>