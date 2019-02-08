<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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