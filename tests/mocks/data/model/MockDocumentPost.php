<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

use lithium\data\entity\Document;
use lithium\data\collection\DocumentSet;

class MockDocumentPost extends \lithium\data\Model {

	protected $_meta = array('connection' => 'mongo');

	protected static $_connection;

	public static function __init() {}

	public static function schema($field = null) {
		return array(
			'_id' => array('type' => 'id'),
			'foo.bar' => array('type' => 'int')
		);
	}

	public function ret($record, $param1 = null, $param2 = null) {
		if ($param2) {
			return $param2;
		}
		if ($param1) {
			return $param1;
		}
		return null;
	}

	public function medicin($record) {
		return 'lithium';
	}

	public static function &connection() {
		if (!static::$_connection) {
			static::$_connection = new MockDocumentSource();
		}
		return static::$_connection;
	}

	public static function find($type = 'all', array $options = array()) {
		switch ($type) {
			case 'first':
				return new Document(array(
					'data' => array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'),
					'model' => __CLASS__
				));
			break;
			case 'all':
			default :
				return new DocumentSet(array(
					'data' => array(
						array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'),
						array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'),
						array('id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three')
					),
					'model' => __CLASS__
				));
			break;
		}
	}
}

?>