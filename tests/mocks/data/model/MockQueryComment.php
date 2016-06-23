<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockQueryComment extends \lithium\data\Model {

	public $belongsTo = ['MockQueryPost'];

	protected $_meta = ['connection' => false];

	protected $_schema = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'author_id' => ['type' => 'integer'],
		'comment' => ['type' => 'text'],
		'created' => ['type' => 'datetime'],
		'updated' => ['type' => 'datetime']
	];
}

?>