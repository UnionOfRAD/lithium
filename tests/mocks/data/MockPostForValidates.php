<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

use lithium\util\Validator;

class MockPostForValidates extends \lithium\data\Model {

	protected $_meta = array('source' => 'mock_posts', 'connection' => 'mock-source');

	public $validates = array(
		'title' => 'please enter a title',
		'email' => array(
			array('notEmpty', 'message' => 'email is empty'),
			array('email', 'message' => 'email is not valid'),
			array('modelIsSet', 'required' => false, 'message' => 'model is not set')
		)
	);

	public static function __init() {
		parent::__init();
		$class = __CLASS__;
		Validator::add('modelIsSet', function($value, $format, $options) use ($class){
				if (isset($options['model']) && $options['model'] = $class) {
					return true;
				}
				return false;
			});
	}
}

?>