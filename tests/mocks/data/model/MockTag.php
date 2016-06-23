<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockTag extends \lithium\data\Model {

	public $hasMany = [
		'ImageTag' => ['to' => 'lithium\tests\mocks\data\model\MockImageTag']
	];

	protected $_meta = [
		'key' => 'id',
		'name' => 'Tag',
		'source' => 'mock_tag',
		'connection' => false
	];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string']
	];
}

?>