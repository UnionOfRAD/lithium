<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockProduct extends \lithium\data\Model {

	protected $_meta = array('source' => 'mock_products', 'connection' => false);

	protected $_inherits = array('_custom');

	protected $_custom = array(
		'prop1' => 'value1'
	);

	protected $_schema = array(
		'id' => array('type' => 'id'),
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

	public static function attribute($name) {
		$self = static::_object();
		return isset($self->$name) ? $self->$name : null;
	}
}

?>