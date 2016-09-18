<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\fixture\model\mongodb;

class ImagesTagsFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\mongodb\ImagesTags';

	protected $_fields = [
		'_id' => ['type' => 'id'],
		'image_id' => ['type' => 'id'],
		'tag_id' => ['type' => 'id']
	];

	protected $_records = [
		['_id' => 1, 'image' => 1, 'tag' => 1],
		['_id' => 2, 'image' => 1, 'tag' => 3],
		['_id' => 3, 'image' => 2, 'tag' => 5],
		['_id' => 4, 'image' => 3, 'tag' => 6],
		['_id' => 5, 'image' => 4, 'tag' => 6],
		['_id' => 6, 'image' => 4, 'tag' => 3],
		['_id' => 7, 'image' => 4, 'tag' => 1]
	];
}

?>