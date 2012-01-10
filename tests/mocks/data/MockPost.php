<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD(http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockPost extends \lithium\tests\mocks\data\MockBase {

	public $hasMany = array('MockComment');

	public static $connection = null;

	public static function resetSchema() {
		static::_object()->_schema = array();
	}

	public static function resetMeta($config = array()) {
		static::_object()->_meta = $config + static::_object()->_meta;
	}

	public static function overrideSchema(array $schema = array()) {
		static::_object()->_schema = $schema;
	}

	public static function instances() {
		return array_keys(static::$_instances);
	}

	public static function &connection() {
		if (static::$connection) {
			return static::$connection;
		}
		return parent::connection();
	}
}

?>