<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockQueryPost extends \lithium\data\Model {

	public $hasMany = ['MockQueryComment'];

	protected $_meta = ['connection' => false, 'key' => 'id'];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'author_id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255],
		'body' => ['type' => 'text'],
		'created' => ['type' => 'datetime'],
		'updated' => ['type' => 'datetime']
	];
}

?>