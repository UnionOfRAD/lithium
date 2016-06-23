<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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