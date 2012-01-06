<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD(http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data;

class MockPost extends \Lithium\Tests\Mocks\Data\MockBase {

	public $hasMany = array('MockComment');

	public static $connection = null;

	public static function resetSchema() {
		static::_object()->_schema = array();
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