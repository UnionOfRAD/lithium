<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockImageTag extends \lithium\data\Model {

	public $belongsTo = [
		'Image' => ['to' => 'lithium\tests\mocks\data\model\MockImage'],
		'Tag' => ['to' => 'lithium\tests\mocks\data\model\MockTag']
	];

	protected $_meta = [
		'key' => 'id',
		'name' => 'ImageTag',
		'source' => 'mock_image_tag',
		'connection' => false
	];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'image_id' => ['type' => 'integer'],
		'tag_id' => ['type' => 'integer']
	];
}

?>