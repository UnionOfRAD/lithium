<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

use lithium\data\source\Http;

class MockHttpModel extends \lithium\data\Model {

	protected $_meta = [
		'source' => 'posts',
		'connection' => false
	];

	public static $connection = null;

	protected $_schema = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'author_id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255],
		'body' => ['type' => 'text'],
		'created' => ['type' => 'datetime'],
		'updated' => ['type' => 'datetime']
	];

	public static function &connection() {
		if (static::$connection) {
			return static::$connection;
		}
		$result = new Http();
		return $result;
	}
}

?>