<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockProductForSchemas extends \lithium\tests\mocks\data\MockBase {

	protected $_schema = array(
		'name' => array('type' => 'string', 'null' => false),
		'price' => array('type' => 'string', 'null' => false),
	);

	public $hasOne = array('MockCreator');

	public $validates = array(
		'name' => array(
			array(
				'notEmpty',
				'message' => 'Name cannot be empty.'
			)
		),
		'price' => array(
			array(
				'notEmpty',
				'message' => 'Price cannot be empty.'
			),
			array(
				'numeric',
				'message' => 'Price must have a numeric value.'
			)
		)
	);

	public static function finders() {
		$self = static::_object();
		return $self->_finders;
	}

	public static function initializers() {
		$self = static::_object();
		return $self->_initializers;
	}
}

?>