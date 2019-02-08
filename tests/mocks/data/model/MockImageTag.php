<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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