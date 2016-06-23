<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockDatabasePost extends \lithium\data\Model {

	public $hasMany = [
		'MockDatabaseComment',
		'MockDatabasePostRevision' => [
			'constraints' => ['MockDatabasePostRevision.deleted' => null]
		]
	];

	protected $_meta = ['connection' => false, 'key' => 'id'];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'author_id' => ['type' => 'integer'],
		'title' => ['type' => 'string'],
		'created' => ['type' => 'datetime']
	];
}

?>