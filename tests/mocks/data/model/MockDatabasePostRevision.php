<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockDatabasePostRevision extends \lithium\data\Model {

	public $belongsTo = ['MockDatabasePost'];

	protected $_meta = ['connection' => false];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'post_id' => ['type' => 'integer'],
		'author_id' => ['type' => 'integer'],
		'title' => ['type' => 'string'],
		'deleted' => ['type' => 'datetime'],
		'created' => ['type' => 'datetime']
	];
}

?>