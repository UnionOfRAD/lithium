<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data;

use lithium\util\Validator;

class MockPostForValidates extends \lithium\data\Model {

	protected $_meta = ['source' => 'mock_posts', 'connection' => false];

	public $validates = [
		'title' => 'please enter a title',
		'email' => [
			['notEmpty', 'message' => 'email is empty'],
			['email', 'message' => 'email is not valid'],
			['modelIsSet', 'required' => false, 'message' => 'model is not set'],
			[
				'inList',
				'list' => ['something@test.com','foo@bar.com'],
				'on' => 'customEvent',
				'message' => 'email is not in 1st list'
			],
			[
				'inList',
				'list' => ['something@test.com'],
				'on' => 'anotherCustomEvent',
				'message' => 'email is not in 2nd list'
			]
		]
	];

	public static function init() {
		$class = __CLASS__;
		Validator::add('modelIsSet', function($value, $format, $options) use ($class) {
			if (isset($options['model']) && $options['model'] = $class) {
				return true;
			}
			return false;
		});
	}
}

MockPostForValidates::init();

?>