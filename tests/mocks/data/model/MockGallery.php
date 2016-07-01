<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\model;

class MockGallery extends \lithium\data\Model {

	public $hasMany = [
		'Image' => ['to' => 'lithium\tests\mocks\data\model\MockImage']
	];
	public $belongsTo = [
		'Parent' => ['to' => 'lithium\tests\mocks\data\model\MockGallery']
	];

	protected $_meta = [
		'key' => 'id',
		'name' => 'Gallery',
		'source' => 'mock_gallery',
		'connection' => false
	];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'title' => ['type' => 'name']
	];
}

?>