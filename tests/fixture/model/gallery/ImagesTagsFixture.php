<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\fixture\model\gallery;

class ImagesTagsFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\gallery\ImagesTags';

	protected $_fields = [
		'id' => ['type' => 'id'],
		'image_id' => ['type' => 'integer', 'length' => 11],
		'tag_id' => ['type' => 'integer', 'length' => 11]
	];

	protected $_records = [
		['id' => 1, 'image_id' => 1, 'tag_id' => 1],
		['id' => 2, 'image_id' => 1, 'tag_id' => 3],
		['id' => 3, 'image_id' => 2, 'tag_id' => 5],
		['id' => 4, 'image_id' => 3, 'tag_id' => 6],
		['id' => 5, 'image_id' => 4, 'tag_id' => 6],
		['id' => 6, 'image_id' => 4, 'tag_id' => 3],
		['id' => 7, 'image_id' => 4, 'tag_id' => 1]
	];
}

?>