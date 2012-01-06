<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data\Source;

use Lithium\Data\Source\MongoDb;

class MockMongoPost extends \Lithium\Data\Model {

	protected $_meta = array(
		'connection' => 'lithium_mongo_test',
		'source' => 'posts'
	);

	protected $_connection;

	protected $_useRealConnection = true;

	public static function schema($field = null) {
		if (is_array($field)) {
			return static::_object()->_schema = $field;
		}
		return parent::schema($field);
	}

	public static function &connection() {
		$self = static::_object();

		if ($self->_useRealConnection) {
			return parent::connection();
		}
		if (!$self->_connection) {
			$self->_connection = new MongoDb(array('autoConnect' => false));
		}
		return $self->_connection;
	}

	public static function resetConnection($mock) {
		$self = static::_object();
		$self->_connection = null;
		$self->_useRealConnection = !$mock;
	}
}

?>