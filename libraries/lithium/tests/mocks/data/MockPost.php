<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD(http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockPost extends \lithium\tests\mocks\data\MockBase {

	public $hasMany = array('MockComment');

	public static function resetSchema() {
		static::_object()->_schema = array();
	}

	public static function overrideSchema(array $schema = array()) {
		static::_object()->_schema = $schema;
	}

	public static function instances() {
		return array_keys(static::$_instances);
	}
}

?>