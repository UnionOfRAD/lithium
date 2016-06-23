<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\http\adapter;

use lithium\data\DocumentSchema;

class MockCouchPost extends \lithium\data\Model {

	protected $_meta = ['source' => 'posts', 'connection' => false, 'key' => 'id'];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'author_id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255],
		'body' => ['type' => 'text'],
		'created' => ['type' => 'datetime'],
		'updated' => ['type' => 'datetime']
	];

	public static function resetSchema($array = false) {
		if ($array) {
			return static::_object()->_schema = [];
		}
		static::_object()->_schema = new DocumentSchema();
	}
}

?>