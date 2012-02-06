<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD(http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

use lithium\data\Schema;

class MockBase extends \lithium\data\Model {

	protected $_meta = array('connection' => null);

	public static $connection = null;

	public static function __init() {
		static::_isBase(__CLASS__, true);
		parent::__init();
	}

	public static function resetSchema($array = false) {
		if ($array) {
			return static::_object()->_schema = array();
		}
		static::_object()->_schema = new Schema();
	}

	public static function &connection() {
		if (static::$connection) {
			return static::$connection;
		}
		return parent::connection();
	}
}

?>