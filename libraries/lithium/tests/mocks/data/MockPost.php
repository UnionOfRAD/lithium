<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD(http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockPost extends \lithium\data\Model {

	public $hasMany = array('MockComment');

	protected $_meta = array('connection' => 'mock-source');

	public static function resetSchema() {
		static::_instance()->_schema = array();
	}

	public static function instances() {
		return array_keys(static::$_instances);
	}
}

?>