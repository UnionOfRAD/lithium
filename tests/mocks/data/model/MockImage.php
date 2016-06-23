<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockImage extends \lithium\data\Model {

	public $belongsTo = [
		'Gallery' => ['to' => 'lithium\tests\mocks\data\model\MockGallery']
	];

	public $hasMany = [
		'ImageTag' => ['to' => 'lithium\tests\mocks\data\model\MockImageTag']
	];

	protected $_meta = [
		'key' => 'id',
		'name' => 'Image',
		'source' => 'mock_image',
		'connection' => false
	];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'title' => ['type' => 'string'],
		'image' => ['type' => 'string'],
		'gallery_id' => ['type' => 'integer']
	];
}

?>