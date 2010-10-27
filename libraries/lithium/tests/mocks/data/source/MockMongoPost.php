<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

class MockMongoPost extends \lithium\data\Model {

	protected $_meta = array(
		'connection' => 'lithium_mongo_test',
		'source' => 'posts'
	);

	public static function schema($field = null) {
		if (is_array($field)) {
			return static::_object()->_schema = $field;
		}
		return parent::schema($field);
	}
}

?>